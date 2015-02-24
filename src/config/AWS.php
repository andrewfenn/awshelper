<?php

return [
    /* Use the following options below to directly configure a
    key and secret key. Only needed if you're coding outside
    EC2 or you haven't set up your own API for getting the key
    and secret from outside of amazon.

    If you're not using this option comment it out. */
    //'key'       => '',
    //'secret'    => '',

    'region'   => 'eu-west-1',
    'iam_role' => '',
    'iam_url'  => 'http://169.254.169.254/latest/meta-data/iam/security-credentials/',
];
