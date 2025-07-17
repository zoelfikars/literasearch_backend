<?php

if (!function_exists('maskEmail')) {
    function maskEmail(string $email)
    {
        $explode = explode('@', $email);
        $name = substr($explode[0], 0, 3) . str_repeat('*', max(strlen($explode[0]) - 3, 0));
        return $name . '@' . $explode[1];
    }
}
