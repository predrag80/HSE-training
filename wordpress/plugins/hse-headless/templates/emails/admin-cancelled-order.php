<?php
/**
 * Branded merchant notification for a cancelled WooCommerce order.
 *
 * @package HSETraining\Headless
 */

defined( 'ABSPATH' ) || exit;

$merchant_variant = 'cancelled';
require __DIR__ . '/admin-new-order.php';
