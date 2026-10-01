<?php
/**
 * Plain-text customer notification for a declined payment.
 *
 * @package HSETraining\Headless
 */

defined( 'ABSPATH' ) || exit;

$outcome = 'failed';
require __DIR__ . '/customer-payment-unsuccessful.php';
