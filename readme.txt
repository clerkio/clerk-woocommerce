=== Clerk ===
Contributors: clerkio, audunhus
Tags: product recommendations, semantic search, customer conversion, customer retention, customer segmentation, webshop personalization, sales optimisation
License: MIT
License URI: https://opensource.org/licenses/MIT
Tested up to: 6.1.1
WC requires at least: 4.4.0
WC tested up to: 7.1.0
Stable Tag: 4.0.9

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
= 4.1.0 - 2023-08-01
* Added support for currency conversion for clients using aelia_currency_convert.
* Fixed variant id param getting trimmed with non ajax powerstep page for some users.
* Change realtime update hook for products to be more deferred.
* Added option to exclude custom attributes from getting stripped and split.

= 4.0.9 - 2023-04-25
* Added more type safety to realtime update variant emulation.

= 4.0.8 - 2023-04-25
* Forced variant variations to instantiated as Class for real-time updates.

= 4.0.7 - 2023-03-30
* Fixed search page settings bug.

= 4.0.6 - 2023-03-29
* Force base api host to https

= 4.0.5 - 2023-03-20
* Force cast facets as array for admin

= 4.0.4 - 2023-03-06
* Fixed image mapping for custom image sizes on first install

= 4.0.3 - 2023-02-14
* Improved page / post import for custom types
* Removed email signup checkbox if message is only whitespace
* Fixed tax rate calculation on grouped products
* Fixed realtime update bug when duplicating individual products

= 4.0.2 - 2023-01-30
* Improved Object returns handling for custom taxonomies
* Added catalog visibility default field
* Fixed powerstep disable lockout
* Fixed quote type for powerstep embedcode
* Removed limit from search page template embed, on favor of content
* Added santinitation for empty attributes

= 4.0.1 - 2022-11-28
* Changed sorting for pagination to work better with overwritten Product ids from alternate import methods.
* Added featured image import to custom page types.
* Fixed escaped quote in facet titles string.

= 4.0.0 - 2022-11-17
* Corrected Syntax for WP standards.
* Added security sanitation to all request data.
* Updated methods for realtime updates, to none locking methods for post meta.
* Deferred template JS load time.
* Changed allowed methods for all external requests.

= 3.8.3 - 2022-10-14
* Added header scripts field to admin
* Added manual translations to powerstep
* Improved key verification for API

= 3.8.2 - 2022-10-14 =
* Added default null values to save_product function

= 3.8.1 - 2022-10-10 =
* Fixed realtime updates object eval

= 3.8.0 - 2022-05-10 =
* Added message to empty search pages.
* Added settings check before syncing variant stocks.
* Changed base domain determination to work with CLI updates.
* Added hooks for syncing new products, without them being edited.
* Added hooks for catching products created through WooCommerce CSV / Import feature in real time updates.
* Fixed bug where prices would be cast to strings when only updating that field.
* Removed css class from input widget, which could generate console errors.
* Added Get / Set config support for all settings fields in Admin.

= 3.7.1 - 2022-07-25 =
* Changed how On Sale attribute is calculated
* Changed default classes and title on powerstep to WP utility classes
* Changed variant exclusion based on missing price
* Added fallback for sales tracking parent id for grouped products
* Changed stock filter to happen in bulk query instead of per product
* Added fallback for getting page url


= 3.7.0 - 2022-06-15 =
* Added Exclude checkbox for all recs, and added filter to span generation.
* Added default lists with variable product data, similar to shopify.
* Added selector for image size to sync in admin.
* Added checkbox to enabled or disable pages in search page embedcode.
* Added sync method for subscriber type users, without historic orders.
* Added checkbox injection with custom message for log/subscriber from checkout page. This is a feature parity thing, with mailchimp/woocommerce mail module.
* Added shortcodes to allow printing, product, category and cart ids in templating engines like elementor.
* Fixed position of cart page slider, by changing hook position. Before it would get removed if the client used ajax cart, and edited their cart contents. Placement also overflowed checkout button on mobile.
* Added better tax handling on order tracking page, to allow us to work seamlessly with geoip modules.
* Fixed page type set wrong for All option on search page.
* Added sanity check for attribute keys with dashes, to make them compatible with our feed structure.
* Improved admin panel UX, with some colors, log and spacing, as well as a floating save button to avoid having to scroll our extensive settings to the bottom to save.
* Added New way to pull and register subscribers
* Fixed search page page type bug
* Change to variant handling to include more complete information.
* Change to sales tracking to account for geoip tax rates.

= 3.6.1 - 2022-01-20 =
* Fixed wrong product stock bug.

= 3.6.0 - 2021-11-29 =
* Added categories and pages to search page.
* Added facets design.
* Added group and configurable product data handling.

= 3.5.4 - 2021-08-31 =
* Improved Collect Basket functionality.
* Fixed page endpoint.

= 3.5.3 - 2021-03-19 =
* Fixed Collect Basket issue for some edge cases.

= 3.5.2 - 2021-03-02 =
* Fixed Page Powerstep won't show.

= 3.5.1 - 2021-02-22 =
* Fixed Facet settings bug for som WP versions.

= 3.5.0 - 2021-02-09 =
* Fixed Page Sync Bug.

= 3.4.0 - 2021-01-05 =
* Support For Clerk Basket Tracking.

= 3.3.6 - 2020-12-03 =
* Fixed facets settings edge case bug.

= 3.3.5 - 2020-11-05 =
* Added created_at as default product attribute.

= 3.3.4 - 2020-09-30 =
* Fixed content comma list bug.
* Fixed exit intent content bug.
* Added default recommendation contents.

= 3.3.3 - 2020-09-03 =
* Fixed some more default settings errors.

= 3.3.2 - 2020-08-27 =
* Fixed some default settings errors.

= 3.3.1 - 2020-07-01 =
* Better handling of facet search settings.

= 3.3.0 - 2020-06-25 =
* Fixed Facet Search bug.
* Fixed Facet Search position bug.
* Fixed pagination for products sync endpoint.
* Removed log to file only supports log to my.clerk.io.


= 3.2.0 - 2020-05-11 =
* Using HTTP API insted of CURL
* Removed Dashboard iframes
* Include WP jquery Libraries.
* Removed De / Activate Debug Mode button.
* Fixed Powerstep and extended the feture to work on all shop not use ajax add to cart also.
* Fixed Faced Search Bug.
* Added all_images for product export so all images are at desposible in Clerk.io


= 3.1.0 - 2020-03-26 =
* Removed Header info from wrong places.
* Rebuild the stock sorting system.
* Fixed SEO Yost no name product attribute.
* Removed all warnings caused by the plug-in.
* Added Customer API Endpoint.
* Updated Changelog.

= 3.0.0 - 2020-03-02 =
* Added stock calculation for variable products.
* Improved/Extended the additional fields system.
* Added Clerk friendly attributes converter.
* Added much better/easy way of implement Clerk Live Search and Clerk Search Page.

= 2.2.7 - 2020-02-18 =
* Fixed Facet Settings Bug.

= 2.2.6 - 2020-02-14 =
* Fixed powerstep multi slider bug.
* Fixed scripts enqueued warning bug.

= 2.2.5 - 2020-02-10 =
* Added facet on search page.
* Added multi content all over.

= 2.2.4 - 2020-01-13 =
* Added button for disabling and enabling wordpress debug mode.

= 2.2.3 - 2019-12-19 =
* Added empty clerk_log.log in release by default.

= 2.2.1 - 2019-12-09 =
* Fixed comma bug in powerstep recomendations.
* Added Stock as default product attribute
* Removed the searchfield id generator, now it's just clerk_searchfield
* Support for spaces in comma seperated lists.
* Added out of stock support for shop's using WooCommerce build in stock tracking.
* Added WPBakery Page Builder in the warning system.

= 2.2.0 - 2019-12-03 =
* Removed unused clerk_admin.js file.
* Added instant search dropdown position setting.
* Added notification warning system that detects if any plugin's is installed that interfair with our extension
  and giving the customer a warning, small description and linking to a support article that can help them to fix the problem.
* Fixed setting dropdown number bug.
* Added custom User-Agent header on all API call to clerk.io.
* Added Version API endpoint tells Clerk.io what system version, Clerk extension version and PHP version the shop is using.
* Added Plugin API endpoint tells Clerk.io what extensions that is installed on the shop, so our customer service have the much info as possible for helping our customers.
* Fixed Pages endpoint bug, prevent the shop to send pages with data that not is up to Clerk.io requerments.
* Removed Load more button from search page it's now controlled from the design's in my.clerk.io.
* Fixed exclude bug on multiple recommendations in powerstep pop-up and page.

= 2.1.0 - 2019-11-19 =
* Support for Pages.
* Language support for clerk.js v2.
* Added settings for numbers of pages, categories and products to show in live search.

= 2.0.1 - 2019-10-16 =
* Added debug guide to plugin backend.
* Change lang filenames for translation plugin support.

= 2.0.0 - 2019-10-07 =
* ###THIS UPDATE REQUIRE NEW DESIGN TEMPLATES IN MY.CLERK.IO ###
* Deprecated Clerk.js v1 and added Clerk.js v2.

= 1.7.1 - 2019-10-04 =
* Added support for additional fields in real time updates

= 1.7.0 - 2019-09-16 =
* Toggle real time updates.
* Toggle out of stock products.
* Order range support for clerk importer.
* Fixed issue with real time updates.
* Better default image size (300x300).
* Added plugin sersion number in settings.

= 1.6.0 - 2019-08-26 =
* Support for Clerk Logger.
* Load more script added.
* Support for load more script.

= 1.5.10 - 2019-03-27 =
* Remove duplicated filter

= 1.5.9 - 2019-03-26 =
* Add more filters

= 1.5.8 - 2019-03-06 =
* Fix critical error with pagination logic

= 1.5.7 - 2019-03-04 =
* Fix pagination error
* Set collect emails to on by default

= 1.5.6 - 2018-11-30 =
* Fix syntax error in call to product/remove

= 1.5.5 - 2018-11-21 =
* Remove legacy hook
* Fix bug with additional fields

= 1.5.4 - 2018-11-01 =
* Re add option to disable emails in order sync
* Fix issue where only refunded orders would get synced

= 1.5.3 - 2018-10-17 =
* Add spanish and italian translations
* Add better support for variable products
* Change JSON error format

= 1.5.2 - 2018-09-21 =
* Add option to toggle content in categories, products and cart
* Update danish localization
* Allow comma separated list of product page contents

= 1.5.1 - 2018-09-03 =
* Reformat code to follow WP styleguide
* Add localization

= 1.5.0 - 2018-07-06 =
* Add option to toggle content in categories, products and cart
* Add widget to insert content

= 1.4.3 - 2018-03-23 =
* Add filter to modify clerk order and category api response
* Add sanity check for order api

= 1.4.2 - 2018-03-22 =
* Add product to filter

= 1.4.1 - 2018-03-22 =
* Add filter to modify clerk product api response

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
