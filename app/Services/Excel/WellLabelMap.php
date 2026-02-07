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
        '24 HRS - SUMMARY', // i have added this;
        'BUDGET',
    ],

    /*
    |--------------------------------------------------------------------------
    | COLUMN INLINE (header → numeric value BELOW same column)
    |--------------------------------------------------------------------------
    */
    'COLUMN_INLINE' => [
        'CUM COST',
    ],


    'record_terminators' => [
        'RIG SUPERVISOR',
        'FORECAST',
    ],



    'row_key_map' => [
        'WELL' => 'WELL NAME',// will contains both of the well name and the field name
        'البئر' => 'WELL NAME',

        'RIG' => 'CONTR/RIG NO',
        'الحفارة'  => 'CONTR/RIG NO',

        'DAY' => 'Days Running',
        'يـوم' => 'Days Running',

        'PROG' => 'DAILY FOOTAGE',
        'إنجاز' => 'DAILY FOOTAGE',

        'PD' => 'CURRENT DEPTH',
        'العمق الحالي' => 'CURRENT DEPTH',
        
        'TD' => 'TD/TARGET',
        'العمق الكلي' => 'TD/TARGET',

        'SUMMARY' => 'SUMMARY',// the long text in the middle

        'CUM.COST' => 'CUM COST',
        'التكلفة التراكمية' => 'CUM COST',
    ],





];
