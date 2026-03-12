<?php
/**
 * BIMVerdi Data Table Component (shadcn-inspired)
 *
 * Standardized table for listing data on Min Side pages.
 *
 * Usage:
 * get_template_part('parts/components/data-table', null, [
 *     'columns' => [
 *         ['key' => 'invoice', 'label' => 'Invoice', 'class' => 'w-[100px]'],
 *         ['key' => 'status', 'label' => 'Status'],
 *         ['key' => 'method', 'label' => 'Method'],
 *         ['key' => 'amount', 'label' => 'Amount', 'align' => 'right'],
 *     ],
 *     'rows' => [
 *         ['invoice' => 'INV001', 'status' => 'Paid', 'method' => 'Credit Card', 'amount' => '$250.00'],
 *     ],
 *     'footer' => ['Total', '', '', '$2,500.00'],
 *     'caption' => 'A list of your recent invoices.',
 *     'empty_message' => 'Ingen data tilgjengelig.',
 *     'show_count' => true,
 *     'total_count' => 10,
 * ]);
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

$columns = $args['columns'] ?? [];
$rows = $args['rows'] ?? [];
$footer = $args['footer'] ?? [];
$caption = $args['caption'] ?? '';
$empty_message = $args['empty_message'] ?? __('Ingen data tilgjengelig.', 'bimverdi');
$show_count = $args['show_count'] ?? false;
$total_count = $args['total_count'] ?? count($rows);

if (empty($columns)) {
    return;
}
?>

<div class="bv-table-wrapper">
    <table class="bv-table">
        <?php if ($caption): ?>
        <caption><?php echo esc_html($caption); ?></caption>
        <?php endif; ?>

        <thead>
            <tr>
                <?php foreach ($columns as $column):
                    $align = ($column['align'] ?? 'left') === 'right' ? ' text-right' : '';
                    $responsive = isset($column['responsive']) ? ' ' . $column['responsive'] : '';
                    $width = isset($column['class']) ? ' style="width:' . esc_attr($column['class']) . '"' : '';
                ?>
                <th class="<?php echo esc_attr(trim($align . $responsive)); ?>"><?php echo esc_html($column['label']); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>

        <tbody>
            <?php if (empty($rows)): ?>
            <tr>
                <td colspan="<?php echo count($columns); ?>" style="padding: 32px 8px; text-align: center; color: #71717A;">
                    <?php echo esc_html($empty_message); ?>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($rows as $row): ?>
            <tr>
                <?php
                $col_index = 0;
                foreach ($columns as $column):
                    $key = $column['key'];
                    $align = ($column['align'] ?? 'left') === 'right' ? ' text-right' : '';
                    $responsive = isset($column['responsive']) ? ' ' . $column['responsive'] : '';
                    $bold = ($col_index === 0) ? ' font-medium' : '';
                ?>
                <td class="<?php echo esc_attr(trim($align . $responsive . $bold)); ?>">
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

        <?php if (!empty($footer)): ?>
        <tfoot>
            <tr>
                <?php foreach ($footer as $i => $cell):
                    $align = ($columns[$i]['align'] ?? 'left') === 'right' ? ' text-right' : '';
                ?>
                <td class="<?php echo esc_attr(trim($align)); ?>"><?php echo esc_html($cell); ?></td>
                <?php endforeach; ?>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>

    <?php if ($show_count && !empty($rows)): ?>
    <div style="display: flex; justify-content: flex-end; padding: 12px 0; font-size: 13px; color: #71717A;">
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
