<?php

return [
    // Off by default until the Laravel migration and regression tests are complete.
    'enabled' => (bool) env('VIBSIES_ADS_ENABLED', false),
    // Internal accounting postings require an independently verified payment integration.
    'finance_posting_enabled' => (bool) env('VIBSIES_ADS_FINANCE_POSTING_ENABLED', false),
    'publisher_base_share_percent' => 70,
    'founding_share_percent' => 80,
    'founding_placement_limit' => 3,
];
