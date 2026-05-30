<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Charts Loader Version
    |--------------------------------------------------------------------------
    |
    | The version of the Google Charts library to load. Use 'current' for the
    | latest stable release, 'upcoming' for the candidate release, or a frozen
    | version string (e.g. '51') to pin a specific release.
    |
    */

    'version' => 'current',

    /*
    |--------------------------------------------------------------------------
    | Default Package
    |--------------------------------------------------------------------------
    |
    | The Google Charts package loaded by default when a chart does not specify
    | its own. Most core charts (line, bar, column, pie, ...) use 'corechart'.
    |
    */

    'default_package' => 'corechart',

    /*
    |--------------------------------------------------------------------------
    | Language
    |--------------------------------------------------------------------------
    |
    | The locale used when loading the chart library. Controls formatting of
    | numbers, dates and built-in labels. Example: 'en', 'fr', 'de'.
    |
    */

    'language' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Responsive
    |--------------------------------------------------------------------------
    |
    | When true, charts are redrawn on window resize so they adapt to their
    | container width.
    |
    */

    'responsive' => true,

    /*
    |--------------------------------------------------------------------------
    | Loader URL
    |--------------------------------------------------------------------------
    |
    | The URL of the Google Charts loader script.
    |
    */

    'loader_url' => 'https://www.gstatic.com/charts/loader.js',

    /*
    |--------------------------------------------------------------------------
    | Google Maps API Key
    |--------------------------------------------------------------------------
    |
    | Required by some chart types (e.g. Map / GeoChart with markers). Leave
    | null if you are not using map-based charts.
    |
    */

    'maps_api_key' => null,

    /*
    |--------------------------------------------------------------------------
    | Default Chart Options
    |--------------------------------------------------------------------------
    |
    | Options merged into every chart unless overridden per-chart. Anything the
    | Google Charts configuration accepts can be set here.
    |
    */

    'default_options' => [
        'height' => 400,
        'width' => '100%',
    ],

];
