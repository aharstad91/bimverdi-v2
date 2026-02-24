<?php
/**
 * The template for displaying pages
 *
 * Clean, focused layout following Variant B design system:
 * - No sidebar (full width content)
 * - Borderless sections with good typography
 * - Warm, calm enterprise aesthetic
 *
 * @package BIMVerdi
 * @version 3.0.0
 */

get_header(); ?>

<main class="bv-page">
    <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>

            <!-- Page Header -->
            <header class="bv-page__header">
                <div class="bv-page__header-inner">
                    <h1 class="bv-page__title"><?php the_title(); ?></h1>
                    <?php if (has_excerpt()) : ?>
                        <p class="bv-page__excerpt"><?php echo get_the_excerpt(); ?></p>
                    <?php endif; ?>
                </div>
            </header>

            <!-- Page Content -->
            <article id="post-<?php the_ID(); ?>" <?php post_class('bv-page__content'); ?>>
                <div class="bv-page__content-inner">
                    <div class="bv-prose">
                        <?php the_content(); ?>
                    </div>
                </div>
            </article>

        <?php endwhile; ?>
    <?php else : ?>

        <div class="bv-page__content">
            <div class="bv-page__content-inner">
                <h1 class="bv-page__title">Side ikke funnet</h1>
                <p>Beklager, siden du leter etter finnes ikke.</p>
            </div>
        </div>

    <?php endif; ?>
</main>

<style>
/* ============================================
   BIM Verdi Page Template - Variant B
   Clean, focused layout without sidebar
   ============================================ */

.bv-page {
    background-color: #FAFAF8;
    min-height: calc(100vh - 64px);
}

/* Page Header */
.bv-page__header {
    background-color: #FFFFFF;
    border-bottom: 1px solid #E5E0D5;
    padding: 48px 24px;
}

.bv-page__header-inner {
    max-width: 800px;
    margin: 0 auto;
}

.bv-page__title {
    font-family: 'Moderat', 'Inter', system-ui, sans-serif;
    font-size: 2.5rem;
    font-weight: 700;
    color: #1A1A1A;
    line-height: 1.2;
    margin: 0;
}

.bv-page__excerpt {
    font-size: 1.125rem;
    color: #5A5A5A;
    line-height: 1.6;
    margin: 16px 0 0 0;
    max-width: 640px;
}

/* Page Content */
.bv-page__content {
    padding: 48px 24px 64px;
}

.bv-page__content-inner {
    max-width: 800px;
    margin: 0 auto;
}

/* Prose Styling for Content */
.bv-prose {
    font-family: 'Moderat', 'Inter', system-ui, sans-serif;
    font-size: 1.0625rem;
    line-height: 1.75;
    color: #1A1A1A;
}

.bv-prose h2 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1A1A1A;
    margin: 48px 0 24px 0;
    line-height: 1.3;
}

.bv-prose h2:first-child {
    margin-top: 0;
}

.bv-prose h3 {
    font-size: 1.375rem;
    font-weight: 600;
    color: #1A1A1A;
    margin: 40px 0 16px 0;
    line-height: 1.3;
}

.bv-prose h4 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1A1A1A;
    margin: 32px 0 12px 0;
}

.bv-prose p {
    margin: 0 0 24px 0;
}

.bv-prose a {
    color: #FF8B5E;
    text-decoration: none;
    border-bottom: 1px solid transparent;
    transition: border-color 0.15s ease;
}

.bv-prose a:hover {
    border-bottom-color: #FF8B5E;
}

.bv-prose ul,
.bv-prose ol {
    margin: 0 0 24px 0;
    padding-left: 24px;
}

.bv-prose li {
    margin-bottom: 8px;
}

.bv-prose li::marker {
    color: #888888;
}

.bv-prose blockquote {
    margin: 32px 0;
    padding: 24px 32px;
    background-color: #F5F5F4;
    border-left: 4px solid #FF8B5E;
    border-radius: 0 8px 8px 0;
}

.bv-prose blockquote p:last-child {
    margin-bottom: 0;
}

.bv-prose img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 32px 0;
}

/* Person/profile images - common pattern on content pages */
.bv-prose img.alignleft,
.bv-prose .alignleft img {
    width: 140px;
    height: 140px;
    object-fit: cover;
    border-radius: 8px;
    margin: 0 24px 16px 0;
}

/* H3 after floated content should clear */
.bv-prose h3 {
    clear: both;
    padding-top: 32px;
    border-top: 1px solid #E5E0D5;
}

.bv-prose h3:first-of-type {
    border-top: none;
    padding-top: 0;
}

.bv-prose figure {
    margin: 32px 0;
}

.bv-prose figcaption {
    font-size: 0.875rem;
    color: #5A5A5A;
    text-align: center;
    margin-top: 12px;
}

.bv-prose hr {
    border: none;
    border-top: 1px solid #E5E0D5;
    margin: 48px 0;
}

.bv-prose table {
    width: 100%;
    border-collapse: collapse;
    margin: 32px 0;
}

.bv-prose th,
.bv-prose td {
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid #E5E0D5;
}

.bv-prose th {
    font-weight: 600;
    background-color: #F5F5F4;
}

.bv-prose code {
    font-family: 'SF Mono', Monaco, monospace;
    font-size: 0.875em;
    background-color: #F5F5F4;
    padding: 2px 6px;
    border-radius: 4px;
}

.bv-prose pre {
    background-color: #1A1A1A;
    color: #F5F5F4;
    padding: 24px;
    border-radius: 8px;
    overflow-x: auto;
    margin: 32px 0;
}

.bv-prose pre code {
    background: none;
    padding: 0;
    color: inherit;
}

/* Responsive */
@media (max-width: 768px) {
    .bv-page__header {
        padding: 32px 16px;
    }

    .bv-page__title {
        font-size: 1.875rem;
    }

    .bv-page__content {
        padding: 32px 16px 48px;
    }

    .bv-prose {
        font-size: 1rem;
    }

    .bv-prose h2 {
        font-size: 1.5rem;
        margin-top: 36px;
    }

    .bv-prose h3 {
        font-size: 1.25rem;
    }

    .bv-prose blockquote {
        padding: 16px 20px;
        margin: 24px 0;
    }
}

/* WordPress specific: Featured image styling */
.bv-prose .wp-post-image {
    width: 100%;
    margin-bottom: 32px;
}

/* WordPress specific: Gallery styling */
.bv-prose .wp-block-gallery {
    margin: 32px 0;
}

/* WordPress specific: Alignment */
.bv-prose .alignleft {
    float: left;
    margin: 8px 24px 16px 0;
}

.bv-prose .alignright {
    float: right;
    margin: 8px 0 16px 24px;
}

.bv-prose .aligncenter {
    display: block;
    margin: 32px auto;
}

/* Clear floats */
.bv-prose::after {
    content: "";
    display: table;
    clear: both;
}
</style>

<?php get_footer(); ?>
