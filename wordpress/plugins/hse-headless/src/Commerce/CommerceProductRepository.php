<?php
/**
 * Resolve public WooCommerce products by the stable Course key.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Commerce;

defined( 'ABSPATH' ) || exit;

/** Maps a stable course_key to a published WooCommerce product SKU. */
final class CommerceProductRepository {
	/** Resolve a published product without exposing its WordPress ID. */
	public static function get_by_course_key( string $course_key ) {
		if ( ! function_exists( 'wc_get_product_id_by_sku' ) || ! function_exists( 'wc_get_product' ) ) {
			return new \WP_Error(
				'hse_commerce_unavailable',
				__( 'Commerce is temporarily unavailable.', 'hse-headless' ),
				array( 'status' => 503 )
			);
		}

		$product_id = wc_get_product_id_by_sku( $course_key );
		$product    = $product_id ? wc_get_product( $product_id ) : false;

		if ( ! $product || 'publish' !== $product->get_status() ) {
			return new \WP_Error(
				'hse_commerce_product_not_found',
				__( 'The requested course is not available for online purchase.', 'hse-headless' ),
				array( 'status' => 404 )
			);
		}

		return $product;
	}
}
