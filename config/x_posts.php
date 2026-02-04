<?php

return [

    /*
    |--------------------------------------------------------------------------
    | X Posts Spreadsheet
    |--------------------------------------------------------------------------
    |
    | Path to the Excel file used for importing/refining X post content.
    | Can be absolute or relative to the project root.
    |
    */

    'spreadsheet_path' => env('X_POSTS_SPREADSHEET_PATH', resource_path('xslx/MoW_X_Posts_2026_CLEANED.xlsx')),

];
