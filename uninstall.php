<?php
/**
 * Removes the plugin's key when it is deleted from the Plugins page.
 *
 * @package Reviewit\Invitations
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'reviewit_invitations_key' );
