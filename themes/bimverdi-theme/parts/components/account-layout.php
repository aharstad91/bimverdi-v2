<?php
/**
 * Account Layout Wrapper Component
 *
 * Provides consistent two-column layout for all account/settings pages.
 * Left: Sidenav navigation
 * Right: Page content
 *
 * Usage:
 * get_template_part('parts/components/account-layout', null, [
 *     'title' => 'Page Title',
 *     'description' => 'Page description',
 *     'actions' => [['text' => 'Button', 'url' => '/url', 'variant' => 'primary']],
 * ]);
 *
 * Then the page content follows after the component.
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

$title = $args['title'] ?? '';
$description = $args['description'] ?? '';
$actions = $args['actions'] ?? [];
$show_header = $args['show_header'] ?? true;
$show_sidenav = $args['show_sidenav'] ?? true;
?>

<?php if ($show_header && $title): ?>
<!-- Page Header -->
<?php get_template_part('parts/components/page-header', null, [
    'title' => $title,
    'description' => $description,
    'actions' => $actions,
]); ?>
<?php endif; ?>

<?php if ($show_sidenav): ?>
<!-- Account Layout: Sidenav + Content -->
<div class="flex flex-col md:flex-row gap-6 md:gap-8 lg:gap-12">
    <!-- Sidenav -->
    <?php get_template_part('parts/components/account-sidenav'); ?>

    <!-- Main Content Area -->
    <div class="flex-1 min-w-0">
<?php else: ?>
<!-- Single-column layout (no sidenav) -->
<div class="max-w-2xl mx-auto">
    <div class="min-w-0"
         data-layout="no-sidenav">
<?php endif; ?>
