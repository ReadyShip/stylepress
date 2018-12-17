<?php
/**
 * Custom CSS Support for 3rd party theme
 *
 * @package stylepress
 */

defined( 'STYLEPRESS_PATH' ) || exit;


$style = '
	    .stylepress-template-std .elementor-page .site {
            max-width: 100%;
			overflow: hidden;
        }
        .stylepress-template-std .site::before {
            display: none;
        }
        .stylepress-template-std .site-header {
            max-width: 100%;
        }
	';
wp_add_inline_style( 'twentyfourteen-style', $style );
