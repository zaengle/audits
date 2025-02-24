<?php

return [

    'with_authenticated_user' => true,

    // This will automatically add the audits column to the fillable array on all models that use MakesAudits
    // If you want more control over which models set the audits column to fillable, set
    // this to false and add the column to the fillable array on each model manually
    'fillable_by_default' => false,

    'auth_models' => [
        'user' => [
            'model' => \App\User::class,
            'guard' => null,
        ]
    ]
];
