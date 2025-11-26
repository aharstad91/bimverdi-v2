<?php
/**
 * Min Side - Layout Wrapper End
 * 
 * Closes the layout wrapper opened by minside-layout-start.php
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
            </div><!-- .minside-content__body -->
        </main><!-- .minside-content -->
    </div><!-- .minside-container -->
</div><!-- .minside-wrapper -->

<style>
/* Min Side Layout Wrapper */
.minside-wrapper {
    min-height: calc(100vh - 80px); /* Adjust based on header */
    background: linear-gradient(to bottom, #f9fafb, #ffffff);
}

.minside-container {
    max-width: 1280px;
    margin: 0 auto;
    display: flex;
    min-height: calc(100vh - 80px);
}

/* Main Content Area */
.minside-content {
    flex: 1;
    padding: 2rem 2.5rem;
    min-width: 0; /* Prevent flex overflow */
}

.minside-content__header {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.minside-content__title-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.minside-content__icon {
    font-size: 1.75rem;
    color: #ea580c;
}

.minside-content__title {
    font-size: 1.875rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.minside-content__description {
    margin: 0.5rem 0 0;
    color: #6b7280;
    font-size: 1rem;
}

.minside-content__body {
    /* Content area styling */
}

/* Alert positioning within minside */
.minside-content .wa-alert {
    margin-bottom: 1.5rem;
}
</style>
