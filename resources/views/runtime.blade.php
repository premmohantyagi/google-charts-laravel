@once
@php
    $jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;
    $loaderUrl = config('google-charts.loader_url', 'https://www.gstatic.com/charts/loader.js');
@endphp
<script type="text/javascript">
(function () {
    if (window.GoogleChartsLaravel) { return; }

    var runtime = {
        loaderUrl: {!! json_encode($loaderUrl, $jsonFlags) !!},
        loaderInjected: false,
        queue: [],

        // Ensure the Google Charts loader is present, then run the callback once it is ready.
        // The loader script is injected only once per page, no matter how many charts there are.
        withLoader: function (callback) {
            if (window.google && window.google.charts) {
                callback();
                return;
            }

            this.queue.push(callback);

            if (this.loaderInjected) {
                return;
            }

            this.loaderInjected = true;

            var self = this;
            var script = document.createElement('script');
            script.src = this.loaderUrl;
            script.onload = function () {
                var pending = self.queue;
                self.queue = [];
                for (var i = 0; i < pending.length; i++) {
                    pending[i]();
                }
            };
            document.head.appendChild(script);
        },

        // Inject the loader ahead of time (used by the optional scripts partial).
        preload: function () {
            this.withLoader(function () {});
        },

        // Fetch a chart definition from a URL and draw it. The endpoint is expected to
        // return a chart's toArray() JSON (type, package, dataTable, options, language).
        load: function (spec) {
            var self = this;
            var container = document.getElementById(spec.id);

            fetch(spec.url, spec.fetch || {})
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(function (payload) {
                    self.render({
                        id: spec.id,
                        type: payload.type,
                        version: spec.version || payload.version,
                        language: payload.language,
                        packages: payload.packages || (payload.package ? [payload.package] : undefined),
                        dataTable: payload.dataTable,
                        options: Object.assign({}, spec.options || {}, payload.options || {}),
                        responsive: spec.responsive
                    });
                })
                .catch(function (error) {
                    if (window.console && console.error) {
                        console.error('GoogleChartsLaravel: failed to load chart "' + spec.id + '"', error);
                    }
                    if (container) {
                        container.setAttribute('data-google-chart-error', '1');
                    }
                });
        },

        // Register a chart for drawing. Packages from every chart are loaded through the
        // shared loader, so the library is fetched once and reused.
        render: function (spec) {
            var self = this;
            this.withLoader(function () {
                google.charts.load(spec.version || 'current', {
                    packages: spec.packages || ['corechart'],
                    language: spec.language || 'en'
                }).then(function () {
                    self.draw(spec);
                });
            });
        },

        draw: function (spec) {
            var container = document.getElementById(spec.id);
            if (!container) {
                return;
            }

            var data = new google.visualization.DataTable(spec.dataTable);
            var chart = new google.visualization[spec.type](container);

            if (spec.events) {
                for (var name in spec.events) {
                    if (Object.prototype.hasOwnProperty.call(spec.events, name)) {
                        google.visualization.events.addListener(chart, name, spec.events[name]);
                    }
                }
            }

            chart.draw(data, spec.options || {});

            if (spec.responsive) {
                window.addEventListener('resize', function () {
                    chart.draw(data, spec.options || {});
                });
            }
        }
    };

    window.GoogleChartsLaravel = runtime;
})();
</script>
@endonce
