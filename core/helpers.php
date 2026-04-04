<?php

/**
 * Parse duration string ke detik.
 * Contoh: "4 jam" → 14400, "1 hari" → 86400, "30" → 30
 */
function parseDuration(string $input)
{
    $value = trim(strtolower($input));

    if (preg_match('/^(\d+)$/', $value, $m)) {
        return (string)$m[1];
    }

    if (preg_match('/^(\d+)\s*(detik|second|seconds|sec|s)$/', $value, $m)) {
        return (string)$m[1];
    }

    if (preg_match('/^(\d+)\s*(menit|minute|minutes|min|m)$/', $value, $m)) {
        return (string)($m[1] * 60);
    }

    if (preg_match('/^(\d+)\s*(jam|hour|hours|h)$/', $value, $m)) {
        return (string)($m[1] * 3600);
    }

    if (preg_match('/^(\d+)\s*(hari|day|days|d)$/', $value, $m)) {
        return (string)($m[1] * 86400);
    }

    return false;
}

/**
 * Parse expiration string ke format FreeRADIUS.
 * Contoh: "+7 hari" → "07 Jan 2025", "+2 jam" → "07 Jan 2025 14:00:00"
 */
function parseExpiration(string $input)
{
    $value = trim($input);
    if ($value === '') {
        return false;
    }

    if (preg_match('/^\+(\d+)\s*(hari|day|days)$/i', $value, $m)) {
        $days = (int)$m[1];
        if ($days <= 0) {
            return false;
        }
        return date('d M Y', strtotime("+{$days} days"));
    }

    if (preg_match('/^\+(\d+)\s*(jam|hour|hours)$/i', $value, $m)) {
        $hours = (int)$m[1];
        if ($hours <= 0) {
            return false;
        }
        return date('d M Y H:i:s', strtotime("+{$hours} hours"));
    }

    $timestamp = strtotime($value);
    if ($timestamp !== false) {
        return date('d M Y H:i:s', $timestamp);
    }

    return false;
}

/**
 * Parse rate limit string ke format Mikrotik.
 * Contoh: "2 Mbps" → "2M/2M", "512 kbps" → "512k/512k"
 */
function parseRate(string $input)
{
    $value = trim(strtolower($input));
    if ($value === '') {
        return false;
    }

    if (preg_match('/^(\d+)\s*mbps$/i', $value, $m)) {
        return $m[1] . "M/" . $m[1] . "M";
    }

    if (preg_match('/^(\d+)\s*kbps$/i', $value, $m)) {
        return $m[1] . "k/" . $m[1] . "k";
    }

    if (preg_match('/^\d+[kKmMgG]\/\d+[kKmMgG]$/', $input)) {
        return $input;
    }

    return false;
}

/**
 * Kembalikan placeholder teks sesuai attribute.
 */
function valuePlaceholder(string $attribute): string
{
    if ($attribute === 'Session-Timeout' || $attribute === 'Max-All-Session') {
        return '4 jam / 1 hari';
    }
    if ($attribute === 'Expiration') {
        return '+7 hari';
    }
    if ($attribute === 'Mikrotik-Rate-Limit') {
        return '2 Mbps';
    }
    return 'Isi value';
}

/**
 * Format tanggal ke format Indonesia.
 * Contoh: "2024-01-07 14:00:00" → "07 Jan 2024 14:00:00"
 */
function indoDate($date)
{
    if (empty($date)) {
        return "-";
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return "-";
    }

    return date("d M Y H:i:s", $timestamp);
}
