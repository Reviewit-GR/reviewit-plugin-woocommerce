<?php
/**
 * The settings page (WooCommerce > Reviewit): the key, and nothing else.
 *
 * @package Reviewit\Invitations
 */

namespace Reviewit\Invitations;

defined( 'ABSPATH' ) || exit;

class Settings {

	const OPTION = 'reviewit_invitations_key';
	const PAGE   = 'reviewit-invitations';

	public static function key(): string {
		return (string) get_option( self::OPTION, '' );
	}

	/** Only a developer changes the address, in wp-config.php, to try against another server. */
	public static function api_url(): string {
		return defined( 'REVIEWIT_API_URL' ) ? (string) constant( 'REVIEWIT_API_URL' ) : Client::DEFAULT_API;
	}

	public static function init( string $plugin_file ): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_filter(
			'plugin_action_links_' . plugin_basename( $plugin_file ),
			static function ( array $links ): array {
				array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=' . self::PAGE ) ) . '">' . esc_html__( 'Settings', 'reviewit-invitations' ) . '</a>' );
				return $links;
			}
		);
	}

	public static function menu(): void {
		add_submenu_page( 'woocommerce', 'Reviewit', 'Reviewit', 'manage_woocommerce', self::PAGE, array( __CLASS__, 'render' ) );
	}

	public static function register(): void {
		register_setting(
			self::PAGE,
			self::OPTION,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => '',
			)
		);
	}

	/** A key starts with rvk_ (or rvk_test_); anything else is refused and the saved key stays. Empty removes it. */
	public static function sanitize( $input ): string {
		$key = trim( sanitize_text_field( (string) $input ) );
		if ( '' !== $key && ! preg_match( '/^rvk_(test_)?[A-Za-z0-9_-]{8,}$/', $key ) ) {
			add_settings_error( self::OPTION, 'reviewit_key', __( 'That does not look like a Reviewit key. It starts with rvk_.', 'reviewit-invitations' ) );
			return self::key();
		}
		return $key;
	}

	public static function render(): void {
		$key = self::key();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Reviewit review invitations', 'reviewit-invitations' ); ?></h1>
			<?php settings_errors( self::OPTION ); ?>
			<?php if ( '' === $key ) : ?>
				<div class="notice notice-warning inline"><p><?php esc_html_e( 'No key yet: no invitations are sent.', 'reviewit-invitations' ); ?></p></div>
			<?php elseif ( Client::is_test_key( $key ) ) : ?>
				<div class="notice notice-info inline"><p><?php esc_html_e( 'This is a test key: nothing is emailed. Use your live key when you are ready.', 'reviewit-invitations' ); ?></p></div>
			<?php endif; ?>
			<form method="post" action="options.php">
				<?php settings_fields( self::PAGE ); ?>
				<table class="form-table" role="presentation"><tr>
					<th scope="row"><label for="reviewit-key"><?php esc_html_e( 'Reviewit key', 'reviewit-invitations' ); ?></label></th>
					<td>
						<input type="password" id="reviewit-key" class="regular-text" autocomplete="off" name="<?php echo esc_attr( self::OPTION ); ?>" value="<?php echo esc_attr( $key ); ?>" />
						<p class="description">
							<?php esc_html_e( 'Create it in your business page on Reviewit, under «Σύνδεση καταστήματος».', 'reviewit-invitations' ); ?>
							<a href="https://help.reviewit.gr/connect/get-started/" target="_blank" rel="noopener"><?php esc_html_e( 'Help', 'reviewit-invitations' ); ?></a>
						</p>
					</td>
				</tr></table>
				<p><?php esc_html_e( 'When an order is completed, only the customer\'s email address and first name and the order number are sent. Reviewit erases the address as soon as its email is sent.', 'reviewit-invitations' ); ?></p>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
