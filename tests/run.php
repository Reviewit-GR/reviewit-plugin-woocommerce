<?php
/**
 * Tests without WordPress or PHPUnit: php tests/run.php
 * The pure parts (reading Reviewit's answers, the notes, the settings clean-up) run against tiny stand-ins for the WordPress functions.
 */

define( 'ABSPATH', __DIR__ );
define( 'REVIEWIT_INVITATIONS_VERSION', 'test' );

$GLOBALS['options'] = array();
$GLOBALS['errors']  = array();

function __( $s ) { return $s; }
function esc_html__( $s ) { return $s; }
function untrailingslashit( $s ) { return rtrim( $s, '/' ); }
function sanitize_text_field( $s ) { return trim( strip_tags( $s ) ); }
function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', $s ) ); }
function get_option( $k, $d = false ) { return $GLOBALS['options'][ $k ] ?? $d; }
function update_option( $k, $v ) { $GLOBALS['options'][ $k ] = $v; return true; }
function add_settings_error( $a, $b, $m ) { $GLOBALS['errors'][] = $m; }

require __DIR__ . '/../includes/Client.php';
require __DIR__ . '/../includes/Settings.php';
require __DIR__ . '/../includes/Sender.php';

use Reviewit\Invitations\Client;
use Reviewit\Invitations\Sender;
use Reviewit\Invitations\Settings;

$failed = 0;
function check( string $name, $got, $want ): void {
	global $failed;
	if ( $got !== $want ) {
		++$failed;
		echo "FAIL $name\n  got:  " . json_encode( $got, JSON_UNESCAPED_UNICODE ) . "\n  want: " . json_encode( $want, JSON_UNESCAPED_UNICODE ) . "\n";
	}
}

// Reviewit's answers (the API's contract: POST /v1/invitations).
check( '202 queued', Client::interpret( 202, array( 'state' => 'queued' ) ), array( 'outcome' => 'queued', 'status' => 202, 'detail' => '' ) );
check( '202 with the test key', Client::interpret( 202, array( 'state' => 'queued', 'test' => true ) )['outcome'], 'test' );
check( '200 skipped, repeat', Client::interpret( 200, array( 'state' => 'skipped', 'reason' => 'repeat' ) ), array( 'outcome' => 'skipped', 'status' => 200, 'detail' => 'repeat' ) );
check( '200 skipped, reviewed', Client::interpret( 200, array( 'state' => 'skipped', 'reason' => 'reviewed' ) )['detail'], 'reviewed' );
check( '401 is final', Client::interpret( 401, array() )['outcome'], 'failed' );
check( '403 invitations_disabled', Client::interpret( 403, array( 'error' => 'invitations_disabled' ) ), array( 'outcome' => 'failed', 'status' => 403, 'detail' => 'invitations_disabled' ) );
check( '429 monthly_limit is final', Client::interpret( 429, array( 'error' => 'monthly_limit' ) )['outcome'], 'failed' );
check( '429 rate_limited is tried again', Client::interpret( 429, array( 'error' => 'rate_limited' ) )['outcome'], 'retry' );
check( '429 without a body (the gateway) is tried again', Client::interpret( 429, array() )['outcome'], 'retry' );
check( '422 names the field', Client::interpret( 422, array( 'error' => 'validation_failed', 'field' => 'email' ) )['detail'], 'validation_failed:email' );
check( '500 is tried again', Client::interpret( 500, array( 'error' => 'internal_error' ) )['outcome'], 'retry' );
check( '503 is tried again', Client::interpret( 503, array() )['outcome'], 'retry' );
check( 'no answer is tried again', Client::interpret( 0, array() )['outcome'], 'retry' );

check( 'test key', Client::is_test_key( 'rvk_test_abc' ), true );
check( 'live key', Client::is_test_key( 'rvk_abc' ), false );
check( 'key with spaces around', Client::is_test_key( '  rvk_test_abc ' ), true );

// The note an order gets.
check( 'note queued', Sender::note( array( 'outcome' => 'queued', 'status' => 202, 'detail' => '' ) ), 'Reviewit: the review invitation was queued.' );
check( 'note skipped', Sender::note( array( 'outcome' => 'skipped', 'status' => 200, 'detail' => 'repeat' ) ), 'Reviewit: no invitation will be sent (repeat).' );
check( 'note refused', Sender::note( array( 'outcome' => 'failed', 'status' => 403, 'detail' => 'plan_required' ) ), 'Reviewit: the invitation was refused (403 plan_required).' );

// The settings: a key is checked, an empty field removes it, junk keeps the saved one.
$live = 'rvk_' . str_repeat( 'a', 32 );
check( 'save a key', Settings::sanitize( " $live " ), $live );
update_option( Settings::OPTION, $live );
check( 'junk is refused, key kept', Settings::sanitize( 'hello' ), $live );
check( 'junk is explained', count( $GLOBALS['errors'] ), 1 );
check( 'empty removes the key', Settings::sanitize( '' ), '' );
check( 'test key accepted', Settings::sanitize( 'rvk_test_' . str_repeat( 'b', 32 ) ), 'rvk_test_' . str_repeat( 'b', 32 ) );

// Every text the plugin shows has a Greek translation, and the .mo is not older than the .po.
$po = file_get_contents( __DIR__ . '/../languages/reviewit-invitations-el.po' );
preg_match_all( '/^msgid "(.*)"$/m', $po, $m );
$have = array_map( 'stripslashes', $m[1] );
$used = array();
foreach ( glob( __DIR__ . '/../includes/*.php' ) as $file ) {
	preg_match_all( "/(?:esc_html__|esc_html_e|__)\\(\\s*'((?:[^'\\\\]|\\\\.)*)',\\s*'reviewit-invitations'/", file_get_contents( $file ), $f );
	$used = array_merge( $used, array_map( 'stripslashes', $f[1] ) );
}
check( 'texts found', count( $used ) > 10, true );
check( 'untranslated texts', array_values( array_diff( $used, $have ) ), array() );
check( 'translations nobody uses', array_values( array_diff( array_filter( $have ), $used ) ), array() );
check( '.mo is not older than .po', filemtime( __DIR__ . '/../languages/reviewit-invitations-el.mo' ) >= filemtime( __DIR__ . '/../languages/reviewit-invitations-el.po' ), true );

echo $failed ? "\n$failed failed\n" : "all passed\n";
exit( $failed ? 1 : 0 );
