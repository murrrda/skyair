<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Automatic risk analysis
    |--------------------------------------------------------------------------
    |
    | After an incident is recorded, each responsible employee is re-evaluated.
    | If they accumulate at least "threshold" incidents within the trailing
    | "window_days" period, an active risk period (a "break") is opened for
    | them. Tune these values to make the flagging stricter or looser.
    |
    */

    'analysis' => [
        'window_days' => 30,
        'threshold' => 3,
        'pause_days' => 30,
    ],

];
