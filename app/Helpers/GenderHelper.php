<?php

if (!function_exists('convert_gender_to_standard')) {
    function convert_gender_to_standard(string $gender): ?string
    {
        $genderLower = mb_strtolower(trim($gender), 'UTF-8');
        if ($genderLower === 'laki-laki' || $genderLower === 'laki laki') {
            return 'male';
        } elseif ($genderLower === 'perempuan') {
            return 'female';
        }
        return null;
    }
}

if (!function_exists('convert_gender_to_indonesian')) {
    function convert_gender_to_indonesian(string $gender): ?string
    {
        $genderLower = mb_strtolower(trim($gender), 'UTF-8');

        if ($genderLower === 'male') {
            return 'Laki-Laki';
        } elseif ($genderLower === 'female') {
            return 'Perempuan';
        }
        return null;
    }
}
