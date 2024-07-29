/**
 * Plugin Name: Clerk
 * Plugin URI: https://clerk.io/
 * Description: Clerk.io Turns More Browsers Into Buyers
 * Version: 4.1.9
 * Author: Clerk.io
 * Author URI: https://clerk.io
 *
 * Text Domain: clerk
 * Domain Path: /i18n/languages/
 * License: MIT
 *
 * @package clerkio/clerk-woocommerce
 */

/**
 * Class Containing Powerstep Initiator Code
 */
class ClerkPowerstep {

	init(){
		document.addEventListener( 'DOMContentLoaded', this.handle_powerstep.bind( this ) );
		if (document.readyState !== 'loading') {
			this.handle_powerstep();
			return;
		}
	}

	handle_powerstep(){
		const popup = document.getElementById( "clerk_powerstep" );
		if (popup) {
			const initialDisplayStyle = window.getComputedStyle( popup ).display;
			if (initialDisplayStyle === "none" || initialDisplayStyle === "") {
				popup.style.display       = "block";
				const updatedDisplayStyle = window.getComputedStyle( popup ).display;
			}
		}

		document.body.addEventListener(
			'added_to_cart',
			async function(e, fragments, hash, button){
				const productId = button.dataset.product_id;

				if (productId) {
					if (variables.type === 'page') {
						window.location.replace( variables.powerstep_url + '?product_id=' + encodeURIComponent( productId ) );
						return;
					}
					const response = await fetch(
						variables.ajax_url,
						{
							method: 'POST',
							headers: {
								'Content-Type': 'application/x-www-form-urlencoded'
							},
							body: new URLSearchParams(
								{
									'action': 'clerk_powerstep',
									'product_id': productId
								}
							)
						}
					)

					if (response.ok) {
						const res = await response.text();
						this.show_popup( res );

						const afterAjaxDisplayStyle = window.getComputedStyle( popup ).display;
					} else {
						console.error( 'Failed to fetch:', response.statusText );
					}
				}
			}
		)

		if (this.get_url_parameter( 'clerk_powerstep' ) && this.get_url_parameter( 'product_id' )) {
			const productId = this.get_url_parameter( 'product_id' );
			if (variables.type === 'page') {
				window.location.replace( variables.powerstep_url + '?product_id=' + encodeURIComponent( productId ) );
				return;
			}

			fetch(
				variables.ajax_url,
				{
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded'
					},
					body: new URLSearchParams(
						{
							'action': 'clerk_powerstep',
							'product_id': productId
						}
					)
				}
			)
			.then( response => response.text() )
			.then( res => this.show_popup( res ) )
			.catch( error => console.error( 'Error:', error ) );
		}
	}

	get_url_parameter(search_param){
		const query_param_string = window.location.search.substring( 1 );
		for (const parameter of query_param_string.split( '&' )) {
			const [key, value] = parameter.split( '=' );
			if (key === search_param) {
				return value === undefined ? true : decodeURIComponent( value );
			}
		}
	}

	show_popup(res){
		document.body.insertAdjacentHTML( 'beforeend', res );
		const popup       = document.getElementById( 'clerk_powerstep' );
		let close_buttons = document.querySelectorAll( '.clerk-popup-close, .clerk-powerstep-close' );

		if (close_buttons.length < 2 && ! popup.querySelector( ".clerk-popup-close" )) {
			const close_button     = document.createElement( "span" );
			close_button.className = "clerk-popup-close";
			close_button.innerHTML = "&times;";
			popup.prepend( close_button );
			close_buttons = document.querySelectorAll( '.clerk-popup-close, .clerk-powerstep-close' );
		}

		const close_btn_length = close_buttons.length ? close_buttons.length : 0;

		for (let i = 0; i < close_btn_length; i++) {
			const btn = close_buttons[i];
			btn.addEventListener(
				'click',
				function() {
					popup.style.display = 'none';
					window.history.pushState( {}, document.title, window.location.href.split( '?' )[0] );
				}
			)
		}

		popup.style.display = 'block';
		Clerk( 'content', '.clerk_powerstep_templates .clerk' );
	}

}

const clerk_powerstep = new ClerkPowerstep();
clerk_powerstep.init();
