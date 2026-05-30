@php
    /**
     * Optional: include this once (e.g. in your layout's <head>) to preload the
     * Google Charts loader so individual charts don't each inject it.
     *
     *   @include('google-charts::scripts')
     *
     * @var array|null $config
     */
    $loaderUrl = $config['loader_url'] ?? config('google-charts.loader_url', 'https://www.gstatic.com/charts/loader.js');
@endphp
@once
    <script type="text/javascript" src="{{ $loaderUrl }}"></script>
    <script type="text/javascript">
        window.googleChartsLaravel = window.googleChartsLaravel || { queue: [], loaderInjected: true };
        window.googleChartsLaravel.loaderInjected = true;
    </script>
@endonce
