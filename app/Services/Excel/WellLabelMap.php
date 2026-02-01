<?php

return [

    /*
    |--------------------------------------------------------------------------
    | INLINE TEXT (label + value in SAME cell)
    |--------------------------------------------------------------------------
    */
    'INLINE_TEXT' => [
        'WELL NAME'    => '/WELL NAME:\s*([A-Z0-9\-]+)/i',
        'PLANNED DAYS' => '/PLANNED DAYS:\s*([\d\.]+)/i',
    ],

    /*
    |--------------------------------------------------------------------------
    | STANDALONE LABEL (value comes AFTER label)
    |--------------------------------------------------------------------------
    */
    'STANDALONE' => [
        'OBJECTIVE',
        'DATE',
        'TD/TARGET',
        'CURRENT DEPTH',
        'CONTR/RIG NO',
        'DAILY FOOTAGE',
        'SPUD IN DATE',
    ],

    /*
    |--------------------------------------------------------------------------
    | COLUMN INLINE (header → numeric value BELOW same column)
    |--------------------------------------------------------------------------
    */
    'COLUMN_INLINE' => [
        'CUM COST',
    ],

];
