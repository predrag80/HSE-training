<?php
/**
 * Plugin Name: HSE Training Headless
 * Description: Custom headless CMS functionality for the HSE Training platform.
 * Version: 0.25.10
 * Text Domain: hse-headless
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/src/Content/ContentLocale.php';
require_once __DIR__ . '/src/Content/ContentLocaleMigration.php';
require_once __DIR__ . '/src/Course/CoursePostType.php';
require_once __DIR__ . '/src/Course/CourseMeta.php';
require_once __DIR__ . '/src/Course/CoursePromotionMeta.php';
require_once __DIR__ . '/src/Course/CoursePageSettings.php';
require_once __DIR__ . '/src/Course/CoursePageRestController.php';
require_once __DIR__ . '/src/Training/TrainingPostType.php';
require_once __DIR__ . '/src/Training/TrainingMigration.php';
require_once __DIR__ . '/src/Company/CompanyPageSettings.php';
require_once __DIR__ . '/src/Company/CompanyPageRestController.php';
require_once __DIR__ . '/src/Legal/LegalPageSettings.php';
require_once __DIR__ . '/src/Legal/LegalPageRestController.php';
require_once __DIR__ . '/src/Legal/EcommerceLegalMigration.php';
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
require_once __DIR__ . '/src/Infrastructure/HeadlessMode.php';
require_once __DIR__ . '/src/Infrastructure/SmtpMailer.php';
require_once __DIR__ . '/src/Contact/ContactEmailTemplate.php';
require_once __DIR__ . '/src/Contact/ContactRestController.php';
require_once __DIR__ . '/src/Commerce/CommerceConfiguration.php';
require_once __DIR__ . '/src/Commerce/CommerceLocale.php';
require_once __DIR__ . '/src/Commerce/CommerceProductRepository.php';
require_once __DIR__ . '/src/Commerce/CommerceProductSync.php';
require_once __DIR__ . '/src/Commerce/CommerceRestController.php';
require_once __DIR__ . '/src/Commerce/CommerceCheckout.php';
require_once __DIR__ . '/src/Commerce/CommercePresentation.php';
require_once __DIR__ . '/src/Commerce/CommerceCustomerEmail.php';

Course\CoursePostType::register_hooks();
Training\TrainingPostType::register_hooks();
Course\CourseMeta::register_hooks();
Course\CoursePromotionMeta::register_hooks();
Course\CoursePageSettings::register_hooks();
Course\CoursePageRestController::register_hooks();
Training\TrainingMigration::register_hooks();
Company\CompanyPageSettings::register_hooks();
Company\CompanyPageRestController::register_hooks();
Legal\LegalPageSettings::register_hooks();
Legal\LegalPageRestController::register_hooks();
Legal\EcommerceLegalMigration::register_hooks();
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
Content\ContentLocale::register_hooks();
Content\ContentLocaleMigration::register_hooks();
Infrastructure\HeadlessMode::register_hooks();
Infrastructure\SmtpMailer::register_hooks();
Contact\ContactRestController::register_hooks();
Commerce\CommerceRestController::register_hooks();
Commerce\CommerceLocale::register_hooks();
Commerce\CommerceCheckout::register_hooks();
Commerce\CommercePresentation::register_hooks();
Commerce\CommerceProductSync::register_hooks();
Commerce\CommerceCustomerEmail::register_hooks();
