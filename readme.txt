=== Clerk ===
Contributors: clerkio
Tags: product recommendations, semantic search, customer conversion, customer retention, customer segmentation, webshop personalization, sales optimisation
License: MIT
License URI: https://opensource.org/licenses/MIT
Tested up to: 4.9.4
WC requires at least: 2.6
WC tested up to: 3.0

== Description ==
Clerk.io is a software that helps your customers buy more from your webshop, through 4 amazing feature:

* Dynamic Product Recommendations, which are based directly on your customers behaviour
* An Intelligent Search Engine, which ranks products based on what most customers will be likely to buy
* Automated Email Recommendations, which always shows the right products to each individual customer
* Customer Segmentation, which lets you find the best possible Audience for any type of marketing.

Clerk.io’s algorithms use your orders to continually updates each feature, so the right products will always be shown
to each unique customer, based on their behaviour.

When Clerk.io has been setup, it runs 100% automatically, so you can spend your time on other important parts of your business.

With this plugin, you can easily get started with Clerk.io in a matter of minutes.

You can get a 7 day free trial of Clerk.io, by going here:
https://my.clerk.io/#/signup

Once signed up, simply login to your my.clerk.io backend, which will guide you through the entire WooCommerce setup.


== Changelog ==
= 1.4.0 - 2018-01-25 =
* Add powerstep popup
* Remove emails from order sync if collect emails is disabled

= 1.3.8 - 2017-12-15 =
* Add option to disable order synchronization

= 1.3.7 - 2017-12-13 =
* Add exit intent

= 1.3.6 - 2017-12-07 =
* Fix error causing first product page to be loaded twice

= 1.3.5 - 2017-11-17 =
* Add wpml config file as first part of WPML support

= 1.3.4 - 2017-10-25 =
* Fix bug causing cart url to be overwritten with null

= 1.3.3 - 2017-10-04 =
* Make product endpoint use catalog image size from woocommerce

= 1.3.2 - 2017-10-04 =
* Add sanity check to order endpoint to avoid division by zero

= 1.3.1 - 2017-09-20 =
* Fix error with get_status in WooCommerce 2.6

= 1.3.0 - 2017-09-20 =
* Add insights dashboards

= 1.2.10 - 2017-09-20 =
* Add logo to menu
* Fix order pagination error with WooCommerce 3.1

= 1.2.9 - 2017-08-23 =
* Fix bug causing category import to go on forever
* Fix issue with 3rd party plugins

= 1.2.8 - 2017-07-12 =
* Remove undefined index in class-clerk-admin-settings.php

= 1.2.7 - 2017-07-12 =
* Fix undefined index in class-clerk-admin-settings.php

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
