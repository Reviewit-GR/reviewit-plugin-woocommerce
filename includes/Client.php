<?php
/**
 * The only code that talks to Reviewit: POST /v1/invitations.
 *
 * @package Reviewit\Invitations
 */

namespace Reviewit\Invitations;

defined( 'ABSPATH' ) || exit;

class Client {

	const DEFAULT_API = 'https://api.reviewit.gr';

	/** What became of a call. */
	const QUEUED  = 'queued';   // Reviewit will send the email.
	const TEST    = 'test';     // The test key: answered like the live key, nothing sent.
	const SKIPPED = 'skipped';  // Reviewit decided not to send (see the reason).
	const FAILED  = 'failed';   // Will not work if repeated: tell the shop.
	const RETRY   = 'retry';    // Reviewit or the network was unavailable: try again later.

	/** @var string */
	private $key;

	/** @var string */
	private $api;

	public function __construct( string $key, string $api = self::DEFAULT_API ) {
		$this->key = trim( $key );
		$this->api = untrailingslashit( $api );
	}

	/** A test key (rvk_test_...) is answered like the live one but nothing is stored, sent or counted. */
	public static function is_test_key( string $key ): bool {
		return 0 === strpos( trim( $key ), 'rvk_test_' );
	}

	/**
	 * Asks Reviewit to invite one customer.
	 *
	 * @return array{outcome:string,status:int,detail:string}
	 */
	public function invite( string $email, string $first_name, string $order_ref ): array {
		$body = array(
			'email'    => $email,
			'orderRef' => $order_ref,
		);
		if ( '' !== $first_name ) {
			$body['firstName'] = $first_name;
		}

		$response = wp_remote_post(
			$this->api . '/v1/invitations',
			array(
				'timeout'    => 10,
				'user-agent' => 'reviewit-woocommerce/' . REVIEWIT_INVITATIONS_VERSION,
				'headers'    => array(
					'Authorization' => 'Bearer ' . $this->key,
					'Content-Type'  => 'application/json',
				),
				'body'       => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'outcome' => self::RETRY,
				'status'  => 0,
				'detail'  => $response->get_error_message(),
			);
		}

		$decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		return self::interpret( (int) wp_remote_retrieve_response_code( $response ), is_array( $decoded ) ? $decoded : array() );
	}

	/**
	 * Turns an HTTP answer into an outcome. Pure, so it is tested without WordPress.
	 *
	 * @param int                  $status HTTP status.
	 * @param array<string,mixed>  $body   Decoded JSON ({} when there was none).
	 * @return array{outcome:string,status:int,detail:string}
	 */
	public static function interpret( int $status, array $body ): array {
		$detail = isset( $body['reason'] ) ? (string) $body['reason'] : ( isset( $body['error'] ) ? (string) $body['error'] : '' );
		if ( 'validation_failed' === $detail && isset( $body['field'] ) ) {
			$detail .= ':' . $body['field'];
		}

		if ( 202 === $status ) {
			$outcome = ! empty( $body['test'] ) ? self::TEST : self::QUEUED;
		} elseif ( 200 === $status ) {
			$outcome = ! empty( $body['test'] ) ? self::TEST : self::SKIPPED;
		} elseif ( $status >= 500 || 0 === $status ) {
			$outcome = self::RETRY;
		} else {
			// 401 wrong key, 403 invitations off or no plan, 422 bad data, 429 the month's limit: repeating will not help.
			$outcome = self::FAILED;
		}

		return array(
			'outcome' => $outcome,
			'status'  => $status,
			'detail'  => $detail,
		);
	}
}
