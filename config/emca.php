<?php

return [

    'admin_emails' => array_values(array_filter(array_map(
        trim(...),
        explode(',', (string) env('EMCA_ADMIN_EMAILS', '')),
    ))),

    'phone' => [
        'country_code' => '+255',
    ],

    'super_admin' => [
        'name' => env('EMCA_SUPER_ADMIN_NAME', 'Super Admin'),
        'phone' => env('EMCA_SUPER_ADMIN_PHONE', '+255692586046'),
        'password' => env('EMCA_SUPER_ADMIN_PASSWORD', 'Chima@97!'),
    ],

    'support_email' => env('EMCA_SUPPORT_EMAIL', 'emca@emca.tech'),

    'support_phone' => env('EMCA_SUPPORT_PHONE', '+255 749 719 998'),

    'support_address' => env('EMCA_SUPPORT_ADDRESS', 'Ben Bella Street, Moshi'),

    'legal_updated_at' => '2026-11-02',

    'wallet' => [
        'minimum_top_up' => (int) env('EMCA_MINIMUM_TOP_UP', 1000),
        'maximum_top_up' => (int) env('EMCA_MAXIMUM_TOP_UP', 10000000),
    ],

    'payment_accounts' => [
        'mpesa' => env('EMCA_PAY_MPESA', 'Lipa Namba 5123 4567 (EmCa Technologies)'),
        'airtel_money' => env('EMCA_PAY_AIRTEL', 'Lipa Namba 1234 5678 (EmCa Technologies)'),
        'mixx_by_yas' => env('EMCA_PAY_MIXX', 'Lipa Namba 9876 5432 (EmCa Technologies)'),
        'halopesa' => env('EMCA_PAY_HALOPESA', ''),
        'bank' => env('EMCA_PAY_BANK', 'CRDB Bank, A/C 0150 0000 0000 (EmCa Technologies)'),
    ],

    'locales' => [
        'sw' => 'Kiswahili',
        'en' => 'English',
    ],

    'plans' => [
        [
            'name' => 'Gathering',
            'max' => 100,
            'price' => 1500,
            'summary' => 'Birthdays, meetings, and smaller family lists.',
            'includes' => [
                'Card design from the template library',
                'WhatsApp and SMS share links',
                'RSVP tracking',
                'One-time check-in codes',
            ],
        ],
        [
            'name' => 'Celebration',
            'max' => 500,
            'price' => 2000,
            'summary' => 'Weddings, sendoffs, and kitchen parties.',
            'includes' => [
                'Everything in Gathering',
                'Pledge and contribution records',
                'Budget and task boards',
                'Vendor shortlist for the event',
            ],
        ],
        [
            'name' => 'Grand',
            'max' => null,
            'price' => 2500,
            'summary' => 'Galas and guest lists above 500.',
            'includes' => [
                'Everything in Celebration',
                'Reminder and thank-you message logs',
                'Live check-in for door teams',
                'A walkthrough with the Emca team',
            ],
        ],
    ],

];
