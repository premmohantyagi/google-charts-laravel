@php
    /**
     * Visual builder UI. Stateless: it draws a live preview and generates copy-paste
     * PHP and JSON. Include it on its own page or embed it in your own layout.
     *
     * @var array $chartTypes
     */
    $chartTypes = $chartTypes ?? [];
@endphp
@include('google-charts::runtime')
@once
<style>
.gc-builder { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; color: #1f2933; max-width: 1100px; margin: 24px auto; padding: 0 16px; display: grid; grid-template-columns: 320px 1fr; gap: 24px; }
.gc-builder h2 { grid-column: 1 / -1; margin: 0 0 4px; font-size: 20px; }
.gc-builder label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 12px; }
.gc-builder label > input, .gc-builder label > select, .gc-builder label > textarea { display: block; width: 100%; margin-top: 4px; padding: 6px 8px; font: inherit; font-weight: 400; border: 1px solid #cbd2d9; border-radius: 6px; box-sizing: border-box; }
.gc-builder textarea { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; resize: vertical; }
.gc-builder fieldset { border: 1px solid #cbd2d9; border-radius: 6px; padding: 8px 10px 12px; margin: 0 0 12px; }
.gc-builder legend { font-size: 13px; font-weight: 600; padding: 0 4px; }
.gc-builder .gcb-control { display: flex; gap: 6px; margin-bottom: 6px; }
.gc-builder .gcb-control select, .gc-builder .gcb-control input { padding: 4px 6px; font: inherit; border: 1px solid #cbd2d9; border-radius: 6px; }
.gc-builder .gcb-control input { flex: 1; min-width: 0; }
.gc-builder button { font: inherit; padding: 6px 12px; border: 1px solid #3056d3; background: #3056d3; color: #fff; border-radius: 6px; cursor: pointer; }
.gc-builder button.gcb-secondary, .gc-builder .gcb-control-remove, .gc-builder .gcb-add-control { background: #fff; color: #3056d3; }
.gc-builder .gcb-error { color: #b91c1c; font-size: 13px; font-weight: 600; }
.gc-builder .gcb-preview { min-height: 300px; border: 1px solid #e4e7eb; border-radius: 8px; padding: 8px; }
.gc-builder pre { background: #1f2933; color: #e4e7eb; padding: 12px; border-radius: 8px; overflow: auto; font-size: 12px; }
.gc-builder .gcb-output-head { display: flex; align-items: center; justify-content: space-between; margin-top: 16px; }
.gc-builder .gcb-output-head h3 { margin: 0; font-size: 15px; }
@media (max-width: 760px) { .gc-builder { grid-template-columns: 1fr; } }
</style>
@endonce
<div class="gc-builder">
    <h2>Chart &amp; Dashboard Builder</h2>
    <div class="gc-builder-form">
        <label>Chart type
            <select class="gcb-type">
                @foreach ($chartTypes as $t)
                    <option value="{{ $t['method'] }}" data-type="{{ $t['type'] }}" data-package="{{ $t['package'] }}">{{ $t['label'] }}</option>
                @endforeach
            </select>
        </label>
        <label>Title
            <input type="text" class="gcb-title" placeholder="Chart title">
        </label>
        <label>Height
            <input type="number" class="gcb-height" value="400" min="0">
        </label>
        <label>Legend position
            <select class="gcb-legend">
                <option value="">Default</option>
                <option value="bottom">Bottom</option>
                <option value="top">Top</option>
                <option value="right">Right</option>
                <option value="left">Left</option>
                <option value="none">None</option>
            </select>
        </label>
        <label>Columns (JSON)
            <textarea class="gcb-columns" rows="4">[["string", "Month"], ["number", "Sales"]]</textarea>
        </label>
        <label>Rows (JSON)
            <textarea class="gcb-rows" rows="6">[["Jan", 1000], ["Feb", 1500], ["Mar", 1200]]</textarea>
        </label>
        <fieldset>
            <legend>Filter controls</legend>
            <div class="gcb-controls-list"></div>
            <button type="button" class="gcb-add-control">Add control</button>
        </fieldset>
        <p class="gcb-error" hidden></p>
    </div>
    <div class="gc-builder-output">
        <h3>Preview</h3>
        <div class="gcb-preview"></div>

        <div class="gcb-output-head">
            <h3>PHP</h3>
            <button type="button" class="gcb-secondary gcb-copy" data-target="php">Copy</button>
        </div>
        <pre class="gcb-php"></pre>

        <div class="gcb-output-head">
            <h3>JSON</h3>
            <button type="button" class="gcb-secondary gcb-copy" data-target="json">Copy</button>
        </div>
        <pre class="gcb-json"></pre>
    </div>
</div>
<script type="text/javascript">
(function () {
    var root = document.currentScript.previousElementSibling;
    if (!root || !root.classList || !root.classList.contains('gc-builder')) { return; }

    var CONTROL_TYPES = ['CategoryFilter', 'NumberRangeFilter', 'StringFilter', 'DateRangeFilter', 'ChartRangeFilter'];

    var els = {
        type: root.querySelector('.gcb-type'),
        title: root.querySelector('.gcb-title'),
        height: root.querySelector('.gcb-height'),
        legend: root.querySelector('.gcb-legend'),
        columns: root.querySelector('.gcb-columns'),
        rows: root.querySelector('.gcb-rows'),
        controlsList: root.querySelector('.gcb-controls-list'),
        addControl: root.querySelector('.gcb-add-control'),
        error: root.querySelector('.gcb-error'),
        preview: root.querySelector('.gcb-preview'),
        php: root.querySelector('.gcb-php'),
        json: root.querySelector('.gcb-json')
    };

    function repeat(s, n) { var r = ''; for (var i = 0; i < n; i++) { r += s; } return r; }
    function isScalar(v) { return v === null || typeof v !== 'object'; }
    function phpStr(s) { return String(s).replace(/\\/g, '\\\\').replace(/'/g, "\\'"); }

    function phpValue(value, indent) {
        indent = indent || 0;
        var pad = repeat('    ', indent);
        var padIn = repeat('    ', indent + 1);
        if (value === null || value === undefined) { return 'null'; }
        if (typeof value === 'boolean') { return value ? 'true' : 'false'; }
        if (typeof value === 'number') { return String(value); }
        if (typeof value === 'string') { return "'" + phpStr(value) + "'"; }
        if (Array.isArray(value)) {
            if (!value.length) { return '[]'; }
            if (value.every(isScalar)) {
                return '[' + value.map(function (v) { return phpValue(v, 0); }).join(', ') + ']';
            }
            return '[\n' + value.map(function (v) { return padIn + phpValue(v, indent + 1); }).join(',\n') + ',\n' + pad + ']';
        }
        var keys = Object.keys(value);
        if (!keys.length) { return '[]'; }
        return '[\n' + keys.map(function (k) {
            return padIn + "'" + phpStr(k) + "' => " + phpValue(value[k], indent + 1);
        }).join(',\n') + ',\n' + pad + ']';
    }

    function buildDataTable(columns, rows) {
        return {
            cols: columns.map(function (c) {
                return Array.isArray(c) ? { type: c[0], label: c[1] } : c;
            }),
            rows: rows.map(function (r) {
                return { c: r.map(function (v) { return { v: v }; }) };
            })
        };
    }

    function readControls() {
        var controls = [];
        var nodes = els.controlsList.querySelectorAll('.gcb-control');
        for (var i = 0; i < nodes.length; i++) {
            controls.push({
                type: nodes[i].querySelector('.gcb-control-type').value,
                column: nodes[i].querySelector('.gcb-control-column').value
            });
        }
        return controls;
    }

    function readConfig() {
        var option = els.type.options[els.type.selectedIndex];
        var options = {};
        var height = parseInt(els.height.value, 10);
        if (!isNaN(height)) { options.height = height; }
        if (els.legend.value) { options.legend = { position: els.legend.value }; }

        return {
            method: els.type.value,
            googleType: option.getAttribute('data-type'),
            package: option.getAttribute('data-package'),
            title: els.title.value.trim(),
            options: options,
            columns: JSON.parse(els.columns.value),
            rows: JSON.parse(els.rows.value),
            controls: readControls()
        };
    }

    function chartExpr(cfg) {
        var expr = 'GoogleChart::' + cfg.method + '()';
        if (cfg.title) { expr += "->title('" + phpStr(cfg.title) + "')"; }
        if (Object.keys(cfg.options).length) { expr += '->options(' + phpValue(cfg.options, 1) + ')'; }
        return expr;
    }

    function generatePhp(cfg) {
        var lines = ['use Premmohantyagi\\GoogleCharts\\Facades\\GoogleChart;', ''];

        if (cfg.controls.length) {
            var chain = ['$dashboard = GoogleChart::dashboard()'];
            chain.push('    ->columns(' + phpValue(cfg.columns, 1) + ')');
            chain.push('    ->rows(' + phpValue(cfg.rows, 1) + ')');
            cfg.controls.forEach(function (c) {
                chain.push("    ->control('" + phpStr(c.type) + "', " + phpValue({ filterColumnLabel: c.column }, 1) + ')');
            });
            chain.push('    ->chart(' + chartExpr(cfg) + ');');
            return lines.concat(chain.join('\n')).join('\n');
        }

        var single = ['$chart = GoogleChart::' + cfg.method + '()'];
        if (cfg.title) { single.push("    ->title('" + phpStr(cfg.title) + "')"); }
        single.push('    ->columns(' + phpValue(cfg.columns, 1) + ')');
        single.push('    ->rows(' + phpValue(cfg.rows, 1) + ')');
        if (Object.keys(cfg.options).length) { single.push('    ->options(' + phpValue(cfg.options, 1) + ')'); }
        single[single.length - 1] += ';';
        return lines.concat(single.join('\n')).join('\n');
    }

    function generateJson(cfg) {
        return JSON.stringify({
            chart: cfg.method,
            title: cfg.title,
            options: cfg.options,
            columns: cfg.columns,
            rows: cfg.rows,
            controls: cfg.controls.map(function (c) {
                return { type: c.type, filterColumnLabel: c.column };
            })
        }, null, 2);
    }

    function renderPreview(cfg) {
        var previewOptions = {};
        var key;
        for (key in cfg.options) { if (cfg.options.hasOwnProperty(key)) { previewOptions[key] = cfg.options[key]; } }
        if (cfg.title) { previewOptions.title = cfg.title; }

        var dataTable = buildDataTable(cfg.columns, cfg.rows);

        if (!cfg.controls.length) {
            els.preview.innerHTML = '<div id="gcb-preview-chart"></div>';
            window.GoogleChartsLaravel.render({
                id: 'gcb-preview-chart',
                type: cfg.googleType,
                packages: [cfg.package],
                language: 'en',
                dataTable: dataTable,
                options: previewOptions,
                responsive: true
            });
            return;
        }

        var inner = '';
        cfg.controls.forEach(function (c, i) { inner += '<div id="gcb-preview-control-' + i + '"></div>'; });
        inner += '<div id="gcb-preview-chart"></div>';
        els.preview.innerHTML = '<div id="gcb-preview-dash">' + inner + '</div>';

        window.GoogleChartsLaravel.dashboard({
            id: 'gcb-preview-dash',
            packages: ['controls', cfg.package],
            language: 'en',
            dataTable: dataTable,
            controls: cfg.controls.map(function (c, i) {
                return { controlType: c.type, options: { filterColumnLabel: c.column }, containerId: 'gcb-preview-control-' + i };
            }),
            charts: [{ chartType: cfg.googleType, options: previewOptions, containerId: 'gcb-preview-chart' }],
            bindings: [{ controls: cfg.controls.map(function (c, i) { return i; }), charts: [0] }],
            responsive: true
        });
    }

    function update() {
        var cfg;
        try {
            cfg = readConfig();
        } catch (error) {
            els.error.textContent = 'Columns/Rows must be valid JSON: ' + error.message;
            els.error.hidden = false;
            return;
        }

        els.error.hidden = true;
        els.php.textContent = generatePhp(cfg);
        els.json.textContent = generateJson(cfg);
        renderPreview(cfg);
    }

    var timer = null;
    function scheduleUpdate() {
        if (timer) { window.clearTimeout(timer); }
        timer = window.setTimeout(update, 250);
    }

    function addControlRow() {
        var row = document.createElement('div');
        row.className = 'gcb-control';
        var optionsHtml = CONTROL_TYPES.map(function (t) { return '<option value="' + t + '">' + t + '</option>'; }).join('');
        row.innerHTML =
            '<select class="gcb-control-type">' + optionsHtml + '</select>' +
            '<input class="gcb-control-column" placeholder="Filter column label">' +
            '<button type="button" class="gcb-control-remove">&times;</button>';
        els.controlsList.appendChild(row);
    }

    ['change', 'input'].forEach(function (eventName) {
        root.addEventListener(eventName, function (event) {
            if (event.target.closest('.gc-builder-form')) { scheduleUpdate(); }
        });
    });

    els.addControl.addEventListener('click', function () { addControlRow(); scheduleUpdate(); });

    els.controlsList.addEventListener('click', function (event) {
        if (event.target.classList.contains('gcb-control-remove')) {
            event.target.parentNode.parentNode.removeChild(event.target.parentNode);
            scheduleUpdate();
        }
    });

    root.addEventListener('click', function (event) {
        var button = event.target.closest ? event.target.closest('.gcb-copy') : null;
        if (!button) { return; }
        var text = button.getAttribute('data-target') === 'json' ? els.json.textContent : els.php.textContent;
        if (navigator.clipboard) { navigator.clipboard.writeText(text); }
        var label = button.textContent;
        button.textContent = 'Copied';
        window.setTimeout(function () { button.textContent = label; }, 1200);
    });

    update();
})();
</script>
