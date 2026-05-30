@php
    /**
     * Optional. Include this once (for example in your layout's <head>) to define the
     * chart runtime early and preload the Google Charts loader before any chart draws:
     *
     *   @include('google-charts::scripts')
     *
     * Charts work without it; this is purely a performance hint for pages with many charts.
     */
@endphp
@include('google-charts::runtime')
@once
    <script type="text/javascript">window.GoogleChartsLaravel.preload();</script>
@endonce
