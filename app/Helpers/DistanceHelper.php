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
