<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User-Managed Branch Mapping JSON File
    |--------------------------------------------------------------------------
    */
    'branch_mapping_file' => storage_path('app/jewelry_sales_branch_mappings.json'),

    /*
    |--------------------------------------------------------------------------
    | POS Branch Aliases -> Existing `branches.name`
    |--------------------------------------------------------------------------
    |
    | Maps branch labels from the POS export (case-insensitive) to existing
    | `branches.name` records in the database. Branches are never auto-created
    | during import; any unmatched branch aborts the import pre-flight check.
    |
    */
    'branch_aliases' => [
        'gold 1' => 'Branch 1',
        '18k 1' => 'Branch 1',
        'percent 1' => 'Branch 1',

        'gold 2' => 'Branch 2',
        '18k 2' => 'Branch 2',
        'percent 2' => 'Branch 2',

        'gold 3' => 'Branch 3',
        '18k 3' => 'Branch 3',
        'percent 3' => 'Branch 3',

        'osg' => 'Online Sale Gold',
        'osw' => 'Online Sale Gold',
    ],

    /*
    |--------------------------------------------------------------------------
    | Customer Address -> Region / Township Matching Rules
    |--------------------------------------------------------------------------
    |
    | Specific address patterns checked before general region/township matching.
    |
    */
    'address_township_rules' => [
        [
            'pattern' => '/amarapura|အမရပူရ|တောင်မြို့/iu',
            'region' => 'Mandalay',
            'township' => 'Amarapura',
        ],
        [
            'pattern' => '/105\s*[x×\*]\s*79|၁၀၅\s*[x×\*]\s*၇၉/iu',
            'region' => 'Mandalay',
            'township' => 'Chanmyathazi',
        ],
    ],
];
