/**
 * Plugin Name: Clerk
 * Plugin URI: https://clerk.io/
 * Description: Clerk.io Turns More Browsers Into Buyers
 * Version: 4.0.1
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
		var getUrlParameter = function getUrlParameter(sParam)
		{
			var sPageURL       = window.location.search.substring( 1 ),
			sURLVariables      = sPageURL.split( '&' ),
			sURLVariablesCount = sURLVariables.length,
			sParameterName,
			i;

			for (i = 0; i < sURLVariablesCount; i++) {
				sParameterName = sURLVariables[i].split( '=' );

				if (sParameterName[0] === sParam) {
					return sParameterName[1] === undefined ? true : decodeURIComponent( sParameterName[1] );
				}
			}
		};

		$( "body" ).on(
			"added_to_cart",
			function (e, fragments, hash, button) {
				// Attempt to get product id from data attribute.
				var product_id = $( button ).data( 'product_id' );

				if (product_id) {
					// Redirect to powerstep page if type is page.
					if (variables.type === 'page') {
						window.location.replace( variables.powerstep_url + "?product_id=" + encodeURIComponent( product_id ) );
					}

					var data = {
						'action': 'clerk_powerstep',
						'product_id': product_id
					};

					$.ajax(
						{
							url: variables.ajax_url,
							method: 'post',
							data: data,
							success: function (res) {
								$( 'body' ).append( res );
								var popup = $( "#clerk_powerstep" );

								$( ".clerk-popup-close" ).on(
									"click",
									function () {
										popup.hide();
									}
								);
								$( ".clerk-powerstep-close" ).on(
									"click",
									function () {
										popup.hide();
									}
								);
								popup.show();

								Clerk( 'content','.clerk_powerstep_templates .clerk' );
							}
						}
					);
					$( 'body' ).trigger( 'post-load' );
				}
			}
		);

		$(
			function () {

				var clerk_powerstep = getUrlParameter( 'clerk_powerstep' );
				var product_id      = getUrlParameter( 'product_id' );

				if (clerk_powerstep) {

					if (product_id) {
						// Redirect to powerstep page if type is page.
						if (variables.type === 'page') {
							window.location.replace( variables.powerstep_url + "?product_id=" + encodeURIComponent( product_id ) );
						}

						var data = {
							'action': 'clerk_powerstep',
							'product_id': product_id
						};

						$.ajax(
							{
								url: variables.ajax_url,
								method: 'post',
								data: data,
								success: function (res) {
									$( 'body' ).append( res );
									var popup = $( "#clerk_powerstep" );

									$( ".clerk-popup-close" ).on(
										"click",
										function () {
											popup.hide();
											window.history.pushState( {}, document.title, window.location.href.split( "?" )[0] );
										}
									);

									$( ".clerk-powerstep-close" ).on(
										"click",
										function () {
											popup.hide();
											window.history.pushState( {}, document.title, window.location.href.split( "?" )[0] );
										}
									);
									popup.show();

									Clerk( 'content','.clerk_powerstep_templates .clerk' );
								}
							}
						);
						$( 'body' ).trigger( 'post-load' );
					}

				}
			}
		);

	}
);
