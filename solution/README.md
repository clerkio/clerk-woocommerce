# Clerk.io Elementor Compatibility Fix

This solution addresses the issue with Clerk.io sliders not working in Elementor when using embed code with product/category data.

## Problem

When using Clerk.io sliders in Elementor pages, sliders that require product or category data (via `data-products` or `data-categories` attributes) do not work properly. This happens because:

1. Elementor doesn't properly process PHP shortcodes within HTML attributes
2. The shortcodes in the data attributes are not evaluated before Clerk.js initializes
3. This results in the slider not having the necessary product/category data to function

## Solution

The solution consists of two parts:

### 1. JavaScript Fix (`clerk-elementor-fix.js`)

This script:
- Waits for the DOM to be fully loaded
- Finds all Clerk elements with PHP shortcodes in data attributes
- Evaluates these shortcodes via AJAX
- Updates the data attributes with the actual values
- Re-initializes Clerk.js to process the updated elements

### 2. PHP Integration (`clerk-elementor-compatibility.php`)

This PHP file:
- Adds an AJAX handler for evaluating shortcodes
- Enqueues the compatibility script when Elementor is active
- Adds filters to process shortcodes in Elementor widgets
- Provides helper methods for getting product and category IDs

## Implementation

To implement this solution:

1. Add the `clerk-elementor-compatibility.php` file to the `includes` directory of the Clerk WooCommerce plugin
2. Add the `clerk-elementor-fix.js` file to the `assets/js` directory of the plugin
3. Include the compatibility file in the main plugin file:

```php
// In the main plugin file (clerk.php)
include_once 'includes/clerk-elementor-compatibility.php';
```

## How It Works

1. When a page is loaded with Elementor, the compatibility script is enqueued
2. The script finds all Clerk elements with shortcodes in their data attributes
3. It sends these shortcodes to the server via AJAX to be evaluated
4. The server evaluates the shortcodes and returns the actual values
5. The script updates the data attributes with these values
6. Clerk.js is re-initialized to process the updated elements

This ensures that the sliders have the correct product/category data before they are initialized, allowing them to function properly in Elementor pages.

## Benefits

- Works with both embed code and plugin-inserted sliders
- No need to modify existing templates or shortcodes
- Compatible with all Elementor widgets
- Minimal performance impact
- No changes required to Clerk.js core functionality

