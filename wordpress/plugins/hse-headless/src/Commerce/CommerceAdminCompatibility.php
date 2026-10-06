<?php
/**
 * Compatibility fixes for third-party Commerce controls in wp-admin.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Commerce;

defined( 'ABSPATH' ) || exit;

/** Keeps hidden BokaPOS refund controls from blocking ordinary order updates. */
final class CommerceAdminCompatibility {
	/** Register admin-only assets. */
	public static function register_hooks(): void {
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_action( 'wp_ajax_woocommerce_refund_line_items', array( self::class, 'validate_fiscal_refund_lines' ), 0 );
	}

	/** Load the bounded compatibility script only while editing an order. */
	public static function enqueue_assets( $hook_suffix ): void {
		if ( ! CommerceConfiguration::is_enabled() ) {
			return;
		}

		$screen    = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$screen_id = is_object( $screen ) && isset( $screen->id ) ? (string) $screen->id : '';
		$page      = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action    = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! self::is_order_editor_request( (string) $hook_suffix, $screen_id, $page, $action ) ) {
			return;
		}

		$plugin_file = dirname( __DIR__, 2 ) . '/hse-headless.php';
		wp_enqueue_script(
			'hse-commerce-admin-compatibility',
			plugins_url( 'assets/commerce-admin.js', $plugin_file ),
			array(),
			'0.35.2',
			true
		);
		wp_localize_script(
			'hse-commerce-admin-compatibility',
			'hseCommerceAdmin',
			array(
				'refundLinesRequired' => __( 'Select the refunded item quantity before continuing. BokaPOS cannot fiscalize an amount-only refund.', 'hse-headless' ),
			)
		);
	}

	/** Stop a fiscal refund before the payment gateway runs when it has no item quantity. */
	public static function validate_fiscal_refund_lines(): void {
		if ( ! CommerceConfiguration::is_enabled() || empty( $_POST['bokapos_refund_fiscalize'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}

		if ( ! check_ajax_referer( 'order-item', 'security', false ) || ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}

		$quantities = isset( $_POST['line_item_qtys'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			? json_decode( sanitize_text_field( wp_unslash( $_POST['line_item_qtys'] ) ), true ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			: array();

		if ( ! self::has_refund_line_quantity( $quantities ) ) {
			wp_send_json_error(
				array(
					'error' => __( 'Select the refunded item quantity before continuing. BokaPOS cannot fiscalize an amount-only refund.', 'hse-headless' ),
				)
			);
		}
	}

	/** Return whether refund input contains at least one positive item quantity. */
	public static function has_refund_line_quantity( $quantities ): bool {
		if ( ! is_array( $quantities ) ) {
			return false;
		}

		foreach ( $quantities as $quantity ) {
			if ( is_numeric( $quantity ) && (float) $quantity > 0 ) {
				return true;
			}
		}

		return false;
	}

	/** Return whether the current request is an HPOS or legacy order editor. */
	public static function is_order_editor_request( string $hook_suffix, string $screen_id, string $page, string $action ): bool {
		$is_hpos = 'woocommerce_page_wc-orders' === $screen_id
			&& 'wc-orders' === $page
			&& 'edit' === $action;
		$is_legacy = 'post.php' === $hook_suffix
			&& in_array( $screen_id, array( 'shop_order', 'woocommerce_page_shop-order' ), true );

		return $is_hpos || $is_legacy;
	}
}
