/**
 * Plugin Name: Clerk
 * Plugin URI: https://clerk.io/
 * Description: Clerk.io Turns More Browsers Into Buyers
 * Version: 4.1.6
 * Author: Clerk.io
 * Author URI: https://clerk.io
 *
 * Text Domain: clerk
 * Domain Path: /i18n/languages/
 * License: MIT
 *
 * @package clerkio/clerk-woocommerce
 * @file Handles front end content get.
 * Ajax handler.
 */

/**
 * Check Page for Clerk Embedded Content
 *
 * @param {Node} el
 */
function clerkGetContent(el)
{
	var data = {
		'action': 'clerk_get_parameters_for_content',
		'content': $( el ).val()
	};

	var categorySelect = $( el ).parent().parent().find( "p[data-clerk-category]" );
	var productSelect  = $( el ).parent().parent().find( "p[data-clerk-product]" );

	// Reset selected values.
	categorySelect.find( "select" ).prop( "selectedIndex", 0 );
	productSelect.find( "select" ).prop( "selectedIndex", 0 );

	categorySelect.hide();
	productSelect.hide();

	$.post(
		ajaxurl,
		data,
		function (response) {
			if (response && response.category) {
				categorySelect.show();
			}

			if (response && response.product) {
				productSelect.show();
			}
		},
		"json"
	);
}
