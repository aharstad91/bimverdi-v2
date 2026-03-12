<?php if (!defined('ABSPATH')) exit; ?>
<h2 class="ds-section__title">Avatar</h2>
<p class="ds-section__desc">Runde avatarer med bilde, initialer eller grupper via <code>bimverdi_avatar()</code>.</p>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Bilde</h3>
<div style="display: flex; gap: 16px; align-items: center; margin-bottom: 32px;">
    <?php bimverdi_avatar(['src' => 'https://i.pravatar.cc/96?img=1', 'alt' => 'Anna Berg', 'size' => 'sm']); ?>
    <?php bimverdi_avatar(['src' => 'https://i.pravatar.cc/96?img=2', 'alt' => 'Erik Olsen']); ?>
    <?php bimverdi_avatar(['src' => 'https://i.pravatar.cc/128?img=3', 'alt' => 'Maria Lund', 'size' => 'lg']); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Initialer (fallback)</h3>
<div style="display: flex; gap: 16px; align-items: center; margin-bottom: 32px;">
    <?php bimverdi_avatar(['initials' => 'AH', 'size' => 'sm']); ?>
    <?php bimverdi_avatar(['initials' => 'BK', 'color' => '#6366F1']); ?>
    <?php bimverdi_avatar(['initials' => 'ML', 'color' => '#DC2626', 'size' => 'lg']); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Status-badge</h3>
<div style="display: flex; gap: 16px; align-items: center; margin-bottom: 32px;">
    <?php bimverdi_avatar(['initials' => 'AH', 'badge' => 'online']); ?>
    <?php bimverdi_avatar(['initials' => 'BK', 'color' => '#6366F1', 'badge' => 'busy']); ?>
    <?php bimverdi_avatar(['initials' => 'EO', 'color' => '#0EA5E9', 'badge' => 'away']); ?>
    <?php bimverdi_avatar(['initials' => 'ML', 'color' => '#DC2626', 'badge' => 'offline']); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Avatar Group</h3>
<div style="display: flex; flex-direction: column; gap: 24px; margin-bottom: 32px;">
    <div>
        <div style="font-size: 13px; color: #71717A; margin-bottom: 8px;">max=3, 5 avatarer</div>
        <?php bimverdi_avatar_group([
            ['src' => 'https://i.pravatar.cc/96?img=4', 'alt' => 'User 1'],
            ['initials' => 'AH'],
            ['initials' => 'BK', 'color' => '#6366F1'],
            ['initials' => 'EO', 'color' => '#0EA5E9'],
            ['initials' => 'ML', 'color' => '#DC2626'],
        ], ['max' => 3]); ?>
    </div>
    <div>
        <div style="font-size: 13px; color: #71717A; margin-bottom: 8px;">Stor størrelse</div>
        <?php bimverdi_avatar_group([
            ['src' => 'https://i.pravatar.cc/128?img=5', 'alt' => 'User A'],
            ['src' => 'https://i.pravatar.cc/128?img=6', 'alt' => 'User B'],
            ['initials' => 'CL', 'color' => '#DC2626'],
            ['initials' => 'DK', 'color' => '#16A34A'],
        ], ['size' => 'lg', 'max' => 4]); ?>
    </div>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Med Item-komponent</h3>
<div style="max-width: 400px; margin-bottom: 32px;">
    <?php bimverdi_item_group('outline'); ?>
        <?php bimverdi_item([
            'avatar'       => 'AH',
            'avatar_color' => '#18181B',
            'title'        => 'Andreas Harstad',
            'description'  => 'Hovedkontakt',
            'meta'         => 'Partner',
        ]); ?>
        <?php bimverdi_item([
            'avatar'       => 'BK',
            'avatar_color' => '#6366F1',
            'title'        => 'Bente Karlsen',
            'description'  => 'Tilleggskontakt',
            'badge'        => ['text' => 'Aktiv', 'color' => 'green'],
        ]); ?>
    <?php bimverdi_item_group_end(); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Størrelser</h3>
<div style="display: flex; gap: 12px; align-items: center; margin-bottom: 32px;">
    <div style="text-align: center;">
        <?php bimverdi_avatar(['initials' => 'SM', 'size' => 'sm']); ?>
        <div style="font-size: 11px; color: #71717A; margin-top: 4px;">sm (32px)</div>
    </div>
    <div style="text-align: center;">
        <?php bimverdi_avatar(['initials' => 'DF']); ?>
        <div style="font-size: 11px; color: #71717A; margin-top: 4px;">default (40px)</div>
    </div>
    <div style="text-align: center;">
        <?php bimverdi_avatar(['initials' => 'LG', 'size' => 'lg']); ?>
        <div style="font-size: 11px; color: #71717A; margin-top: 4px;">lg (48px)</div>
    </div>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Kode</h3>
<div style="background: #18181B; color: #E4E4E7; padding: 16px; border-radius: 6px; font-size: 13px; font-family: monospace; white-space: pre; overflow-x: auto;">// Bilde
bimverdi_avatar([
    'src' => '/uploads/avatar.jpg',
    'alt' => 'Andreas Harstad',
]);

// Initialer med farge
bimverdi_avatar([
    'initials' => 'AH',
    'color'    => '#6366F1',
    'badge'    => 'online',
]);

// Gruppe
bimverdi_avatar_group([
    ['src' => '/img/1.jpg', 'alt' => 'User 1'],
    ['initials' => 'AH'],
    ['initials' => 'BK', 'color' => '#DC2626'],
], ['max' => 3, 'size' => 'lg']);</div>
