<?php
/**
 * Plain-text customer notification for a cancelled payment.
 *
 * @package HSETraining\Headless
 */

defined( 'ABSPATH' ) || exit;

$outcome = 'cancelled';
require __DIR__ . '/customer-payment-unsuccessful.php';
