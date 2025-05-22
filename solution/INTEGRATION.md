# Clerk.io Elementor Integration Guide

This guide explains how to integrate Clerk.io sliders with Elementor pages, ensuring that all slider types work correctly, including those that require product or category data.

## Method 1: Using Shortcodes in Elementor HTML Widget

The most reliable way to add Clerk.io sliders to Elementor pages is using the HTML widget with shortcodes:

1. Add an HTML widget to your Elementor page
2. Insert the following code:

```html
<span class="clerk" 
      data-template="@popular-products" 
      data-products="[clerk_product_id]">
</span>
```

The `[clerk_product_id]` shortcode will be automatically replaced with the current product ID.

For category pages, use:

```html
<span class="clerk" 
      data-template="@category-products" 
      data-categories="[clerk_category_id]">
</span>
```

## Method 2: Using Embed Code

If you're copying embed code from my.clerk.io, make sure to replace any PHP code with the appropriate shortcodes:

Instead of:
```html
<span class="clerk" 
      data-template="@popular-products" 
      data-products="<?php echo $product->get_id(); ?>">
</span>
```

Use:
```html
<span class="clerk" 
      data-template="@popular-products" 
      data-products="[clerk_product_id]">
</span>
```

## Method 3: Using the Clerk.io Widget (Coming Soon)

We're working on a dedicated Elementor widget for Clerk.io that will make integration even easier. This will allow you to:

1. Add a Clerk.io widget directly to your Elementor page
2. Select the template from a dropdown
3. Configure all options through the Elementor interface
4. Product and category data will be automatically handled

## Troubleshooting

If your sliders still don't work after implementing these solutions:

1. **Check Browser Console**: Look for any JavaScript errors related to Clerk.io
2. **Verify Shortcodes**: Make sure you're using the correct shortcodes (`[clerk_product_id]`, `[clerk_category_id]`, or `[clerk_cart_ids]`)
3. **Clear Cache**: Clear your Elementor cache and any caching plugins
4. **Check Template ID**: Verify that the template ID in the `data-template` attribute is correct
5. **Inspect Element**: Use browser developer tools to check if the data attributes have been properly populated with actual values

## Support

If you encounter any issues with this integration, please contact Clerk.io support at support@clerk.io or through the chat on your my.clerk.io dashboard.

