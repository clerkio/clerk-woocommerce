# Clerk.io for WooCommerce

The official [Clerk.io](https://clerk.io) plugin for WooCommerce. It connects your store to Clerk.io's AI platform, giving you personalized search, product recommendations, and visitor tracking out of the box.

**Version:** 4.2.2 · **PHP:** 7.4+ · **WordPress:** 5.0+ · **WooCommerce:** 4.4+

---

## What It Does

This plugin handles three things:

1. **Feeds your product data to Clerk.io** — Registers WordPress REST API endpoints (`/wp-json/clerk/product`, `/clerk/order`, etc.) that the Clerk.io platform calls to sync your catalog. Also pushes real-time updates when products are saved or deleted.

2. **Renders Clerk.io on the frontend** — Injects [Clerk.js](https://docs.clerk.io/docs/clerkjs-quick-start) via `wp_footer`, which handles search results, live search dropdowns, recommendation sliders, exit-intent popups, and tracking.

3. **Tracks visitor behavior** — Logs page views, clicks, cart contents, and completed orders so Clerk.io's AI can learn and improve results.

### Feature Overview

| Feature | What it does |
|---------|-------------|
| **Search** | Replaces WooCommerce's native search with a Clerk.io-powered search page via shortcode `[clerk-search]`. Redirects search forms to use the Clerk search page. |
| **Live Search** | Type-ahead dropdown on the search field. Configurable selector, categories, suggestions count, dropdown position. |
| **Recommendations** | Sliders on product, category, cart pages. Replaces WooCommerce's native related products template via `wc_get_template` filter. |
| **Powerstep** | After add-to-cart, shows a page or popup with recommendations. Intercepts `woocommerce_add_to_cart_redirect`. |
| **Exit Intent** | Overlay triggered when the visitor moves to leave the page. |
| **Sales Tracking** | `<span>` on the thank-you page that logs the order to Clerk.io. |
| **Basket Tracking** | Client-side: intercepts AJAX cart requests and syncs cart to Clerk.io. Server-side: syncs cart to Clerk.io on cart changes for logged-in customers. |
| **Real-Time Sync** | Hooks into `save_post` (priority 1000) and `before_delete_post` to push product/page changes to the Clerk.io API. |
| **WPML / Polylang** | Full multi-language support with per-language settings stored as `clerk_options_{lang}`. |

---

## Installation

**Via WordPress Admin:**

1. Download the latest `.zip` from [GitHub Releases](https://github.com/clerkio/clerk-woocommerce/releases).
2. Go to **Plugins > Add New > Upload Plugin**, upload the `.zip`, and activate.

After install, a **Clerk** menu appears in the admin sidebar. Enter your API keys from [my.clerk.io](https://my.clerk.io).

Full setup guide: [help.clerk.io/integrations/woocommerce/get-started](https://help.clerk.io/integrations/woocommerce/get-started/)

---

## How It Works Under the Hood

### Data Feed Endpoints

All under `/wp-json/clerk/`. Authentication uses JWT (`X-Clerk-Authorization: Bearer <token>`) or legacy public/private key.

| Endpoint | What it returns |
|----------|----------------|
| `/clerk/product` | Products with prices (incl/excl tax), stock, images, categories, SKU, tags, ratings, variants (for variable products), additional fields. Resolves custom fields via WC attributes → post_meta → ACF → taxonomy terms. |
| `/clerk/order` | Completed orders with line items, quantities, prices, email, customer ID. Supports Aelia currency conversion. |
| `/clerk/category` | Categories including `product_cat`, `product_brand`, `pwb-brand`, `berocket_brand`. |
| `/clerk/customer` | Customers and newsletter subscribers with configurable extra fields. |
| `/clerk/page` | Pages and posts (configurable post types) with additional fields. |
| `/clerk/version` | WordPress version, plugin version, PHP version. |

All paginated endpoints accept `page`, `limit`, `orderby`, `order` parameters.

There are also diagnostic and configuration management endpoints available for Clerk.io platform integration.

### Filters for modifying feed data

| Filter | Where |
|--------|-------|
| `clerk_product_array` | Modify product data during full sync |
| `clerk_product_sync_array` | Modify product data during real-time sync |
| `clerk_category_array` | Modify category data |
| `clerk_order_array` | Modify order data |
| `clerk_customer_array` | Modify customer data |
| `clerk_get_posts` | Modify pages data |
| `clerk_tracking_order_array` | Modify order tracking data on thank-you page |

---

## Structure

```
├── class-clerk.php                     ← Main plugin bootstrap
├── readme.txt                          ← WordPress plugin readme (changelog)
├── wpml-config.xml                     ← WPML string translation config
│
├── includes/
│   ├── class-clerk-admin-settings.php  ← Admin settings page & all config fields
│   ├── class-clerk-rest-api.php        ← REST API endpoints for data sync
│   ├── class-clerk-search.php          ← [clerk-search] shortcode, search page rendering
│   ├── class-clerk-content.php         ← Recommendation sliders (category/cart/product pages)
│   ├── class-clerk-visitor-tracking.php ← Clerk.js injection, live search, basket tracking JS, email logging
│   ├── class-clerk-sales-tracking.php  ← Order tracking on thank-you page
│   ├── class-clerk-basket.php          ← Server-side basket tracking (POST to Clerk API)
│   ├── class-clerk-powerstep.php       ← Powerstep page/popup logic
│   ├── class-clerk-exit-intent.php     ← Exit intent span output
│   ├── class-clerk-realtime-updates.php ← Real-time product/page sync on save/delete
│   ├── class-clerk-api.php             ← Clerk.io API client (cURL)
│   ├── class-clerk-logger.php          ← Logging
│   ├── clerk-multi-lang-helpers.php    ← WPML & Polylang helper functions
│   ├── clerk-legacy-helpers.php        ← WC version compatibility helpers
│   ├── clerk-template-functions.php    ← Template locator (theme overrides: yourtheme/clerk/)
│   └── widgets/
│       ├── class-clerk-widget-search.php   ← Search widget
│       └── class-clerk-widget-content.php  ← Content/recommendations widget
│
├── templates/
│   ├── clerk-powerstep.php             ← Powerstep page template
│   ├── clerk-powerstep-popup.php       ← Powerstep popup template
│   ├── clerk-related-products.php      ← Replaces WooCommerce's related products
│   └── clerk-searchform.php            ← Search form template
│
├── assets/
│   ├── css/admin.css, powerstep.css
│   ├── js/admin.js, powerstep.js
│   └── img/clerk.png
│
└── i18n/languages/                     ← Translations (da_DK, es_ES, it_IT, sv_SE)
```

---

## Customizing & Extending

If you need to customize the plugin, here are the parts to be careful with.

**WooCommerce related products override.** When product recommendations are enabled, the plugin filters `wc_get_template` and replaces `single-product/related.php` with its own `clerk-related-products.php` template. This completely removes WooCommerce's native related products section. If you need both, you'll need to disable the Clerk product recommendations and add Clerk spans manually.

**Search form rewriting.** When search is enabled, `class-clerk-visitor-tracking.php` rewrites search forms on the page to point to Clerk's search endpoint. If your theme has multiple search forms, be aware they will all be rewritten.

**Basket tracking and AJAX interception.** When basket tracking is enabled, the plugin intercepts AJAX requests related to cart changes (`add_to_cart`, `remove_from_cart`, `get_refreshed_fragments`) to keep Clerk.io in sync with the cart. If you see unexpected AJAX behavior, check if `collect_baskets` is enabled. Server-side basket tracking (`class-clerk-basket.php`) also sends cart data to the Clerk.io API on cart changes for logged-in customers.

**Real-time updates via `save_post`.** Real-time sync hooks into `save_post` to push product and page changes to the Clerk.io API. If you're doing bulk imports, consider temporarily disabling real-time updates in the Clerk settings.

**Template overrides.** All templates can be overridden in your theme at `yourtheme/clerk/` (e.g. `yourtheme/clerk/clerk-powerstep.php`). The plugin uses `clerk_locate_template()` which checks the theme directory first.

---

## Troubleshooting

- **Plugin not appearing:** Make sure WooCommerce is active — the Clerk plugin requires it.
- **Search not working:** Check API keys in the Clerk admin page. Open browser console and look for Clerk.js errors.
- **Products missing from feed:** Visit `/wp-json/clerk/product?limit=1` (with auth) to check the feed. Ensure products are published and in stock.
- **Real-time sync not working:** Check `realtime_updates` is enabled. Logging sends to `api.clerk.io` — there's no local log file.
- **WPML/Polylang:** Settings are stored per-language as `clerk_options_{lang}`. Make sure each language has its own API keys configured.

---

## Links

- [Setup Guide](https://help.clerk.io/integrations/woocommerce/get-started/)
- [Plugin Configuration](https://help.clerk.io/integrations/woocommerce/plugin/)
- [FAQ](https://help.clerk.io/integrations/woocommerce/faq/)
- [Clerk.js Docs](https://docs.clerk.io/docs/clerkjs-quick-start)
- [Design Template Language](https://docs.clerk.io/docs/clerkjs-template-language)
- [API Reference](https://docs.clerk.io/reference)
- [Dashboard](https://my.clerk.io)

---

## Contributing

1. Fork and branch from `master`
2. Test against a WordPress + WooCommerce instance
3. Open a PR with a clear description

Issues & feature requests: [github.com/clerkio/clerk-woocommerce/issues](https://github.com/clerkio/clerk-woocommerce/issues)

---

## Support

- [help.clerk.io](https://help.clerk.io) · [support@clerk.io](mailto:support@clerk.io) · [status.clerk.io](https://status.clerk.io)
