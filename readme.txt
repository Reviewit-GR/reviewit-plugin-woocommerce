=== Reviewit Review Invitations for WooCommerce ===
Contributors: reviewitgr
Tags: reviews, woocommerce, invitations, greece, ratings
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Requires Plugins: woocommerce
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Invites your customers to review your shop on Reviewit.gr when an order is completed. It sends only the customer's email, first name and order number.

== Description ==

[Reviewit.gr](https://www.reviewit.gr) is a Greek review platform. This plugin asks Reviewit to email each customer an invitation to review your shop, a little after their order is completed.

* One setting: your Reviewit key. An invitation goes out when an order is completed.
* Sends only the customer's email address, first name and the order number. Nothing else about the order.
* Reviewit does the rest: it emails after the delay you chose, never emails someone who unsubscribed, never invites the same customer twice within 90 days, and skips customers who already reviewed you.
* The call is made in the background, so completing an order is not slowed down. Each order gets a private note with the answer.

You need a Reviewit business page with a plan, and invitations switched on there. See the [help center](https://help.reviewit.gr/connect/platforms/).

= For developers =

* `reviewit_invitation_should_send` (filter): return `false` to leave an order out (digital goods, wholesale, ...).
* `REVIEWIT_API_URL` (constant in wp-config.php): another API address, for testing.

== External services ==

This plugin sends data to Reviewit.gr so it can email your customer an invitation to review your shop. It is the plugin's whole purpose.

* **What is sent, and when:** when an order reaches the status you chose in the settings, one request to `https://api.reviewit.gr/v1/invitations` with the customer's billing email address, billing first name, the order number, and your Reviewit key.
* **What Reviewit does with it:** it emails the customer one invitation, erases the address as soon as the email is sent (at the latest after 30 days), and never emails someone who unsubscribed.
* The service is run by Reviewit.gr: [privacy policy](https://www.reviewit.gr/privacy-policy/) and [terms of use](https://www.reviewit.gr/terms/).

You must have told your customers that you will ask them for a review (for example in your privacy policy). Reviewit asks you to confirm it before it accepts invitations.

== Installation ==

1. Install and activate the plugin (WooCommerce must be active).
2. On Reviewit, open your business page, section «Σύνδεση καταστήματος», and create a key.
3. In WooCommerce > Reviewit paste the key and save.

== Frequently Asked Questions ==

= Does it send an invitation for every order? =

For every completed order, unless Reviewit skips it (the customer unsubscribed, was invited in the last 90 days, or already reviewed you) or a filter leaves it out. An order is sent once.

= Why was an order "refused"? =

The order note says why. `invitations_disabled`: switch invitations on in your Reviewit business page. `plan_required`: the page needs a plan. `monthly_limit`: your plan's monthly allowance is used. `unauthorized`: the key is wrong or was revoked.

== Changelog ==

= 0.1.0 =
* First version.
