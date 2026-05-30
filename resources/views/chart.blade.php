@php
    /**
     * @var \Premmohantyagi\GoogleCharts\Contracts\Chart $chart
     * @var array $config
     */
    $id       = $chart->getId();
    $type     = $chart->getType();
    $package  = $chart->getPackage();
    $language = $chart->getLanguage();
    $options  = $chart->getOptions();
    $events   = method_exists($chart, 'getEvents') ? $chart->getEvents() : [];

    // Encode payloads safely for an inline <script> context.
    $jsonFlags    = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;
    $idJson       = json_encode($id, $jsonFlags);
    $typeJson     = json_encode($type, $jsonFlags);
    $dataJson     = json_encode($chart->getDataTable(), $jsonFlags);
    $optionsJson  = json_encode((object) $options, $jsonFlags) ?: '{}';
    $packagesJson = json_encode([$package], $jsonFlags);
    $versionJson  = json_encode($config['version'] ?? 'current', $jsonFlags);
    $languageJson = json_encode($language, $jsonFlags);
    $responsive   = ! empty($config['responsive']);

    // Container dimensions: numbers become px, strings (e.g. "100%") are used as-is.
    $cssDimension = function ($value, $fallback) {
        $value = $value ?? $fallback;
        return is_numeric($value) ? $value . 'px' : $value;
    };
    $width  = $cssDimension($options['width'] ?? null, '100%');
    $height = $cssDimension($options['height'] ?? null, '400px');
@endphp
@include('google-charts::runtime')
<div id="{{ $id }}" class="google-chart" style="width: {{ $width }}; height: {{ $height }};"></div>
<script type="text/javascript">
@if ($chart->isAjax())
window.GoogleChartsLaravel.load({
    id: {!! $idJson !!},
    url: {!! json_encode($chart->getAjaxUrl(), $jsonFlags) !!},
    version: {!! $versionJson !!},
    options: {!! $optionsJson !!},
    responsive: {{ $responsive ? 'true' : 'false' }}
});
@else
window.GoogleChartsLaravel.render({
    id: {!! $idJson !!},
    type: {!! $typeJson !!},
    version: {!! $versionJson !!},
    language: {!! $languageJson !!},
    packages: {!! $packagesJson !!},
    dataTable: {!! $dataJson !!},
    options: {!! $optionsJson !!},
    responsive: {{ $responsive ? 'true' : 'false' }},
    events: {
        @foreach ($events as $event => $handler)
        {!! json_encode($event, $jsonFlags) !!}: {!! $handler !!},
        @endforeach
    }
});
@endif
</script>
