
jQuery('.wrap form').submit(function () {

	CollectAttributes();

});

function remove_facet_line(data_value) {

	jQuery("[data=" + data_value + "]").remove();

}

function add_facet() {
	var linescount = jQuery('#facets_content #facets_lines').length;

	facets_lines = document.createElement("tr");
	facets_lines.setAttribute("id", "facets_lines");
	facets_lines.setAttribute("data", jQuery('#faceted_navigation_custom').val());

	facet_td = document.createElement("td");

	facet = document.createElement("input");
	facet.setAttribute("id", "facets_facet");
	facet.setAttribute("type", "text");
	facet.setAttribute("value", jQuery('#faceted_navigation_custom').val());
	facet.setAttribute("readonly", '');

	title_td = document.createElement("td");
	title = document.createElement("input");
	title.setAttribute("id", "facets_title");
	title.setAttribute("type", "text");
	title.setAttribute("value", '');


	position_td = document.createElement("td");
	position = document.createElement("input");
	position.setAttribute("id", "facets_position");
	position.setAttribute("type", "text");
	position.setAttribute("value", linescount + 1);

	checkbox_td = document.createElement("td");

	checkbox = document.createElement("input");
	checkbox.setAttribute("type", "checkbox");
	checkbox.setAttribute("id", "faceted_enabled");
	checkbox.setAttribute("value", "1");


	remove = document.createElement("a");
	remove.setAttribute("class", "close");
	remove.setAttribute("onclick", 'remove_facet_line("' + jQuery("#faceted_navigation_custom").val() + '");');

	facet_td.append(facet)
	facets_lines.append(facet_td);
	title_td.append(title);
	facets_lines.append(title_td);
	position_td.append(position);
	facets_lines.append(position_td);
	checkbox_td.append(checkbox);
	checkbox_td.append(remove);
	facets_lines.append(checkbox_td);

	jQuery('#facets_content').append(facets_lines);

	jQuery('#faceted_navigation_custom').val('')

}


function CollectAttributes() {

	Attributes = [];

	count = 0;
	countFacets = jQuery('input[class^=facets_facet]').length;

	while ((count + 1) <= countFacets) {

		var data = {

			attribute: jQuery('input[class^=facets_facet]:eq(' + count + ')').val(),
			title: jQuery('input[class^=facets_title]:eq(' + count + ')').val(),
			position: jQuery('input[class^=facets_position]:eq(' + count + ')').val(),
			checked: jQuery('input[class^=faceted_enabled]:eq(' + count + ')').is(':checked')

		};

		Attributes.push(data);

		count = count + 1;

	}

	jQuery('#faceted_navigation').val(JSON.stringify(Attributes));

}

jQuery(".closebtn").click(function () {
	jQuery(".alert").remove();
});

const clerkSubmitAdminForm = () => {
    document.querySelector('#submit').click();
}
document.addEventListener('DOMContentLoaded', function(){
    document.querySelector('#powerstep_custom_text_enabled').addEventListener('click', function(e){
        switch(e.target.checked){
            case true:
                document.querySelector('#powerstep_custom_text_back').removeAttribute('disabled');
                document.querySelector('#powerstep_custom_text_title').removeAttribute('disabled');
                document.querySelector('#powerstep_custom_text_cart').removeAttribute('disabled');
                break;
            case false:
                document.querySelector('#powerstep_custom_text_back').setAttribute('disabled', true);
                document.querySelector('#powerstep_custom_text_title').setAttribute('disabled', true);
                document.querySelector('#powerstep_custom_text_cart').setAttribute('disabled', true);
                break;
        }
    });
    let customPowerstepTexts = document.querySelector('#powerstep_custom_text_enabled').checked;
    if(!customPowerstepTexts){
        document.querySelector('#powerstep_custom_text_back').setAttribute('disabled', true);
        document.querySelector('#powerstep_custom_text_title').setAttribute('disabled', true);
        document.querySelector('#powerstep_custom_text_cart').setAttribute('disabled', true);
    }
});