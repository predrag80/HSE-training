<?php
/**
 * Branded merchant notification for a WooCommerce order whose payment failed.
 *
 * @package HSETraining\Headless
 */

defined( 'ABSPATH' ) || exit;

$merchant_variant = 'failed';
require __DIR__ . '/admin-new-order.php';
