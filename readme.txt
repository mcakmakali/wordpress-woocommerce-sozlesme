=== Sözleşme Yönetimi ===
Contributors: mcakmakali
Donate link: https://mehmetalicakmak.me
Tags: woocommerce, checkout, contract, agreement, pdf
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires Plugins: woocommerce

Adds contracts (distance selling agreement, pre-information form, privacy notice, etc.) that customers must accept on the WooCommerce checkout page.

== Description ==

**Sözleşme Yönetimi** (Turkish for "Agreement Management") lets you add one or more agreements to the WooCommerce checkout page that customers accept with a checkbox before placing an order.

= Features =

* Create an unlimited number of agreements from the admin screen; each one can be marked "required" or "optional".
* Agreements are listed as checkboxes right above the payment method box on checkout; clicking the title opens a popup with the full text.
* Required agreements must be accepted before the order can be placed (validated both in the browser and on the server).
* Merge tags available inside the agreement text: order number, order date, customer name/email/phone, billing/shipping address, payment method, a table of cart items with quantity/tax/totals, cart total, site name, current date.
* The accepted agreements are stored on the order (with the merge tags already resolved to the real order data) as a permanent record.
* When an order is successfully paid, a PDF of the accepted agreements is automatically attached to the order confirmation email sent to the customer.
* Optional "Customer Type" (Individual/Business) checkout fields: company name, tax office, tax number, or national ID can be collected and used as merge tags.
* Agreements are not publicly viewable as single pages and are excluded from search results and the XML sitemap.

= Requirements =

* WooCommerce (must be active)
* PHP 8.1 or later

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/sozlesme-yonetimi-for-woocommerce` directory, or install the plugin through the WordPress "Add Plugin" screen directly.
2. Activate the plugin through the "Plugins" screen in WordPress.
3. Go to WooCommerce → Sözleşmeler to add agreements, and WooCommerce → Sözleşme Ayarları to toggle the optional Customer Type fields.

== Frequently Asked Questions ==

= What checkout flows does this plugin work with? =

It is designed to work with both the classic WooCommerce checkout template and customized checkout plugins such as CheckoutWC.

= Do I need to install a separate PDF library? =

No, the required PDF library (dompdf) is bundled with the plugin.

= Are agreements viewable as public pages? =

No, agreements are only shown on checkout and in the admin screen; they cannot be viewed as a single page and are excluded from the sitemap.

== Screenshots ==

1. Agreement checkboxes listed above the payment method box on the checkout page.
2. The popup that displays the full agreement text.
3. The admin screen for adding an agreement, with merge tag buttons.

== Changelog ==

= 1.0.0 =
* Initial release.
