<?php
// Fill these in after running: php generate-keys.php
// (or set them as environment variables in production)
return [
    'vapid' => [
        'subject'    => getenv('VAPID_SUBJECT') ?: 'mailto:mahabubcmt8@gmail.com',
        'publicKey'  => getenv('VAPID_PUBLIC_KEY') ?: 'BHVmxznuTGR5uuPIHlYmRSLiagqKnjxg7vqMBwq_00a1hQFIy6P0hE870SGFEuyT_8mJhpo-rRgdT2UUvrrEixA',
        'privateKey' => getenv('VAPID_PRIVATE_KEY') ?: 'Rf2kDFuUKgiG1G1FexC6eBdvvrEYLcmQVRWwELXF3Ng',
    ],
    'db_path' => __DIR__ . '/data/push.sqlite',
];
