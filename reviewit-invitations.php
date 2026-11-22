<?php
/**
 * Plugin Name:       Reviewit Review Invitations for WooCommerce
 * Plugin URI:        https://help.reviewit.gr/connect/platforms/
 * Description:       Invites your customers to review your shop on Reviewit.gr when an order is completed. Sends only the customer's email, first name and order number.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * WC requires at least: 7.1
 * WC tested up to:     11.3
 * Author:            Reviewit.gr
 * Author URI:        https://www.reviewit.gr
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       reviewit-invitations
 * Domain Path:       /languages
 *
 * @package Reviewit\Invitations
 */

defined( 'ABSPATH' ) || exit;

define( 'REVIEWIT_INVITATIONS_VERSION', '0.1.0' );

require_once __DIR__ . '/includes/Client.php';
require_once __DIR__ . '/includes/Sender.php';
require_once __DIR__ . '/includes/Settings.php';

// Works with WooCommerce's high-performance order storage.
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

add_action(
	'plugins_loaded',
	static function () {
		load_plugin_textdomain( 'reviewit-invitations', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		if ( ! class_exists( 'WooCommerce' ) ) {
			return; // "Requires Plugins" already makes WordPress ask for it.
		}
		\Reviewit\Invitations\Settings::init( __FILE__ );
		add_action( 'woocommerce_order_status_completed', array( \Reviewit\Invitations\Sender::class, 'schedule' ) );
		add_action( \Reviewit\Invitations\Sender::HOOK, array( \Reviewit\Invitations\Sender::class, 'run' ), 10, 2 );
	}
);
