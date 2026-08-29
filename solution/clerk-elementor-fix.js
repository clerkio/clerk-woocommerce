/**
 * Clerk.io Elementor Compatibility Fix
 * 
 * This script fixes the issue with Clerk.io sliders not working in Elementor
 * when using embed code with product/category data.
 * 
 * The problem occurs because Elementor doesn't properly process PHP shortcodes
 * within HTML attributes, particularly in custom widgets or embed code blocks.
 * 
 * This script:
 * 1. Waits for the DOM to be fully loaded
 * 2. Finds all Clerk elements with PHP shortcodes in data attributes
 * 3. Evaluates these shortcodes via AJAX
 * 4. Updates the data attributes with the actual values
 * 5. Re-initializes Clerk.js to process the updated elements
 */
(function() {
    // Wait for DOM to be fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Find all Clerk elements that might contain PHP shortcodes
        const clerkElements = document.querySelectorAll('.clerk, [data-template]');
        
        if (clerkElements.length === 0) {
            return;
        }
        
        // Process each element
        clerkElements.forEach(function(element) {
            processClerkElement(element);
        });
        
        // Re-initialize Clerk.js after a short delay to ensure all elements are processed
        setTimeout(function() {
            if (typeof window.Clerk === 'function') {
                // Re-initialize all clerk content
                Clerk('content', '.clerk');
            }
        }, 500);
    });
    
    /**
     * Process a Clerk element to evaluate PHP shortcodes in data attributes
     */
    function processClerkElement(element) {
        // Check for PHP shortcodes in data-products attribute
        const productsAttr = element.getAttribute('data-products');
        if (productsAttr && productsAttr.includes('<?php')) {
            evaluateShortcode(productsAttr, function(result) {
                if (result) {
                    element.setAttribute('data-products', result);
                }
            });
        }
        
        // Check for PHP shortcodes in data-categories attribute
        const categoriesAttr = element.getAttribute('data-categories');
        if (categoriesAttr && categoriesAttr.includes('<?php')) {
            evaluateShortcode(categoriesAttr, function(result) {
                if (result) {
                    element.setAttribute('data-categories', result);
                }
            });
        }
        
        // Also check for shortcodes in the format [clerk_product_id] or [clerk_category_id]
        const allAttributes = element.attributes;
        for (let i = 0; i < allAttributes.length; i++) {
            const attr = allAttributes[i];
            if (attr.value.includes('[clerk_product_id]') || 
                attr.value.includes('[clerk_category_id]') ||
                attr.value.includes('[clerk_cart_ids]')) {
                
                evaluateShortcode(attr.value, function(result) {
                    if (result) {
                        element.setAttribute(attr.name, result);
                    }
                });
            }
        }
    }
    
    /**
     * Evaluate a PHP shortcode via AJAX
     */
    function evaluateShortcode(shortcode, callback) {
        // Create AJAX request to evaluate the shortcode
        const xhr = new XMLHttpRequest();
        xhr.open('POST', ajaxurl || '/wp-admin/admin-ajax.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                callback(xhr.responseText);
            }
        };
        
        // Send the shortcode to be evaluated
        xhr.send('action=clerk_evaluate_shortcode&shortcode=' + encodeURIComponent(shortcode));
    }
})();

