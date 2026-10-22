<?php

return ['default' => env('CACHE_STORE', 'file'), 'stores' => ['array' => ['driver' => 'array'], 'file' => ['driver' => 'file', 'path' => storage_path('framework/cache/data')]], 'prefix' => 'social_cache'];
