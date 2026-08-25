<?php

return [
    'show_warnings' => false,
    'public_path' => null,
    'convert_entities' => true,
    'options' => [
        /**
         * The location of the DOMPDF font directory
         */
        'font_dir' => storage_path('fonts/'),

        /**
         * The location of the DOMPDF font cache directory
         */
        'font_cache' => storage_path('fonts/'),

        /**
         * The location of a temporary directory.
         */
        'temp_dir' => sys_get_temp_dir(),

        /**
         * dompdf's "chroot" — this is a directory where dompdf will restrict access
         */
        'chroot' => realpath(base_path()),

        /**
         * Protocol whitelist
         */
        'allowed_protocols' => [
            "file://" => ["rules" => ["allow_url_fopen" => true]],
            "http://" => ["rules" => ["allow_url_fopen" => true]],
            "https://" => ["rules" => ["allow_url_fopen" => true]],
        ],

        /**
         * Operational options
         */
        'log_output_file' => storage_path('logs/dompdf.html'),
        'enable_font_subsetting' => false,
        'pdf_backend' => 'CPDF',
        'default_media_type' => 'screen',
        'default_paper_size' => 'a4',
        'default_paper_orientation' => 'landscape',
        'default_font' => 'DejaVu Sans',
        'dpi' => 96,
        'enable_php' => false,
        'enable_javascript' => false,
        'enable_remote' => true,
        'font_height_ratio' => 1.1,
        'enable_html5_parser' => true,
    ],
];
