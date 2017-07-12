=== Clerk ===
Contributors: clerkio
Tags: product recommendations, semantic search, customer conversion, customer retention, customer segmentation, webshop personalization, sales optimisation
License: MIT
License URI: https://opensource.org/licenses/MIT
WC requires at least: 2.6
WC tested up to: 3.0

== Changelog ==
= 1.2.6 - 2017-07-12 =
* Fix undefined constant in clerk.php

= 1.2.5 - 2017-06-30 =
* Add 'is_salable' attribute to indicate wheter a product is in stock

= 1.2.4 - 2017-06-15 =
* Change product API to only send published products

= 1.2.3 - 2017-05-29 =
* Show correct import url in configuration

= 1.2.2 - 2017-05-18 =
* Add version endpoint to REST API
* Add support for additional fields
* Add option to toggle email collection

= 1.2.1 - 2017-05-18 =
* Cast prices to floats to avoid empty strings when price is 0

= 1.2.0 - 2017-05-18 =
* Change API endpoints according to new specification

= 1.1.0 - 2017-05-17 =
* Ensure backwards compatibility with WC 2.6

= 1.0.1 - 2017-05-02 =
* Send product object on order import instead of just product id

= 1.0.0 - 2017-04-12 =
* Initial release of WooCommerce extension for Clerk.io
