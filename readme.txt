=== WooCommerce Custom Pricing ===
Contributors: Shorov
Tags: woocommerce, custom pricing, customer pricing, ecommerce, price management
Requires at least: 5.0
Tested up to: 6.5
Stable tag: 1.0.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 3.0
WC tested up to: 8.0

A WooCommerce extension to set custom product prices for individual customers with a user-friendly tabbed interface.

== Description ==

WooCommerce Custom Pricing allows store owners to assign custom prices to specific products for individual customers. Featuring an intuitive admin interface, this plugin makes it easy to manage personalized pricing in your WooCommerce store. Key features include:

- A "Customer List" tab with AJAX-powered search and pagination (15 customers per page) to select customers.
- A "Customer Details" tab to set multiple custom prices per customer.
- Persistent status indicators (checkmark) for saved prices.
- AJAX-powered price updates for a seamless experience.
- Fully integrated with WooCommerce products and user roles (customers and subscribers).

Perfect for businesses offering personalized pricing, discounts, or special rates to specific clients. The AJAX search ensures you can quickly find any customer, even in a large database, while pagination keeps the list manageable.

== Installation ==

1. Upload the `woo-custom-pricing` folder to the `/wp-content/plugins/` directory, or install it directly via the WordPress admin plugin installer.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Ensure WooCommerce is installed and active (version 3.0 or higher required).
4. Navigate to `WooCommerce > Custom Pricing` in the admin menu to start managing custom prices.

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =
Yes, WooCommerce Custom Pricing is an extension for WooCommerce and requires it to be installed and active (version 3.0 or higher).

= Can I set custom prices for multiple products per customer? =
Yes, in the "Customer Details" tab, you can add and manage multiple product prices for each customer with an easy-to-use interface.

= How do I know if a price has been saved? =
A green checkmark appears in the "Status" column next to each product after saving its custom price and remains visible until the price is removed.

= How does the search work with many customers? =
The "Customer List" tab features an AJAX search box that queries all customers in real-time, displaying up to 15 per page with pagination for easy navigation.

= Can I use this plugin with other WooCommerce extensions? =
Yes, as long as the extensions don’t conflict with WooCommerce’s pricing hooks (`woocommerce_product_get_price` and `woocommerce_product_get_regular_price`), it should work seamlessly.

== Screenshots ==

1. **Customer List Tab**: Browse customers with AJAX search and pagination (15 per page) for easy navigation.
2. **Customer Details Tab**: Set custom prices for multiple products with persistent status indicators.

== Changelog ==

= 1.0.0 =
* Initial release with customer list, details tab, and custom pricing functionality.
* Added AJAX-powered search box and pagination (15 customers per page) to the "Customer List" tab.
* Included persistent status checkmarks for saved prices in the "Customer Details" tab.

== Upgrade Notice ==

= 1.0.0 =
This is the first version of the plugin. No upgrades available yet.

== License ==
This plugin is licensed under the GPLv2 or later. See the License URI for more details.

== Additional Information ==

For more details, support, or to contribute, visit the plugin's GitHub repository: [https://github.com/ovick1997/woo-custom-pricing](https://github.com/ovick1997/woo-custom-pricing).

Developed by Md Shorov Abedin. Visit [shorovabedin.com](https://shorovabedin.com) for more information.