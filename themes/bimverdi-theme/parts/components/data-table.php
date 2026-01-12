<?php
/**
 * Reusable Data Table Component
 * 
 * Standardized table for listing data on Min Side pages.
 * 
 * Usage:
 * get_template_part('parts/components/data-table', null, [
 *     'columns' => [
 *         ['key' => 'name', 'label' => 'Verktøy', 'width' => 'w-auto'],
 *         ['key' => 'type', 'label' => 'Type', 'width' => 'w-32', 'responsive' => 'hidden sm:table-cell'],
 *         ['key' => 'status', 'label' => 'Status', 'width' => 'w-24'],
 *         ['key' => 'actions', 'label' => 'Handlinger', 'width' => 'w-24', 'align' => 'right'],
 *     ],
 *     'rows' => [
 *         [
 *             'name' => '<div class="flex items-center gap-3">...</div>',
 *             'type' => '<span class="badge">...</span>',
 *             'status' => '<span class="badge-success">Aktiv</span>',
 *             'actions' => '<div class="flex gap-1">...</div>',
 *         ],
 *     ],
 *     'empty_message' => 'Ingen verktøy registrert ennå.',
 *     'show_count' => true,
 *     'total_count' => 10,
 * ]);
 * 
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

$columns = $args['columns'] ?? [];
$rows = $args['rows'] ?? [];
$empty_message = $args['empty_message'] ?? __('Ingen data tilgjengelig.', 'bimverdi');
$show_count = $args['show_count'] ?? false;
$total_count = $args['total_count'] ?? count($rows);

if (empty($columns)) {
    return;
}
?>

<div class="overflow-x-auto">
    <table class="w-full text-left text-sm">
        <thead>
            <tr class="border-b border-[#E5E0D8]">
                <?php foreach ($columns as $column): 
                    $width = $column['width'] ?? '';
                    $responsive = $column['responsive'] ?? '';
                    $align = $column['align'] ?? 'left';
                    $align_class = $align === 'right' ? 'text-right' : ($align === 'center' ? 'text-center' : '');
                ?>
                <th class="py-3 <?php echo $column === reset($columns) ? 'pr-4' : ($column === end($columns) ? 'pl-4' : 'px-4'); ?> text-xs font-medium text-[#5A5A5A] <?php echo esc_attr("$width $responsive $align_class"); ?>">
                    <?php echo esc_html($column['label']); ?>
                </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
            <!-- Empty row with message -->
            <tr>
                <td colspan="<?php echo count($columns); ?>" class="py-8 text-center text-[#5A5A5A]">
                    <?php echo esc_html($empty_message); ?>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($rows as $row): ?>
            <tr class="border-b border-[#E5E0D8] hover:bg-[#FAFAF8] transition-colors group">
                <?php 
                $col_index = 0;
                foreach ($columns as $column): 
                    $key = $column['key'];
                    $responsive = $column['responsive'] ?? '';
                    $align = $column['align'] ?? 'left';
                    $align_class = $align === 'right' ? 'text-right' : ($align === 'center' ? 'text-center' : '');
                    $vertical_align = $column['vertical_align'] ?? 'middle';
                    $vertical_class = $vertical_align === 'top' ? 'align-top' : ($vertical_align === 'bottom' ? 'align-bottom' : 'align-middle');
                ?>
                <td class="py-4 <?php echo $col_index === 0 ? 'pr-4' : ($col_index === count($columns) - 1 ? 'pl-4' : 'px-4'); ?> <?php echo esc_attr("$responsive $align_class $vertical_class"); ?>">
                    <?php 
                    // Allow HTML for complex content (icons, badges, etc.)
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo $row[$key] ?? ''; 
                    ?>
                </td>
                <?php 
                $col_index++;
                endforeach; 
                ?>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <?php if ($show_count && !empty($rows)): ?>
    <!-- Item count -->
    <div class="flex justify-end py-3 text-xs text-[#5A5A5A]">
        <?php 
        $displayed = count($rows);
        if ($displayed === $total_count) {
            printf(_n('Viser %d element', 'Viser %d elementer', $displayed, 'bimverdi'), $displayed);
        } else {
            printf(__('Viser %d av %d elementer', 'bimverdi'), $displayed, $total_count);
        }
        ?>
    </div>
    <?php endif; ?>
</div>
