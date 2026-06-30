<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Crew workload limits
    |--------------------------------------------------------------------------
    |
    | The weekly flight-hour cap mirrors CrewAssignmentService — it is the
    | reference the performance report uses to flag over- and near-limit crew.
    | An employee at >= near_limit_ratio of the cap is "near the limit"; above
    | the cap is "over the limit" (prekoračenje).
    |
    */

    'weekly_hours_cap' => 60,

    'near_limit_ratio' => 0.9,

];
