<?php
/**
 * BIM Verdi - Dynamisk Avmeldingsfrist
 * 
 * Beregner avmeldingsfrist basert på arrangementformat:
 * - Fysisk: 48 timer før
 * - Digitalt: 24 timer før
 * - Hybrid: 48 timer før
 * 
 * @package BIM_Verdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get dynamic cancellation deadline for an arrangement
 * 
 * @param int $arrangement_id Post ID of the arrangement
 * @return array {
 *     @type int    $timestamp  Unix timestamp for deadline
 *     @type string $formatted  Human-readable deadline
 *     @type bool   $can_cancel Whether cancellation is still allowed
 *     @type int    $hours      Hours before event
 *     @type string $format     Event format (fysisk/digitalt/hybrid)
 * }
 */
function bimverdi_get_avmeldingsfrist($arrangement_id) {
    // Get event details
    $dato = get_field('arrangement_dato', $arrangement_id);
    $tid_start = get_field('tidspunkt_start', $arrangement_id);
    $format = get_field('arrangement_format', $arrangement_id);
    $manual_frist = get_field('pamelding_frist', $arrangement_id);
    
    // Default result
    $result = array(
        'timestamp' => 0,
        'formatted' => '',
        'can_cancel' => false,
        'hours' => 0,
        'format' => $format,
    );
    
    if (!$dato || !$tid_start) {
        return $result;
    }
    
    // Parse event datetime
    $event_datetime = strtotime($dato . ' ' . $tid_start);
    
    if (!$event_datetime) {
        return $result;
    }
    
    // If manual deadline is set, use that
    if ($manual_frist) {
        $deadline = strtotime($manual_frist . ' 23:59:59');
        $result['timestamp'] = $deadline;
        $result['formatted'] = date_i18n('j. F Y', $deadline);
        $result['can_cancel'] = time() < $deadline;
        $result['hours'] = round(($event_datetime - $deadline) / 3600);
        return $result;
    }
    
    // Calculate dynamic deadline based on format
    switch ($format) {
        case 'digitalt':
            $hours_before = 24;
            break;
        case 'fysisk':
        case 'hybrid':
        default:
            $hours_before = 48;
            break;
    }
    
    $deadline = $event_datetime - ($hours_before * 3600);
    
    $result['timestamp'] = $deadline;
    $result['formatted'] = date_i18n('j. F Y \k\l. H:i', $deadline);
    $result['can_cancel'] = time() < $deadline;
    $result['hours'] = $hours_before;
    
    return $result;
}

/**
 * Get formatted message about cancellation deadline
 * 
 * @param int $arrangement_id
 * @return string HTML message
 */
function bimverdi_get_avmeldingsfrist_message($arrangement_id) {
    $frist = bimverdi_get_avmeldingsfrist($arrangement_id);
    
    if (!$frist['timestamp']) {
        return '';
    }
    
    $format_labels = array(
        'fysisk' => 'fysiske arrangementer',
        'digitalt' => 'digitale arrangementer',
        'hybrid' => 'hybridarrangementer',
    );
    
    $format_label = $format_labels[$frist['format']] ?? 'arrangementer';
    
    if ($frist['can_cancel']) {
        return sprintf(
            '<span class="text-gray-600">Avmeldingsfrist: <strong>%s</strong> (%d timer før for %s)</span>',
            esc_html($frist['formatted']),
            $frist['hours'],
            esc_html($format_label)
        );
    } else {
        return sprintf(
            '<span class="text-red-600">Avmeldingsfristen (%s) har passert</span>',
            esc_html($frist['formatted'])
        );
    }
}

/**
 * Check if user can cancel registration
 * 
 * @param int $arrangement_id
 * @return bool
 */
function bimverdi_can_cancel_registration($arrangement_id) {
    $frist = bimverdi_get_avmeldingsfrist($arrangement_id);
    return $frist['can_cancel'];
}

/**
 * Get time remaining until cancellation deadline
 * 
 * @param int $arrangement_id
 * @return string Human-readable time remaining
 */
function bimverdi_get_avmelding_time_remaining($arrangement_id) {
    $frist = bimverdi_get_avmeldingsfrist($arrangement_id);
    
    if (!$frist['timestamp'] || !$frist['can_cancel']) {
        return '';
    }
    
    $remaining = $frist['timestamp'] - time();
    
    if ($remaining <= 0) {
        return 'Frist utløpt';
    }
    
    $days = floor($remaining / 86400);
    $hours = floor(($remaining % 86400) / 3600);
    
    if ($days > 0) {
        return sprintf('%d dager og %d timer igjen', $days, $hours);
    } elseif ($hours > 0) {
        $minutes = floor(($remaining % 3600) / 60);
        return sprintf('%d timer og %d minutter igjen', $hours, $minutes);
    } else {
        $minutes = floor($remaining / 60);
        return sprintf('%d minutter igjen', $minutes);
    }
}
