@php
    /**
     * @var \Premmohantyagi\GoogleCharts\Contracts\Chart $chart
     * @var array $config
     */
    $id         = $chart->getId();
    $type       = $chart->getType();
    $package    = $chart->getPackage();
    $language   = $chart->getLanguage();
    $options    = $chart->getOptions();
    $events     = method_exists($chart, 'getEvents') ? $chart->getEvents() : [];

    // Encode payloads safely for an inline <script> context.
    $jsonFlags    = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;
    $dataJson     = json_encode($chart->getDataTable(), $jsonFlags);
    $optionsJson  = json_encode((object) $options, $jsonFlags) ?: '{}';
    $packagesJson = json_encode([$package], $jsonFlags);
    $versionJson  = json_encode($config['version'] ?? 'current', $jsonFlags);
    $languageJson = json_encode($language, $jsonFlags);
    $loaderUrl    = json_encode($config['loader_url'] ?? 'https://www.gstatic.com/charts/loader.js', $jsonFlags);
    $responsive   = ! empty($config['responsive']);

    // Container dimensions: numbers become px, strings (e.g. "100%") are used as-is.
    $cssDimension = function ($value, $fallback) {
        $value = $value ?? $fallback;
        return is_numeric($value) ? $value . 'px' : $value;
    };
    $width  = $cssDimension($options['width'] ?? null, '100%');
    $height = $cssDimension($options['height'] ?? null, '400px');
@endphp
<div id="{{ $id }}" class="google-chart" style="width: {{ $width }}; height: {{ $height }};"></div>
<script type="text/javascript">
(function () {
    var elementId = {!! json_encode($id, $jsonFlags) !!};

    function draw() {
        var container = document.getElementById(elementId);
        if (!container) { return; }

        var data = new google.visualization.DataTable({!! $dataJson !!});
        var options = {!! $optionsJson !!};
        var chart = new google.visualization.{{ $type }}(container);

        @foreach ($events as $event => $handler)
            google.visualization.events.addListener(chart, {!! json_encode($event, $jsonFlags) !!}, {!! $handler !!});
        @endforeach

        chart.draw(data, options);

        @if ($responsive)
            window.addEventListener('resize', function () { chart.draw(data, options); });
        @endif
    }

    function loadAndDraw() {
        google.charts.load({!! $versionJson !!}, { packages: {!! $packagesJson !!}, language: {!! $languageJson !!} });
        google.charts.setOnLoadCallback(draw);
    }

    if (window.google && window.google.charts) {
        loadAndDraw();
        return;
    }

    // Inject the loader only once per page; queue chart bootstraps until it is ready.
    var GCL = window.googleChartsLaravel = window.googleChartsLaravel || { queue: [], loaderInjected: false };
    GCL.queue.push(loadAndDraw);

    if (!GCL.loaderInjected) {
        GCL.loaderInjected = true;
        var script = document.createElement('script');
        script.src = {!! $loaderUrl !!};
        script.onload = function () {
            var queued = GCL.queue;
            GCL.queue = [];
            for (var i = 0; i < queued.length; i++) { queued[i](); }
        };
        document.head.appendChild(script);
    }
})();
</script>
