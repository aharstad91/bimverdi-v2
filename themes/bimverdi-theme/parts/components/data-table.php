<?php
/**
 * BIMVerdi Data Table Component (shadcn-inspired)
 *
 * Standardized table with rich column types for listing data.
 *
 * Column types:
 *   (default)  Plain text/HTML
 *   'avatar'   Image with initials fallback. Row value: ['src'=>url, 'initials'=>'AB', 'label'=>'Name']
 *   'badge'    Pill badge. Row value: string or ['label'=>'Text', 'variant'=>'default']
 *   'link'     Clickable text. Row value: ['label'=>'Text', 'href'=>'/url', 'external'=>false]
 *   'action'   Icon button (chevron). Row value: ['href'=>'/url']
 *
 * Usage:
 * get_template_part('parts/components/data-table', null, [
 *     'columns' => [
 *         ['key' => 'logo', 'label' => '', 'type' => 'avatar', 'width' => '48px'],
 *         ['key' => 'name', 'label' => 'Navn', 'type' => 'link'],
 *         ['key' => 'type', 'label' => 'Type', 'type' => 'badge'],
 *         ['key' => 'amount', 'label' => 'Beløp', 'align' => 'right'],
 *         ['key' => 'action', 'label' => '', 'type' => 'action', 'width' => '48px'],
 *     ],
 *     'rows' => [
 *         [
 *             'logo'   => ['src' => '/img/logo.png', 'initials' => 'AB'],
 *             'name'   => ['label' => 'Acme Corp', 'href' => '/foretak/acme/'],
 *             'type'   => 'Programvare',
 *             'amount' => '250 kr',
 *             'action' => ['href' => '/foretak/acme/'],
 *         ],
 *     ],
 *     'footer' => ['Total', '', '', '2 500 kr'],
 *     'caption' => 'A list of items.',
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
                    $width_style = isset($column['width']) ? ' style="width:' . esc_attr($column['width']) . '"' : '';
                ?>
                <th class="<?php echo esc_attr(trim($align . $responsive)); ?>"<?php echo $width_style; ?>><?php echo esc_html($column['label']); ?></th>
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
                    $type = $column['type'] ?? 'default';
                    $align = ($column['align'] ?? 'left') === 'right' ? ' text-right' : '';
                    $responsive = isset($column['responsive']) ? ' ' . $column['responsive'] : '';
                    $bold = ($col_index === 0 && $type === 'default') ? ' font-medium' : '';
                    $value = $row[$key] ?? '';
                ?>
                <td class="<?php echo esc_attr(trim($align . $responsive . $bold)); ?>">
                    <?php
                    switch ($type):
                        case 'avatar':
                            // Value: ['src' => url, 'initials' => 'AB', 'icon' => 'lucide-name', 'color' => '#hex']
                            $av = is_array($value) ? $value : ['initials' => $value];
                            $src = $av['src'] ?? '';
                            $initials = $av['initials'] ?? '';
                            $icon = $av['icon'] ?? '';
                            $color = $av['color'] ?? '#F5F5F4';
                            $bg_style = ($src || $icon || $color === '#F5F5F4') ? '' : 'background-color:' . esc_attr($color) . '; color: #fff;';
                            ?>
                            <div class="bv-table-avatar" <?php if ($bg_style) echo 'style="' . $bg_style . '"'; ?>>
                                <?php if ($src): ?>
                                <img src="<?php echo esc_url($src); ?>" alt="">
                                <?php elseif ($icon):
                                    if (function_exists('bimverdi_get_icon_svg')) {
                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                        echo bimverdi_get_icon_svg($icon, 18);
                                    }
                                ?>
                                <?php else: ?>
                                <span><?php echo esc_html($initials); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php
                            break;

                        case 'badge':
                            // Value: string or ['label' => 'Text', 'variant' => 'default|muted']
                            $badge = is_array($value) ? $value : ['label' => $value];
                            $label = $badge['label'] ?? '';
                            $icon = $badge['icon'] ?? '';
                            if ($label || $icon):
                            ?>
                            <span class="bv-table-badge"><?php
                                if ($icon) echo '<span class="bv-table-badge-icon">' . $icon . '</span>';
                                echo esc_html($label);
                            ?></span>
                            <?php
                            endif;
                            break;

                        case 'link':
                            // Value: ['label' => 'Text', 'href' => '/url', 'external' => false]
                            $link = is_array($value) ? $value : ['label' => $value];
                            $label = $link['label'] ?? '';
                            $href = $link['href'] ?? '';
                            $external = $link['external'] ?? false;
                            $target = $external ? ' target="_blank" rel="noopener noreferrer"' : '';
                            if ($href): ?>
                            <a href="<?php echo esc_url($href); ?>" class="bv-table-link"<?php echo $target; ?>><?php echo esc_html($label); ?><?php if ($external) echo ' ↗'; ?></a>
                            <?php else:
                                echo esc_html($label);
                            endif;
                            break;

                        case 'action':
                            // Value: ['href' => '/url'] or string href
                            $href = is_array($value) ? ($value['href'] ?? '') : $value;
                            if ($href): ?>
                            <a href="<?php echo esc_url($href); ?>" class="bv-table-action" title="Se detaljer">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                            </a>
                            <?php endif;
                            break;

                        default:
                            // Plain text/HTML (backwards compatible)
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo $value;
                            break;
                    endswitch;
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
