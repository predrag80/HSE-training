<?php
/**
 * Plugin Name: HSE Training Headless
 * Description: Custom headless CMS functionality for the HSE Training platform.
 * Version: 0.7.0
 * Text Domain: hse-headless
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/src/Course/CoursePostType.php';
require_once __DIR__ . '/src/Course/CourseMeta.php';
require_once __DIR__ . '/src/Course/CoursePromotionMeta.php';
require_once __DIR__ . '/src/Company/CompanyPageSettings.php';
require_once __DIR__ . '/src/Company/CompanyPageRestController.php';
require_once __DIR__ . '/src/Service/ServicePostType.php';
require_once __DIR__ . '/src/Service/ServiceMeta.php';
require_once __DIR__ . '/src/Service/ServiceRepository.php';
require_once __DIR__ . '/src/Service/ServiceRestController.php';
require_once __DIR__ . '/src/Reference/ReferencePostType.php';
require_once __DIR__ . '/src/Reference/ReferenceMeta.php';
require_once __DIR__ . '/src/Reference/ReferenceRepository.php';
require_once __DIR__ . '/src/Reference/ReferenceRestController.php';
require_once __DIR__ . '/src/Homepage/HeroSlidePostType.php';
require_once __DIR__ . '/src/Homepage/HeroSlideMeta.php';
require_once __DIR__ . '/src/Homepage/HomepageSettings.php';
require_once __DIR__ . '/src/Homepage/HomepageRestController.php';
require_once __DIR__ . '/src/Homepage/HomepageMigration.php';

Course\CoursePostType::register_hooks();
Course\CourseMeta::register_hooks();
Course\CoursePromotionMeta::register_hooks();
Company\CompanyPageSettings::register_hooks();
Company\CompanyPageRestController::register_hooks();
Service\ServicePostType::register_hooks();
Service\ServiceMeta::register_hooks();
Service\ServiceRestController::register_hooks();
Reference\ReferencePostType::register_hooks();
Reference\ReferenceMeta::register_hooks();
Reference\ReferenceRestController::register_hooks();
Homepage\HeroSlidePostType::register_hooks();
Homepage\HeroSlideMeta::register_hooks();
Homepage\HomepageSettings::register_hooks();
Homepage\HomepageRestController::register_hooks();
Homepage\HomepageMigration::register_hooks();
