<?php

return [
    'google_matrix' => [
        'per_minute' => env('RATE_MATRIX_PER_MIN', 30),
        'per_hour'   => env('RATE_MATRIX_PER_HOUR', 1200),
    ],
];
