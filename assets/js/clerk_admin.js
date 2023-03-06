/**
 * Plugin Name: Clerk
 * Plugin URI: https://clerk.io/
 * Description: Clerk.io Turns More Browsers Into Buyers
 * Version: 4.0.4
 * Author: Clerk.io
 * Author URI: https://clerk.io
 *
 * Text Domain: clerk
 * Domain Path: /i18n/languages/
 * License: MIT
 *
 * @package clerkio/clerk-woocommerce
 */

jQuery( document ).ready(
	function ($) {

		var before_logging_level;

		$( '#clerk-dialog' ).dialog(
			{
				title: 'Changing Logging Level',
				dialogClass: 'wp-dialog',
				autoOpen: false,
				draggable: false,
				width: 'auto',
				modal: true,
				resizable: false,
				closeOnEscape: false,
				position: {
					my: "center",
					at: "center",
					of: window
				},
				buttons: {
					"Cancel": function () {
						document.querySelector( '#log_level [value="' + before_logging_level + '"]' ).selected = true;
						$( this ).dialog( 'close' );
					},
					"I'm Sure": function () {
						$( this ).dialog( 'close' );
					},

				},
				open: function () {
					// close dialog by clicking the overlay behind it.
					$( ".ui-dialog-titlebar-close" ).hide();

				},
			}
		);

		$( '#clerk-dialog' ).html( '<p>Debug Mode should not be used in production! Are you sure you want to change logging level to Debug Mode ? </p>' );

		$( '#log_level' ).focus(
			function () {

				before_logging_level = $( '#log_level' ).val();

			}
		).change(
			function () {

				if ($( '#log_level' ).val() !== 'Error + Warn + Debug Mode') {

					before_logging_level = $( '#log_level' ).val();

				} else {

					$( '#clerk-dialog' ).dialog( 'open' );

				}

			}
		);
	}
);
