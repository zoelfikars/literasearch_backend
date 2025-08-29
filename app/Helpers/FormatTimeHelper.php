<?php

use Illuminate\Support\Carbon;

if (!function_exists('format_time')) {
    function format_time(string $time)
    {
        return Carbon::parse($time)->translatedFormat('l, d-F-Y H:i');
    }
}
if (!function_exists('format_date')) {
    function format_date(string $time)
    {
        return Carbon::parse($time)->translatedFormat('l, d-F-Y');
    }

}
if (!function_exists('format_identity_date')) {
    function format_identity_date(string $time)
    {
        return Carbon::parse($time)->translatedFormat('d-m-Y');
    }
}
