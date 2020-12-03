<?php

return [

    'with_authenticated_user' => true,

    'auth_models' => [
        'user' => [
            'model' => \App\User::class,
            'guard' => null,
        ]
    ]
];
