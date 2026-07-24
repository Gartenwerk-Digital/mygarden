<?php

return [

    'images' => [
        /*
         * The filesystem disk used for all private image uploads
         * (Timeline, Garden, Catalog in later phases).
         *
         * Supported: any disk defined in config/filesystems.php.
         *
         * Note: changing this value in production does not migrate existing
         * files automatically. Objects must be moved before switching the disk.
         */
        'disk' => env('MYGARDEN_IMAGE_DISK', 'local'),
    ],

];
