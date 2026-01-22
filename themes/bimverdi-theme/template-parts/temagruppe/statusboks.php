<?php
/**
 * Temagruppe Statusboks
 *
 * Displays status badge, meeting frequency, member count, and upcoming event count.
 * Uses borderless section style per UI contract.
 *
 * @package BimVerdi_Theme
 *
 * @param array $args {
 *     @type string   $status        Status: aktiv|planlegging|pause
 *     @type string   $motefrekvens  Meeting frequency text
 *     @type int      $member_count  Number of member companies
 *     @type int      $event_count   Number of upcoming events
 * }
 */

if (!defined('ABSPATH')) exit;

$status = $args['status'] ?? 'aktiv';
$motefrekvens = $args['motefrekvens'] ?? '';
$member_count = $args['member_count'] ?? 0;
$event_count = $args['event_count'] ?? 0;

// Status configuration
$status_config = [
    'aktiv' => [
        'label' => 'Aktiv',
        'color' => '#22C55E', // Green
        'bg' => '#DCFCE7',
    ],
    'planlegging' => [
        'label' => 'Planlegging',
        'color' => '#EAB308', // Yellow
        'bg' => '#FEF9C3',
    ],
    'pause' => [
        'label' => 'Pause',
        'color' => '#6B7280', // Gray
        'bg' => '#F3F4F6',
    ],
];

$current_status = $status_config[$status] ?? $status_config['aktiv'];
?>

<section class="pb-6 border-b border-[#D6D1C6]">
    <h2 class="sr-only">Status</h2>

    <!-- Status Badge -->
    <div class="mb-4">
        <span
            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium"
            style="background-color: <?php echo esc_attr($current_status['bg']); ?>; color: <?php echo esc_attr($current_status['color']); ?>;"
        >
            <span class="w-2 h-2 rounded-full" style="background-color: <?php echo esc_attr($current_status['color']); ?>;"></span>
            <?php echo esc_html($current_status['label']); ?>
        </span>
    </div>

    <!-- Info List -->
    <dl class="space-y-3">
        <?php if ($motefrekvens) : ?>
        <div class="flex justify-between items-start">
            <dt class="text-sm text-[#5A5A5A]">Motefrekvens</dt>
            <dd class="text-sm font-medium text-[#1A1A1A] text-right"><?php echo esc_html($motefrekvens); ?></dd>
        </div>
        <?php endif; ?>

        <div class="flex justify-between items-start">
            <dt class="text-sm text-[#5A5A5A]">Deltakere</dt>
            <dd class="text-sm font-medium text-[#1A1A1A]">
                <?php echo esc_html($member_count); ?> bedrifter
            </dd>
        </div>

        <div class="flex justify-between items-start">
            <dt class="text-sm text-[#5A5A5A]">Kommende moter</dt>
            <dd class="text-sm font-medium text-[#1A1A1A]">
                <?php echo esc_html($event_count); ?> planlagt
            </dd>
        </div>
    </dl>
</section>
