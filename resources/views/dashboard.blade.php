@php
    /**
     * @var \Premmohantyagi\GoogleCharts\Dashboard\Dashboard $dashboard
     * @var array $config
     */
    $id       = $dashboard->getId();
    $controls = $dashboard->getControls();
    $charts   = $dashboard->getCharts();

    $jsonFlags    = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;
    $idJson       = json_encode($id, $jsonFlags);
    $dataJson     = json_encode($dashboard->getDataTable(), $jsonFlags);
    $packagesJson = json_encode($dashboard->getPackages(), $jsonFlags);
    $controlsJson = json_encode($controls, $jsonFlags);
    $chartsJson   = json_encode($charts, $jsonFlags);
    $bindingsJson = json_encode($dashboard->getBindings(), $jsonFlags) ?: '[]';
    $versionJson  = json_encode($config['version'] ?? 'current', $jsonFlags);
    $languageJson = json_encode($dashboard->getLanguage(), $jsonFlags);
    $responsive   = ! empty($config['responsive']);
@endphp
@include('google-charts::runtime')
<div id="{{ $id }}" class="google-dashboard">
    <div class="google-dashboard-controls">
        @foreach ($controls as $control)
            <div id="{{ $control['containerId'] }}" class="google-dashboard-control"></div>
        @endforeach
    </div>
    <div class="google-dashboard-charts">
        @foreach ($charts as $chart)
            <div id="{{ $chart['containerId'] }}" class="google-dashboard-chart"></div>
        @endforeach
    </div>
</div>
<script type="text/javascript">
window.GoogleChartsLaravel.dashboard({
    id: {!! $idJson !!},
    version: {!! $versionJson !!},
    language: {!! $languageJson !!},
    packages: {!! $packagesJson !!},
    dataTable: {!! $dataJson !!},
    controls: {!! $controlsJson !!},
    charts: {!! $chartsJson !!},
    bindings: {!! $bindingsJson !!},
    responsive: {{ $responsive ? 'true' : 'false' }}
});
</script>
