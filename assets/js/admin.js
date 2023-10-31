const admin_form = document.querySelector( 'form#clerkAdminForm' );
if (admin_form) {
	admin_form.addEventListener(
		'submit',
		(e) => {
			collect_attributes();
		}
	);
}

function remove_facet_line(data_value) {
	const admin_form = document.querySelector( 'form#clerkAdminForm' );
	const elements   = admin_form.querySelectorAll( `[data = "${data_value}"]` );
	elements.forEach(
		el => {
			el.remove();
		}
	);
}

const clerk_submit_admin_form = () => {
	document.querySelector( '#submit' ).click();
}

const close_btn = admin_form.querySelector( '.closebtn' );
if (close_btn) {
	close_btn.addEventListener(
		'click',
		(e) => {
			admin_form.querySelector( '.alert' ).remove();
		}
	)
}
const custom_facet_input = document.querySelector( '#faceted_navigation_custom' );
if (custom_facet_input) {
	custom_facet_input.addEventListener(
		'keydown',
		(e) => {
			if (e.keyCode == 13) {
				e.preventDefault();
				add_facet();
			}
		}
	)
}


function add_facet() {
	let linescount           = document.querySelectorAll( '.facets_content .facets_lines' ).length;
	const custom_facet_input = document.querySelector( '#faceted_navigation_custom' );
	const facet_value        = custom_facet_input.value;

	const facets_lines = document.createElement( "div" );
	facets_lines.setAttribute( "class", "facets_lines" );
	facets_lines.setAttribute( "data", facet_value );

	const facet_td = document.createElement( "div" );

	const facet = document.createElement( "input" );
	facet.setAttribute( "class", "facets_facet" );
	facet.setAttribute( "type", "text" );
	facet.setAttribute( "value", facet_value );
	facet.setAttribute( "readonly", '' );

	const title_td = document.createElement( "div" );
	const title    = document.createElement( "input" );
	title.setAttribute( "class", "facets_title" );
	title.setAttribute( "type", "text" );
	title.setAttribute( "value", '' );

	const position_td = document.createElement( "div" );
	const position    = document.createElement( "input" );
	position.setAttribute( "class", "facets_position" );
	position.setAttribute( "type", "text" );
	position.setAttribute( "value", linescount + 1 );

	const checkbox_td = document.createElement( "div" );

	const checkbox = document.createElement( "input" );
	checkbox.setAttribute( "type", "checkbox" );
	checkbox.setAttribute( "class", "faceted_enabled" );
	checkbox.setAttribute( "value", "1" );

	const remove = document.createElement( "div" );
	remove.setAttribute( "class", "close" );
	remove.setAttribute( "onclick", `remove_facet_line( '${facet_value}' );` );

	facet_td.append( facet )
	facets_lines.append( facet_td );
	title_td.append( title );
	facets_lines.append( title_td );
	position_td.append( position );
	facets_lines.append( position_td );
	checkbox_td.append( checkbox );
	checkbox_td.append( remove );
	facets_lines.append( checkbox_td );

	document.querySelector( '.facets_content' ).append( facets_lines );

	custom_facet_input.value = '';
}


function collect_attributes() {

	let attribute_reference = [];

	let count                           = 0;
	const countFacets                   = document.querySelectorAll( 'input[class^=facets_facet]' ).length;
	const facet_attributes_value_holder = document.querySelector( '#faceted_navigation' );

	while ((count + 1) <= countFacets) {

		attribute_reference.push(
			{
				attribute: document.querySelector( `input[class ^ = facets_facet]:eq( ${count} )` ).value,
				title: document.querySelector( `input[class ^ = facets_title]:eq( ${count} )` ).value,
				position: document.querySelector( `input[class ^ = facets_position]:eq( ${count} )` ).value,
				checked: document.querySelector( `input[class ^ = faceted_enabled]:eq( ${count} )` ) ? .checked
			}
		);

		count += 1;

		}

		facet_attributes_value_holder.value = JSON.stringify( attribute_reference );

		}

		document.querySelector( '#powerstep_custom_text_enabled' ).addEventListener(
			'click',
			function(e){
				switch (e.target.checked) {
					case true:
						document.querySelector( '#powerstep_custom_text_back' ).removeAttribute( 'disabled' );
						document.querySelector( '#powerstep_custom_text_title' ).removeAttribute( 'disabled' );
						document.querySelector( '#powerstep_custom_text_cart' ).removeAttribute( 'disabled' );
						break;
					case false:
						document.querySelector( '#powerstep_custom_text_back' ).setAttribute( 'disabled', true );
						document.querySelector( '#powerstep_custom_text_title' ).setAttribute( 'disabled', true );
						document.querySelector( '#powerstep_custom_text_cart' ).setAttribute( 'disabled', true );
						break;
				}
			}
		);
const customPowerstepTexts = document.querySelector( '#powerstep_custom_text_enabled' ).checked;
if ( ! customPowerstepTexts) {
	document.querySelector( '#powerstep_custom_text_back' ).setAttribute( 'disabled', true );
	document.querySelector( '#powerstep_custom_text_title' ).setAttribute( 'disabled', true );
	document.querySelector( '#powerstep_custom_text_cart' ).setAttribute( 'disabled', true );
}
