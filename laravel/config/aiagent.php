<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | AI Agent Box Title
    |--------------------------------------------------------------------------
    |
    | Fallback heading shown on the floating AI configuration box in the
    | dialer page. Administrators can override this from Settings > General,
    | which stores the value in the `settings` table and is read through
    | config('settings.ai_box_title') ahead of this default.
    |
    */

    'box_title' => 'AI Configuration',

    /*
    |--------------------------------------------------------------------------
    | AI Agent Box Subtitle
    |--------------------------------------------------------------------------
    |
    | Secondary line rendered under the box heading.
    |
    */

    'box_subtitle' => 'Google Gemini Live',

];