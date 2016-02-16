<?php

	/*
	Plugin Name: CodeCaptain Image to Figure converter
	Description: Wrap every image in a post in a figure tag & make them responsive, while reserving space on the page
	Version: 1.0
	Author: Sabatino Masala
	Author URI: http://www.codecaptain.io/
	*/

	function cc_print_responsive_styles() {
		?>
		<style>
			<?php include 'responsive-styles.css'; ?>
		</style>
		<?
	}

	function cc_wrap_images( $content ) {

		// Create the document
		$dom = new DOMDocument();
		
		// Mute error output & load up the wysiwyg content
		libxml_use_internal_errors(true);
		// We need utf8 encoding, or we'd get unexpected output
		$dom->LoadHTML('<?xml encoding="utf-8" ?>' . $content);
		libxml_clear_errors();

		// Fetch all the images
		$images = $dom->getElementsByTagName('img');

		foreach ($images as $image) {

			// Get the image src
			$src = $image->getAttribute('src');
			// Get the image alt
			$alt = $image->getAttribute('alt');
			// If no alt is set, we have our default
			if (!$alt || $alt == '') {
				$alt = '&copy; CodeCaptain';
			}

			// Get all the image classes
			$classes = $image->getAttribute('class');
			
			// Store width & height in a variable
			$width = $image->getAttribute('width');
			$height = $image->getAttribute('height');

			// Division by 0 protection
			if (!$width) {
				$width = 1;
			}

			// We want to calculate the aspect ratio of the image (2 decimals)
			$aspect = round($height / $width * 10000) / 100;

			// Create the figure tag
			$figure = $dom->createElement('figure');

			// Create the responsive container
			$responsiveContainer = $dom->createElement('div');
			$responsiveContainer->setAttribute('class', 'cc-responsive-container');

			// Create the wrapper
			$imageWrapper = $dom->createElement('div');
			$imageWrapper->setAttribute('class', 'image-wrapper');

			// Create the alignment div
			$wpAlignDiv = $dom->createElement('div');
			$wpAlignDiv->setAttribute('class', 'wp-aligner ' . $classes);

			// Set the padding-bottom to the aspect as a percentage
			$responsiveContainer->setAttribute('style', 'padding-bottom: ' . $aspect . '%');

			// We don't want to stretch the image, so we'll set the max-width and max-height to $width and $height respectively
			$imageWrapper->setAttribute('style', 'max-width: ' . $width . 'px; max-height: ' . $height . 'px;');

			// Create the figcaption
			$figCaption = $dom->createElement('figcaption');
			// Set the value of the figcaption to the alt tag of the image
			$figCaption->nodeValue = $alt;

			/*
				We need to achieve the following structure:
					figure
						.wp-aligner
							.image-wrapper
								.cc-responsive-container
									img
						figcaption
			*/

			// Add the figcaption and aligndiv to the figure
			$figure->appendChild($wpAlignDiv);
			$figure->appendChild($figCaption);

			// Add the imagewrapper to the aligndiv
			$wpAlignDiv->appendChild($imageWrapper);

			// Add the responsive container to the imagewrapper
			$imageWrapper->appendChild($responsiveContainer);

			// Finally we need to replace the image with our new figure
			$image->parentNode->replaceChild($figure, $image);

			// And after we replaced the image, we want to re-add it to the responsive container
			$responsiveContainer->appendChild($image);

			// We don't need the classes on the image anymore
			$image->removeAttribute('class');

		}

		// Let's loop over all newly created figures
		$figures = $dom->getElementsByTagName('figure');

		// If the figure is wrapped in a p-tag, we'll remove the p-tag
		foreach ($figures as $figure) {

			if ($figure->parentNode->tagName == 'p') {
				$parent = $figure->parentNode;
				$parent->parentNode->replaceChild($figure, $parent);
			}

		}

		// Last step, return our new content
		return $dom->saveHTML();

	}

	add_filter( 'the_content', 'cc_wrap_images' );
	add_action( 'wp_print_styles', 'cc_print_responsive_styles' );

?>