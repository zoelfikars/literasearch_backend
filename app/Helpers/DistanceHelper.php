<?php
if (!function_exists('format_distance')) {
    function format_distance($distanceKm)
    {
        if ($distanceKm < 1) {
            $meters = round($distanceKm * 1000);
            return $meters . ' M';
        } else {
            $roundedKm = round($distanceKm, 2);
            return $roundedKm . ' KM';
        }
    }
}

if (!function_exists('format_distance_m')) {
    function format_distance_m(?float $meters): ?string
    {
        if ($meters === null) return null;
        if ($meters < 1000) {
            return (int) round($meters) . ' M';
        }
        return number_format($meters / 1000, 2) . ' KM';
    }
}
