<?php if (!defined('ABSPATH')) exit; ?>
<h2 class="ds-section__title">Table</h2>
<p class="ds-section__desc">Tabell via <code>data-table</code> template part, eller direkte med <code>.bv-table</code> klasser.</p>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Med footer og caption</h3>
<div style="max-width: 640px;">
<?php get_template_part('parts/components/data-table', null, [
    'columns' => [
        ['key' => 'invoice', 'label' => 'Invoice'],
        ['key' => 'status', 'label' => 'Status'],
        ['key' => 'method', 'label' => 'Method'],
        ['key' => 'amount', 'label' => 'Amount', 'align' => 'right'],
    ],
    'rows' => [
        ['invoice' => 'INV001', 'status' => 'Paid', 'method' => 'Credit Card', 'amount' => '$250.00'],
        ['invoice' => 'INV002', 'status' => 'Pending', 'method' => 'PayPal', 'amount' => '$150.00'],
        ['invoice' => 'INV003', 'status' => 'Unpaid', 'method' => 'Bank Transfer', 'amount' => '$350.00'],
        ['invoice' => 'INV004', 'status' => 'Paid', 'method' => 'Credit Card', 'amount' => '$450.00'],
        ['invoice' => 'INV005', 'status' => 'Paid', 'method' => 'PayPal', 'amount' => '$550.00'],
        ['invoice' => 'INV006', 'status' => 'Pending', 'method' => 'Bank Transfer', 'amount' => '$200.00'],
        ['invoice' => 'INV007', 'status' => 'Unpaid', 'method' => 'Credit Card', 'amount' => '$300.00'],
    ],
    'footer' => ['Total', '', '', '$2,500.00'],
    'caption' => 'A list of your recent invoices.',
]); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Tom tabell</h3>
<div style="max-width: 640px;">
<?php get_template_part('parts/components/data-table', null, [
    'columns' => [
        ['key' => 'name', 'label' => 'Navn'],
        ['key' => 'role', 'label' => 'Rolle'],
        ['key' => 'status', 'label' => 'Status'],
    ],
    'rows' => [],
    'empty_message' => 'Ingen brukere funnet.',
]); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Med element-teller</h3>
<div style="max-width: 640px;">
<?php get_template_part('parts/components/data-table', null, [
    'columns' => [
        ['key' => 'product', 'label' => 'Product'],
        ['key' => 'price', 'label' => 'Price', 'align' => 'right'],
    ],
    'rows' => [
        ['product' => 'Wireless Mouse', 'price' => '$29.99'],
        ['product' => 'Mechanical Keyboard', 'price' => '$129.99'],
        ['product' => 'USB-C Hub', 'price' => '$49.99'],
    ],
    'show_count' => true,
    'total_count' => 12,
]); ?>
</div>

<h3 style="font-size: 15px; font-weight: 600; color: #18181B; margin: 32px 0 12px;">Kode</h3>
<div style="background: #18181B; color: #E4E4E7; padding: 16px; border-radius: 6px; font-size: 13px; font-family: monospace; white-space: pre; overflow-x: auto;">get_template_part('parts/components/data-table', null, [
    'columns' => [
        ['key' => 'invoice', 'label' => 'Invoice'],
        ['key' => 'status', 'label' => 'Status'],
        ['key' => 'amount', 'label' => 'Amount', 'align' => 'right'],
    ],
    'rows' => [
        ['invoice' => 'INV001', 'status' => 'Paid', 'amount' => '$250.00'],
    ],
    'footer'  => ['Total', '', '$2,500.00'],
    'caption' => 'A list of your recent invoices.',
]);

// Eller direkte HTML:
&lt;table class="bv-table"&gt;
    &lt;thead&gt;&lt;tr&gt;&lt;th&gt;Navn&lt;/th&gt;&lt;th class="text-right"&gt;Bel&oslash;p&lt;/th&gt;&lt;/tr&gt;&lt;/thead&gt;
    &lt;tbody&gt;&lt;tr&gt;&lt;td&gt;Ola&lt;/td&gt;&lt;td class="text-right"&gt;$100&lt;/td&gt;&lt;/tr&gt;&lt;/tbody&gt;
    &lt;tfoot&gt;&lt;tr&gt;&lt;td&gt;Total&lt;/td&gt;&lt;td class="text-right"&gt;$100&lt;/td&gt;&lt;/tr&gt;&lt;/tfoot&gt;
&lt;/table&gt;</div>
