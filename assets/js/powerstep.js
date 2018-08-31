jQuery(document).ready(function ($) {
    $("body").on("added_to_cart", function (e, fragments, hash, button) {
        //Attempt to get product id from data attribute
        var product_id = $(button).data('product_id');

        if (product_id) {
            //Redirect to powerstep page if type is page
            if (variables.type === 'page') {
                window.location.replace(variables.powerstep_url + "?product_id=" + encodeURIComponent(product_id));
            }

            var data = {
                'action': 'clerk_powerstep',
                'product_id': product_id
            };

            $.ajax({
                url: variables.ajax_url,
                method: 'post',
                data: data,
                success: function (res) {
                    $('body').append(res);
                    var popup = Clerk.ui.popup("#clerk_powerstep");

                    $(".clerk_powerstep_close").on("click", function () {
                        popup.close();
                    });

                    popup.show();

                    Clerk.renderBlocks(".clerk_powerstep_templates .clerk");
                }
            });
            $('body').trigger('post-load');
        }
    });
});