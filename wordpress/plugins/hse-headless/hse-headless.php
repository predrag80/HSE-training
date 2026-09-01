<?php
/**
 * Plugin Name: HSE Training Headless
 * Description: Custom headless CMS functionality for the HSE Training platform.
 * Version: 0.1.0
 * Text Domain: hse-headless
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/src/Course/CoursePostType.php';
require_once __DIR__ . '/src/Course/CourseMeta.php';

Course\CoursePostType::register_hooks();
Course\CourseMeta::register_hooks();
