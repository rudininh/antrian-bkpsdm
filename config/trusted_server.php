<?php

return [
    'hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TRUSTED_SERVER_HOSTS', 'desktop-904qfme')),
    ))),
    'user_email' => env('TRUSTED_SERVER_USER_EMAIL', 'admin@bkpsdm.test'),
];
