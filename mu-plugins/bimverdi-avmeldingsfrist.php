<?php
/**
 * BIM Verdi - Dynamisk Avmeldingsfrist
 * 
 * Avmeldingsfristen følger påmeldingsfristen (ACF «pameldingsfrist»,
 * satt av Bård). Beslutning 02.10.2026 — erstatter 24/48-timersregelen.
 * Uten påmeldingsfrist stenger avmelding ved arrangementets start.
 * 
 * @package BIM_Verdi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Tolk en klokkeslett-streng («2026-10-08 13:00:00») som norsk tid.
 *
 * WordPress setter PHP-tidssonen til UTC, så strtotime() alene leser
 * norske tider to timer for sent (én time om vinteren).
 *
 * @param string $datetime Dato/tid slik den er lagret i ACF
 * @return int Unix-tidsstempel, 0 hvis strengen ikke kan tolkes
 */
function bimverdi_local_timestamp($datetime) {
    if (!$datetime) {
        return 0;
    }
    try {
        return (new DateTimeImmutable((string) $datetime, wp_timezone()))->getTimestamp();
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get dynamic cancellation deadline for an arrangement
 * 
 * @param int $arrangement_id Post ID of the arrangement
 * @return array {
 *     @type int    $timestamp  Unix timestamp for deadline
 *     @type string $formatted  Human-readable deadline
 *     @type bool   $can_cancel Whether cancellation is still allowed
 *     @type int    $hours      Timer mellom frist og start (informativt)
 *     @type string $format     Event format (fysisk/digitalt/hybrid)
 * }
 */
function bimverdi_get_avmeldingsfrist($arrangement_id) {
    $dato = get_field('arrangement_dato', $arrangement_id);
    $tid_start = get_field('tidspunkt_start', $arrangement_id);
    $format = get_field('arrangement_type', $arrangement_id);
    $pameldingsfrist = get_field('pameldingsfrist', $arrangement_id);

    $result = array(
        'timestamp' => 0,
        'formatted' => '',
        'can_cancel' => false,
        'hours' => 0,
        'format' => $format,
    );

    $event_datetime = ($dato && $tid_start) ? bimverdi_local_timestamp($dato . ' ' . $tid_start) : 0;

    // Frist = påmeldingsfristen; uten den stenger avmelding ved start.
    $deadline = $pameldingsfrist ? bimverdi_local_timestamp($pameldingsfrist) : $event_datetime;
    if (!$deadline) {
        return $result;
    }

    $result['timestamp'] = $deadline;
    $result['formatted'] = wp_date('j. F Y \k\l. H:i', $deadline);
    $result['can_cancel'] = time() < $deadline;
    $result['hours'] = $event_datetime ? max(0, (int) round(($event_datetime - $deadline) / 3600)) : 0;

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

    if ($frist['can_cancel']) {
        return sprintf(
            '<span class="text-gray-600">Avmeldingsfrist: <strong>%s</strong></span>',
            esc_html($frist['formatted'])
        );
    }

    return sprintf(
        '<span class="text-red-600">Avmeldingsfristen (%s) har passert</span>',
        esc_html($frist['formatted'])
    );
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
