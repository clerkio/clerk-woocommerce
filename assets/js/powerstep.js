/**
 * Plugin Name: Clerk
 * Plugin URI: https://clerk.io/
 * Description: Clerk.io Turns More Browsers Into Buyers
 * Version: 4.1.2
 * Author: Clerk.io
 * Author URI: https://clerk.io
 *
 * Text Domain: clerk
 * Domain Path: /i18n/languages/
 * License: MIT
 *
 * @package clerkio/clerk-woocommerce
 */

function ready(fn) {
	if (document.readyState !== 'loading') {
		fn();
		return;
	}
	document.addEventListener('DOMContentLoaded', fn);
}
ready(() => {
	const getUrlParameter = (sParam) => {
		const sPageURL = window.location.search.substring(1);
		for (const parameter of sPageURL.split('&')) {
			const [key, value] = parameter.split('=');

			if (key === sParam) {
				return value === undefined ? true : decodeURIComponent(value);
			}
		}
	};
	const popup = document.getElementById("clerk_powerstep");
	if (popup) {
		console.log("Popup HTML:", popup.outerHTML);

		const initialDisplayStyle = window.getComputedStyle(popup).display;
		console.log("Popup initial display style:", initialDisplayStyle);

		if (initialDisplayStyle === "none" || initialDisplayStyle === "") {
			popup.style.display = "block";

			const updatedDisplayStyle = window.getComputedStyle(popup).display;
			console.log("Popup display after setting to block:", updatedDisplayStyle);
		}

	}

	// Function to show popup
	const showPopup = (res) => {
		document.body.insertAdjacentHTML('beforeend', res);
		const popup = document.getElementById('clerk_powerstep');

		var closeButtons = document.querySelectorAll('.clerk-popup-close, .clerk-powerstep-close');
		if (closeButtons.length < 2) {
			if (!popup.querySelector(".clerk-popup-close")) {
				console.debug("No close button found, adding one");
				const closeButton = document.createElement("span");
				closeButton.className = "clerk-popup-close";
				closeButton.innerHTML = "&times;";
				// closeButton.addEventListener("click", (e) => {
				// 	e.stopPropagation();
				// 	popup.style.display = "none";
				// });
				popup.prepend(closeButton);
			}
		}

		var closeButtons = document.querySelectorAll('.clerk-popup-close, .clerk-powerstep-close');
		console.log('Close buttons:', closeButtons);
		closeButtons.forEach((button) => {
			button.addEventListener('click', () => {
				console.log('Close button clicked');
				popup.style.display = 'none';
				window.history.pushState({}, document.title, window.location.href.split('?')[0]);
			});
		});

		popup.style.display = 'block';
		Clerk('content', '.clerk_powerstep_templates .clerk');
	};

	// Event listener for "added_to_cart" event
	document.body.addEventListener('added_to_cart', async (e, fragments, hash, button) => {
		console.log('Product added to cart:', button);

		const productId = button.dataset.product_id;
		if (productId) {
			if (variables.type === 'page') {
				window.location.replace(variables.powerstep_url + '?product_id=' + encodeURIComponent(productId));
				return;
			}

			const data = {
				'action': 'clerk_powerstep',
				'product_id': productId
			};

			const response = await fetch(variables.ajax_url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams(data)
			});

			if (response.ok) {
				const res = await response.text();
				console.log('Response:', res);
				showPopup(res);

				const afterAjaxDisplayStyle = window.getComputedStyle(popup).display;
				console.log('Popup display style after AJAX:', afterAjaxDisplayStyle);
			} else {
				console.error('Failed to fetch:', response.statusText);
			}
		}
	});

	// Check if the URL has the clerk_powerstep parameter
	if (getUrlParameter('clerk_powerstep') && getUrlParameter('product_id')) {
		const productId = getUrlParameter('product_id');
		if (variables.type === 'page') {
			window.location.replace(variables.powerstep_url + '?product_id=' + encodeURIComponent(productId));
			return;
		}

		const data = {
			'action': 'clerk_powerstep',
			'product_id': productId
		};

		fetch(variables.ajax_url, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: new URLSearchParams(data)
		})
			.then(response => response.text())
			.then(res => showPopup(res))
			.catch(error => console.error('Error:', error));
	}
});
