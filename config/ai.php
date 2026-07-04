<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    | Provider yang digunakan jika tidak ada pilihan eksplisit.
    | Supported: "gemini", "groq"
    */
    'default' => env('AI_DEFAULT_PROVIDER', 'gemini'),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limit' => (int) env('GENERATE_RATE_LIMIT', 5),
    'daily_limit' => (int) env('GENERATE_DAILY_LIMIT', 50),

    /*
    |--------------------------------------------------------------------------
    | Provider Configurations
    |--------------------------------------------------------------------------
    */
    'providers' => [

        'gemini' => [
            'key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
            'timeout' => 60,
            'retry' => 3,
            'retry_sleep' => 2000,
        ],

        'groq' => [
            'key' => env('GROQ_API_KEY'),
            'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
            'timeout' => 60,
            'temperature' => 0.3,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Material Text Limit (characters)
    |--------------------------------------------------------------------------
    | Batas karakter teks materi yang dikirim ke AI untuk menghindari
    | over-limit token pada provider tertentu.
    */
    'material_text_limit' => 8000,

    /*
    |--------------------------------------------------------------------------
    | Supported Providers (untuk validasi input user)
    |--------------------------------------------------------------------------
    */
    'supported_providers' => ['gemini', 'groq'],

];
