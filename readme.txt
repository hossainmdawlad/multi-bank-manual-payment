=== Multiple Bank Payment Method ===
Contributors: hossainawlad
Tags: woocommerce, payment gateway, multiple bank transfer, manual payment, bank payment gateway
Requires at least: 6.0
Tested up to: 6.9
Stable tag: 2.2.0
Requires PHP: 7.4
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

WooCommerce gateway that allows customers to choose from multiple bank accounts and upload a payment receipt (screenshot/image) during checkout for manual verification.

== Description ==
This plugin adds a secure **Multiple Bank Payment** option to your WooCommerce store. Customers can choose their preferred bank from a list of configured bank accounts, view the full account details (number, holder name, branch, routing, SWIFT code, address), and **upload a screenshot or photo of their bank payment receipt** directly on the checkout page. The uploaded image is securely stored and displayed in the order details for easy manual verification by the store admin.

**Perfect for businesses that receive payments through multiple bank accounts.**

**Features:**
* Configure **unlimited bank accounts** — add or remove banks from the settings page at any time.
* Each bank account supports **Account Name, Number, Holder, Branch, Routing, SWIFT Code, and Address**.
* Customers select their bank from a clean dropdown and see the correct account details instantly.
* Customers upload a **payment receipt image** (PNG/JPG) during checkout.
* Automatic file validation (max 5MB, only images allowed).
* Uploaded receipt is visible in the **WooCommerce order details** in the admin dashboard.
* Selected bank details are recorded on each order for accurate bookkeeping.
* Automatic migration — existing single-bank installations are upgraded seamlessly with no data loss.
* Compatible with WooCommerce emails, order statuses, and cart flow.
* No sensitive API keys needed — secure and lightweight.

== Installation ==
1. Upload the `multiple-bank-payment-method` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **WooCommerce → Settings → Payments**.
4. Find **"Multiple Bank Payment Method"** and click **Manage**.
5. Enable the gateway, add one or more bank accounts, and save changes.
6. Customers will now see this option at checkout, select their bank, and upload their payment proof.

== Frequently Asked Questions ==

= How do I add multiple bank accounts? =
Go to **WooCommerce → Settings → Payments → Multiple Bank Payment Method → Manage**. You will see a "Bank Accounts" section with an **+ Add Bank** button. Fill in the bank details for each account and click **Save changes**.

= Can I change the order of the bank list? =
Currently banks are shown in the order they are listed. You can remove and re-add them in your preferred order.

= What file types are allowed? =
Only **JPG, JPEG, and PNG** images are accepted. Maximum file size: **5MB**.

= Where can I see the uploaded receipt? =
Go to **WooCommerce → Orders → [Order]**. The receipt image appears under the billing address section, along with the bank account the customer selected.

= Does this plugin auto-verify payments? =
No. Payments are marked as **"On Hold"** until you manually verify the uploaded receipt.

= Will my existing single-bank data be lost on update? =
No. The plugin automatically migrates your existing bank account settings to the new multi-bank format the first time the settings page is loaded after the update.

= Is this compatible with other payment methods? =
Yes. It works alongside PayPal, Stripe, Cash on Delivery, and other gateways.

== Screenshots ==
1. Checkout page — bank selector dropdown and receipt upload.
2. Admin order details — selected bank info + receipt image.
3. Settings page — multi-bank repeater management UI.

== Changelog ==

|= 2.3.0 =
* **New**: SWIFT Code field added to bank accounts — display SWIFT/BIC on checkout and admin order details.
* **New**: Bank Address field added to bank accounts — customers and admins can see the bank's address.
* **Improved**: `get_payment_method_data()` now includes `swift_code` and `bank_address` for block-based checkout.
* **Improved**: Auto-migration populates `swift_code` and `bank_address` as empty for legacy single-bank upgrades.

||= 2.2.0 =
* **Refactored**: Renamed developer prefix from "RSLDV" to "ALD" throughout the plugin codebase.
* **New**: Bank logo display — add logo URLs to bank accounts and display them on checkout.
* **New**: Bank logo dynamically updates when customers select different banks from the dropdown.
* **Improved**: Complete CSS class prefix update from `rsldv-` to `ald-` for better namespace isolation.
* **Improved**: Block-based checkout compatibility enhanced with bank logo support in payment method data.
* **Security**: Strengthened file validation and nonce verification.

|= 1.2.0 =
* **New**: Multi-bank support — add unlimited bank accounts from the settings page. Customers select their preferred bank at checkout.
* **New**: Automatic migration from legacy single-bank settings — existing data is preserved with zero downtime.
* **New**: Selected bank name, account number, holder, branch, and routing number are now recorded on every order.
* **New**: Admin order details now show a dedicated "Paid via Bank" table above the receipt image.
* **Improved**: Checkout JS now reads bank data from `data-*` attributes on dropdown options — cleaner, faster, no server round-trips.
* **Improved**: Gateway ID changed to `multiple_bank_payment` to avoid conflicts with the legacy plugin slug.
* **Improved**: File input ID unified to `bbpm-payment-receipt` across all templates and localisation calls.
* **Fix**: No stale single-bank property references remain — all code paths fully redirected to the `bank_accounts` array.
* **Fix**: Admin repeater "Save" now correctly persists bank data — input `name` attributes now use `$this->get_field_name()` so WooCommerce's save handler correctly maps POST data back to the `bank_accounts` option.
* **Security**: Nonce verification now halts execution on failure — checkout cannot proceed without a valid nonce.
* **Security**: Server-side MIME type and file-size validation added for payment receipt uploads — images only (JPEG/PNG), max 5 MB, enforced before `wp_handle_upload()`.

= 1.1.7 =
* Fix: Fixed the "Payment Pending" and infinite loading issues to ensure smooth order placement.
* Fix: Removed global namespace function to comply with WordPress Naming Conventions (Non-Prefixed Function error).
* Fix: Optimized asset enqueuing using anonymous functions for better security and compatibility.

= 1.0.8 =
* Fixed: Resolved "Security check failed" error during checkout when a user tries to create a new account while using this payment gateway.
* Fixed: Resolved infinite loading issue on the "Order Received" page by optimizing the checkout AJAX flow and standardizing redirect logic.
* Improved: Enhanced the Admin Order Details UI with a refined receipt verification box and a "View Full Image" button for better accessibility.
* Compliance: Fully sanitized all $_POST and $_FILES inputs to adhere to WordPress Security Coding Standards (WPCS).

= 1.0.7 =
* Improve: Enhanced the checkout user interface with a modern, card-based layout for bank details.
* Fixed: Resolved "Choose File" border breaking issue on mobile devices.
* Fixed: Increased image upload size limit to 5MB to support high-quality receipts.
* Tested: Fully compatible with the latest WordPress and WooCommerce versions.

= 1.0.6 =
* Fixed: Image upload size increased to 1MB.
* Tested with the latest WordPress version.
* Fixed minor bugs.

= 1.0.5 =
* Fix: Missing data.

= 1.0.4 =
* Major fix: Resolved critical conflict where inline script usage broke file upload functionality.
* Compliance: Removed direct inline &lt;script&gt; tag to adhere to WordPress plugin submission guidelines.
* Fix: Ensured file size validation and AJAX disable logic now function correctly using standard JS enqueuing methods.

= 1.0.3 =
* Disabled WooCommerce AJAX checkout when this gateway is selected to ensure file uploads work reliably.
* Improved JavaScript isolation and form handling.

= 1.0.2 =
* Fixed "Please upload a payment receipt" error on checkout.
* Added proper translators comment and PHPCS compliance.
* Enhanced security and file validation.

= 1.0.1 =
* Minor CSS and UI improvements.
* Better error handling for file uploads.

= 1.0.0 =
* Initial release.

== Update Notice ==
|= 2.2.0 =
Major update! This version includes complete codebase refactoring with new "ALD" prefix, bank logo display support, and improved compatibility. Upgrade recommended for all users.

|= 1.2.0 =
Multi-bank support is now available. Add unlimited bank accounts, and customers can pick their preferred bank at checkout. Security hardened — nonce verification is strictly enforced and receipt uploads are validated server-side for file type and size. Existing single-bank settings are migrated automatically — no data loss. Recommended for all users.
