<?php
/**
 * Sends the invitation for one order, off the request that completed it.
 *
 * @package Reviewit\Invitations
 */

namespace Reviewit\Invitations;

defined( 'ABSPATH' ) || exit;

class Sender {

	const HOOK     = 'reviewit_send_invitation';
	const META     = '_reviewit_invitation';
	const ATTEMPTS = 3;
	const WAIT     = HOUR_IN_SECONDS;

	/** Queues the call with WooCommerce's Action Scheduler, so completing the order is not slowed down. */
	public static function schedule( $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( $order && ! $order->get_meta( self::META ) && self::should_send( $order ) ) {
			as_enqueue_async_action( self::HOOK, array( (int) $order_id, 1 ), 'reviewit' );
		}
	}

	/** Shops can leave an order out (digital goods, wholesale, ...) with this filter. */
	public static function should_send( $order ): bool {
		return (bool) apply_filters( 'reviewit_invitation_should_send', is_email( $order->get_billing_email() ), $order );
	}

	/** One call, and what to do with the answer. An order is answered once; only a failure of Reviewit or the network is tried again. */
	public static function run( $order_id, $attempt = 1 ): void {
		$order = wc_get_order( $order_id );
		$key   = Settings::key();
		if ( ! $order || '' === $key || $order->get_meta( self::META ) ) {
			return;
		}

		$result = ( new Client( $key, Settings::api_url() ) )->invite(
			$order->get_billing_email(),
			trim( $order->get_billing_first_name() ),
			(string) $order->get_order_number()
		);

		if ( Client::RETRY === $result['outcome'] && $attempt < self::ATTEMPTS ) {
			as_schedule_single_action( time() + self::WAIT, self::HOOK, array( (int) $order_id, $attempt + 1 ), 'reviewit' );
			return;
		}

		$order->update_meta_data( self::META, $result['outcome'] );
		$order->add_order_note( self::note( $result ) );
		$order->save();
	}

	/** The private order note: the only record, so the shop sees what became of each order. */
	public static function note( array $result ): string {
		$detail = '' !== $result['detail'] ? ' (' . $result['detail'] . ')' : '';
		switch ( $result['outcome'] ) {
			case Client::QUEUED:
				return __( 'Reviewit: the review invitation was queued.', 'reviewit-invitations' );
			case Client::TEST:
				return __( 'Reviewit: test key, nothing was sent.', 'reviewit-invitations' );
			case Client::SKIPPED:
				/* translators: %s: the reason Reviewit gave, for example "(repeat)". */
				return sprintf( __( 'Reviewit: no invitation will be sent%s.', 'reviewit-invitations' ), $detail );
			case Client::RETRY:
				return __( 'Reviewit could not be reached: the invitation was not sent.', 'reviewit-invitations' );
			default:
				/* translators: 1: HTTP status, 2: the error code Reviewit gave, for example "plan_required". */
				return sprintf( __( 'Reviewit: the invitation was refused (%1$d %2$s).', 'reviewit-invitations' ), $result['status'], $result['detail'] );
		}
	}
}
