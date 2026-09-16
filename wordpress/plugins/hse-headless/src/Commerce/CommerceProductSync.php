<?php
/**
 * Derived WooCommerce product catalogue for staging checkout evaluation.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Commerce;

use HSETraining\Headless\Content\ContentLocale;
use HSETraining\Headless\Course\CourseMeta;
use HSETraining\Headless\Course\CoursePostType;
use HSETraining\Headless\Course\CoursePromotionMeta;

defined( 'ABSPATH' ) || exit;

/** Mirrors published Course content into hidden WooCommerce products. */
final class CommerceProductSync {
	public const SYNCED_META             = '_hse_course_product_synced';
	public const SOURCE_ID_META          = '_hse_source_course_id';
	public const COURSE_KEY_META         = '_hse_course_key';
	public const ONLINE_PURCHASE_META    = '_hse_online_purchase_enabled';

	private const LOCALIZED_SOURCE_META        = '_hse_course_source_%s';
	private const LOCALIZED_TITLE_META         = '_hse_course_title_%s';
	private const LOCALIZED_DESCRIPTION_META   = '_hse_course_description_%s';
	private const LOCALIZED_SUMMARY_META       = '_hse_course_summary_%s';
	private const LOCALIZED_IMAGE_META         = '_hse_course_image_%s';
	private const LOCALIZED_VISIBLE_PRICE_META = '_hse_course_visible_price_%s';
	private const LOCALIZED_EYEBROW_META       = '_hse_course_page_eyebrow_%s';
	private const LOCALIZED_CARD_LABEL_META    = '_hse_course_card_label_%s';
	private const LOCALIZED_CTA_LABEL_META     = '_hse_course_cta_label_%s';
	private const LOCALIZED_FEATURED_META      = '_hse_course_featured_%s';

	/** Return whether a Course is explicitly enabled and has a valid CMS price. */
	public static function is_online_sales_course( string $course_key ): bool {
		$course_key = CourseMeta::sanitize_course_key( $course_key );
		if ( '' === $course_key ) {
			return false;
		}
		$source     = self::find_source( $course_key, ContentLocale::DEFAULT_LOCALE, 0 );
		$source     = $source ?: self::find_source( $course_key, '', 0 );

		return $source
			&& (bool) get_post_meta( $source->ID, CourseMeta::ONLINE_PURCHASE_ENABLED, true )
			&& '' !== CourseMeta::sanitize_online_price( get_post_meta( $source->ID, CourseMeta::ONLINE_PRICE, true ) );
	}

	/** Register automatic synchronization for future Course edits. */
	public static function register_hooks(): void {
		add_action( 'save_post_' . CoursePostType::POST_TYPE, array( self::class, 'sync_saved_course' ), 40, 3 );
		add_action( 'rest_after_insert_' . CoursePostType::POST_TYPE, array( self::class, 'sync_rest_course' ), 40, 3 );
		add_action( 'before_delete_post', array( self::class, 'sync_before_delete' ), 20, 2 );
	}

	/** Synchronize a Course after native editor or programmatic saves. */
	public static function sync_saved_course( $post_id, $post, $update ): void {
		unset( $update );
		if ( ! $post instanceof \WP_Post
			|| CoursePostType::POST_TYPE !== $post->post_type
			|| wp_is_post_autosave( $post_id )
			|| wp_is_post_revision( $post_id ) ) {
			return;
		}

		$course_key = CourseMeta::sanitize_course_key( get_post_meta( $post_id, CourseMeta::COURSE_KEY, true ) );
		if ( '' !== $course_key ) {
			self::sync_course_key( $course_key );
		}
	}

	/** Synchronize after REST has persisted registered Course metadata. */
	public static function sync_rest_course( $post, $request, $creating ): void {
		unset( $request, $creating );
		if ( $post instanceof \WP_Post ) {
			self::sync_saved_course( $post->ID, $post, true );
		}
	}

	/** Re-resolve the product before a Course record is permanently removed. */
	public static function sync_before_delete( $post_id, $post ): void {
		if ( ! $post instanceof \WP_Post || CoursePostType::POST_TYPE !== $post->post_type ) {
			return;
		}

		$course_key = CourseMeta::sanitize_course_key( get_post_meta( $post_id, CourseMeta::COURSE_KEY, true ) );
		if ( '' !== $course_key ) {
			self::sync_course_key( $course_key, (int) $post_id );
		}
	}

	/**
	 * Synchronize every distinct published Course key and retire stale mirrors.
	 *
	 * @return array{synced: array<int, string>, retired: array<int, string>}
	 */
	public static function sync_all(): array {
		$course_keys = array();
		$post_ids    = get_posts(
			array(
				'post_type'      => CoursePostType::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		foreach ( $post_ids as $post_id ) {
			$course_key = CourseMeta::sanitize_course_key( get_post_meta( $post_id, CourseMeta::COURSE_KEY, true ) );
			if ( '' !== $course_key ) {
				$course_keys[ $course_key ] = true;
			}
		}

		$synced = array();
		foreach ( array_keys( $course_keys ) as $course_key ) {
			$product = self::sync_course_key( $course_key );
			if ( ! is_wp_error( $product ) && $product ) {
				$synced[] = $course_key;
			}
		}

		$retired = array();
		foreach ( self::synced_product_ids() as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}

			$course_key = CourseMeta::sanitize_course_key( $product->get_sku() );
			if ( '' !== $course_key && ! isset( $course_keys[ $course_key ] ) ) {
				$product->set_status( 'draft' );
				$product->save();
				$retired[] = $course_key;
			}
		}

		return array(
			'synced'  => $synced,
			'retired' => $retired,
		);
	}

	/** Synchronize one stable Course key into a single hidden simple product. */
	public static function sync_course_key( string $course_key, int $exclude_post_id = 0 ) {
		if ( ! CommerceConfiguration::is_enabled() ) {
			return new \WP_Error( 'hse_commerce_disabled', __( 'Commerce synchronization is disabled.', 'hse-headless' ) );
		}
		if ( ! function_exists( 'wc_get_product_id_by_sku' ) || ! class_exists( 'WC_Product_Simple' ) ) {
			return new \WP_Error( 'hse_commerce_unavailable', __( 'WooCommerce must be active before Course products can be synchronized.', 'hse-headless' ) );
		}

		$course_key = CourseMeta::sanitize_course_key( $course_key );
		if ( '' === $course_key ) {
			return new \WP_Error( 'hse_commerce_course_key_invalid', __( 'Course key is invalid.', 'hse-headless' ) );
		}

		$source     = self::find_source( $course_key, ContentLocale::DEFAULT_LOCALE, $exclude_post_id );
		$source     = $source ?: self::find_source( $course_key, '', $exclude_post_id );
		$product_id = wc_get_product_id_by_sku( $course_key );
		$product    = $product_id ? wc_get_product( $product_id ) : new \WC_Product_Simple();

		if ( $product_id && ! $product instanceof \WC_Product_Simple ) {
			return new \WP_Error( 'hse_commerce_product_type_invalid', __( 'The matching WooCommerce SKU is not a simple product.', 'hse-headless' ) );
		}
		if ( ! $source ) {
			if ( $product && $product_id && '1' === (string) get_post_meta( $product_id, self::SYNCED_META, true ) ) {
				$product->set_status( 'draft' );
				$product->save();
			}
			return $product_id ? $product : null;
		}

		$product->set_name( wp_strip_all_tags( $source->post_title ) );
		$product->set_slug( $source->post_name ?: $course_key );
		$product->set_sku( $course_key );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'hidden' );
		$product->set_description( wp_kses_post( $source->post_content ) );
		$product->set_short_description( wp_kses_post( get_post_meta( $source->ID, CourseMeta::SHORT_DESCRIPTION, true ) ) );
		$product->set_image_id( (int) get_post_thumbnail_id( $source->ID ) );
		$product->set_menu_order( (int) $source->menu_order );
		$product->set_virtual( true );
		$product->set_sold_individually( true );
		$product->set_manage_stock( false );
		$product->set_stock_status( 'instock' );
		$product->set_tax_status( 'none' );
		$product->set_reviews_allowed( false );

		$online_price   = CourseMeta::sanitize_online_price( get_post_meta( $source->ID, CourseMeta::ONLINE_PRICE, true ) );
		$online_enabled = (bool) get_post_meta( $source->ID, CourseMeta::ONLINE_PURCHASE_ENABLED, true ) && '' !== $online_price;
		if ( $online_enabled ) {
			$product->set_regular_price( $online_price );
			$product->set_sale_price( '' );
			$product->set_price( $online_price );
		} else {
			$product->set_regular_price( '' );
			$product->set_sale_price( '' );
			$product->set_price( '' );
		}

		$saved_id = $product->save();
		update_post_meta( $saved_id, self::SYNCED_META, '1' );
		update_post_meta( $saved_id, self::SOURCE_ID_META, (int) $source->ID );
		update_post_meta( $saved_id, self::COURSE_KEY_META, $course_key );
		update_post_meta( $saved_id, self::ONLINE_PURCHASE_META, $online_enabled ? '1' : '0' );
		self::sync_localized_content( $saved_id, $course_key, $exclude_post_id );

		return wc_get_product( $saved_id );
	}

	/** Copy both editorial languages into private product metadata. */
	private static function sync_localized_content( int $product_id, string $course_key, int $exclude_post_id ): void {
		foreach ( ContentLocale::supported() as $locale ) {
			$source = self::find_source( $course_key, $locale, $exclude_post_id );
			$values = array(
				self::LOCALIZED_SOURCE_META        => $source ? (int) $source->ID : 0,
				self::LOCALIZED_TITLE_META         => $source ? wp_strip_all_tags( $source->post_title ) : '',
				self::LOCALIZED_DESCRIPTION_META   => $source ? wp_kses_post( $source->post_content ) : '',
				self::LOCALIZED_SUMMARY_META       => $source ? wp_kses_post( get_post_meta( $source->ID, CourseMeta::SHORT_DESCRIPTION, true ) ) : '',
				self::LOCALIZED_IMAGE_META         => $source ? (int) get_post_thumbnail_id( $source->ID ) : 0,
				self::LOCALIZED_VISIBLE_PRICE_META => $source ? sanitize_text_field( get_post_meta( $source->ID, CourseMeta::VISIBLE_PRICE, true ) ) : '',
				self::LOCALIZED_EYEBROW_META       => $source ? sanitize_text_field( get_post_meta( $source->ID, CourseMeta::PAGE_EYEBROW, true ) ) : '',
				self::LOCALIZED_CARD_LABEL_META    => $source ? sanitize_text_field( get_post_meta( $source->ID, CoursePromotionMeta::HOMEPAGE_LABEL, true ) ) : '',
				self::LOCALIZED_CTA_LABEL_META     => $source ? sanitize_text_field( get_post_meta( $source->ID, CoursePromotionMeta::HOMEPAGE_CTA_LABEL, true ) ) : '',
				self::LOCALIZED_FEATURED_META      => $source ? (bool) get_post_meta( $source->ID, CoursePromotionMeta::FEATURED_ON_HOMEPAGE, true ) : false,
			);

			foreach ( $values as $pattern => $value ) {
				update_post_meta( $product_id, sprintf( $pattern, $locale ), $value );
			}
		}
	}

	/** Return the preferred published Course source for one key and language. */
	private static function find_source( string $course_key, string $locale, int $exclude_post_id ) {
		$meta_query = array(
			array(
				'key'   => CourseMeta::COURSE_KEY,
				'value' => $course_key,
			),
		);
		if ( '' !== $locale ) {
			$meta_query[] = ContentLocale::query_clause( $locale );
		}

		$posts = get_posts(
			array(
				'post_type'      => CoursePostType::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'post__not_in'   => $exclude_post_id ? array( $exclude_post_id ) : array(),
				'meta_query'     => $meta_query,
				'no_found_rows'  => true,
			)
		);

		return $posts ? $posts[0] : null;
	}

	/** Return IDs of products previously created by this derived catalogue. */
	private static function synced_product_ids(): array {
		return get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => self::SYNCED_META,
				'meta_value'     => '1',
				'no_found_rows'  => true,
			)
		);
	}
}
