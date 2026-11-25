<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Deployed Version
    |--------------------------------------------------------------------------
    |
    | Shown by the /health endpoint so every deploy can be traced to a commit.
    | APP_VERSION is set by CI/Docker builds; Render provides RENDER_GIT_COMMIT.
    |
    */

    'version' => env('APP_VERSION', env('RENDER_GIT_COMMIT', 'dev')),

];
