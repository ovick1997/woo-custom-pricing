=== WooCommerce Custom Pricing ===
Contributors: shorovabedin
Tags: woocommerce, custom pricing, customer pricing, ecommerce, price management
Requires at least: 5.0
Tested up to: 6.5
Stable tag: 1.0.3
Requires PHP: 7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 3.0
WC tested up to: 8.0

A WooCommerce extension to set custom product prices via rules or individually per customer.

== Description ==

WooCommerce Custom Pricing enables personalized pricing in WooCommerce. Create pricing rules with custom prices for multiple products and assign them to customers individually or in bulk. Key features include:

- "Pricing Rules" tab to create and manage rules with multiple product prices.
- "Customer List" tab with AJAX search, pagination (15 per page), rule assignment, and status indicators.
- "Bulk Customer" tab to assign multiple customers to a rule at once.
- "Customer Details" tab for individual custom prices (overrides rules).
- Persistent status indicators (checkmark) for saved prices and rule assignments.

Ideal for businesses offering group discounts or personalized pricing.

== Installation ==

1. Upload the `woo-custom-pricing` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Ensure WooCommerce is installed and active (version 3.0+ required).
4. Navigate to `WooCommerce > Custom Pricing` to manage prices and rules.

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =
Yes, it requires WooCommerce 3.0 or higher.

= How do I create and assign rules? =
Create rules in "Pricing Rules", then assign them individually in "Customer List" or in bulk via "Bulk Customer".

= What’s the status indicator? =
A green checkmark appears in "Customer List" when a rule is assigned and in "Customer Details" or "Pricing Rules" when a price is saved.

= How does the search work? =
"Customer List" has an AJAX search box, showing 15 customers per page with pagination.

== Screenshots ==

1. **Pricing Rules Tab**: Create rules with multiple product prices.
2. **Customer List Tab**: Search, assign rules, and see status.
3. **Bulk Customer Tab**: Assign multiple customers to a rule.
4. **Customer Details Tab**: Set individual custom prices.

== Changelog ==

= 1.0.3 =
* Fixed "Add New Rule" by simplifying JavaScript redirect and ensuring PHP handles new rule IDs correctly.

= 1.0.2 =
* Fixed "Add Rule" logic, updated "Customer List" with status column.

= 1.0.1 =
* Added "Bulk Customer" tab and rule assignment via dropdown in "Customer List".

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.3 =
Resolved persistent issue with "Add New Rule" functionality.

== License ==
Licensed under GPLv2 or later.

== Additional Information ==

Visit [https://github.com/ovick1997/woo-custom-pricing](https://github.com/ovick1997/woo-custom-pricing) for more details.
Developed by Md Shorov Abedin ([shorovabedin.com](https://shorovabedin.com)).