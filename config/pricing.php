<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Recompute interval
    |--------------------------------------------------------------------------
    |
    | A flight's price stays fixed for this many days before the scheduled
    | flights:recompute-prices command is allowed to refresh it again. This
    | implements the "prices change periodically, every few days" rule rather
    | than on every page view.
    |
    */

    'recompute_interval_days' => 3,

    /*
    |--------------------------------------------------------------------------
    | Base price
    |--------------------------------------------------------------------------
    |
    | When a flight has no explicit base price yet, it is derived from the
    | route distance: max(minimum, distance_km * per_km).
    |
    */

    'base' => [
        'minimum' => 5000,
        'per_km' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Occupancy tiers
    |--------------------------------------------------------------------------
    |
    | Factor applied based on how full the flight is (sold / capacity * 100).
    | The first tier whose "min" the occupancy reaches (from the top) wins.
    |
    */

    'occupancy' => [
        ['min' => 90, 'factor' => 1.30, 'label' => '+30% (skoro puno)'],
        ['min' => 70, 'factor' => 1.15, 'label' => '+15% (visoka potražnja)'],
        ['min' => 40, 'factor' => 1.00, 'label' => '(normalna popunjenost)'],
        ['min' => 0, 'factor' => 0.90, 'label' => '−10% (niska potražnja)'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Season
    |--------------------------------------------------------------------------
    |
    | A destination tagged 'summer' is pricier during summer months and cheaper
    | during winter months; a 'winter' destination is the opposite. Anything
    | else (neutral destination or a shoulder month) uses the neutral factor.
    |
    */

    'season' => [
        'summer_months' => [6, 7, 8],
        'winter_months' => [12, 1, 2],
        'in_season_factor' => 1.18,
        'off_season_factor' => 0.90,
        'neutral_factor' => 1.00,
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback class multipliers
    |--------------------------------------------------------------------------
    |
    | Used only when a ticket class row has no multiplier set. Matched against
    | the lowercased class name.
    |
    */

    'class_multipliers' => [
        'ekonom' => 1.0,
        'biznis' => 1.5,
        'prva' => 2.0,
    ],

];
