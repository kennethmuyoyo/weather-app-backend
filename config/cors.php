<?php

   return [
       'paths' => ['api/*'],
       'allowed_methods' => ['*'],
       'allowed_origins' => array_filter(explode(',', env('ALLOWED_ORIGINS', ''))),
       'allowed_origins_patterns' => [],
       'allowed_headers' => ['*'],
       'exposed_headers' => [],
       'max_age' => 0,
       'supports_credentials' => false,
   ];