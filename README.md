# Google Charts for Laravel

A Laravel package for building [Google Charts](https://developers.google.com/chart) with a fluent
PHP API. You define a chart in PHP and render it in Blade. The package outputs the chart container
and the inline JavaScript that loads the Google Charts library and draws it. There is no build step,
no npm, and no manual script wiring.

```php
use Premmohantyagi\GoogleCharts\Facades\GoogleChart;

$chart = GoogleChart::columnChart()
    ->title('Monthly Sales')
    ->columns([
        ['string', 'Month'],
        ['number', 'Sales'],
    ])
    ->rows([
        ['Jan', 1000],
        ['Feb', 1500],
        ['Mar', 1200],
    ])
    ->options([
        'height' => 400,
        'legend' => ['position' => 'bottom'],
    ]);
```

```blade
{!! $chart->render() !!}
```

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Quick Start](#quick-start)
- [Building Charts](#building-charts)
  - [Columns and rows](#columns-and-rows)
  - [Data from arrays and collections](#data-from-arrays-and-collections)
  - [Data from Eloquent and collections](#data-from-eloquent-and-collections)
  - [Options](#options)
- [Rendering Charts](#rendering-charts)
  - [Inline render](#inline-render)
  - [Blade component](#blade-component)
  - [Multiple charts on one page](#multiple-charts-on-one-page)
- [AJAX Charts](#ajax-charts)
- [Dashboards and Filter Controls](#dashboards-and-filter-controls)
- [Available Charts](#available-charts)
- [Fluent API Reference](#fluent-api-reference)
- [Responsive Charts](#responsive-charts)
- [Events](#events)
- [Export as Image](#export-as-image)
- [Visual Builder](#visual-builder)
- [Testing](#testing)
- [Changelog](#changelog)
- [License](#license)

## Requirements

| Dependency | Supported versions |
| ---------- | ------------------ |
| PHP        | 8.0 to 8.4         |
| Laravel    | 8 to 13            |

## Installation

Install via Composer:

```bash
composer require premmohantyagi/google-charts-laravel
```

The service provider and the `GoogleChart` facade are registered automatically through Laravel's
[package auto-discovery](https://laravel.com/docs/packages#package-discovery), so no further setup
is required.

## Configuration

The package works with sensible defaults. To customise them, publish the config file:

```bash
php artisan vendor:publish --tag=google-charts-config
```

This creates `config/google-charts.php`:

```php
return [
    // Google Charts loader version: 'current', 'upcoming', or a frozen version (e.g. '51').
    'version' => 'current',

    // Default package used when a chart does not specify its own.
    'default_package' => 'corechart',

    // Locale used when loading the chart library.
    'language' => 'en',

    // Redraw charts on window resize so they adapt to their container.
    'responsive' => true,

    // The Google Charts loader script URL.
    'loader_url' => 'https://www.gstatic.com/charts/loader.js',

    // Required by some map-based charts (e.g. Map with markers). Leave null otherwise.
    'maps_api_key' => null,

    // Options merged into every chart unless overridden per-chart.
    'default_options' => [
        'height' => 400,
        'width'  => '100%',
    ],
];
```

To customise the Blade views (the container markup and the loader script), publish them too:

```bash
php artisan vendor:publish --tag=google-charts-views
```

The views are published to `resources/views/vendor/google-charts/`.

## Quick Start

Create a chart in your controller and pass it to a view:

```php
use Premmohantyagi\GoogleCharts\Facades\GoogleChart;

class DashboardController
{
    public function index()
    {
        $chart = GoogleChart::pieChart()
            ->title('Traffic Sources')
            ->columns([
                ['string', 'Source'],
                ['number', 'Visits'],
            ])
            ->rows([
                ['Organic', 4200],
                ['Direct',  2100],
                ['Referral', 900],
            ]);

        return view('dashboard', ['chart' => $chart]);
    }
}
```

Render it in `dashboard.blade.php`:

```blade
<div class="card">
    {!! $chart->render() !!}
</div>
```

## Building Charts

Every chart uses the same fluent builder. Start a chart through the facade or by instantiating its
class directly:

```php
// Via the facade
$chart = GoogleChart::lineChart();

// Or directly
use Premmohantyagi\GoogleCharts\Charts\LineChart;
$chart = new LineChart();
```

### Columns and rows

Columns are declared as `[type, label]` pairs. Valid Google Charts column types are
`string`, `number`, `boolean`, `date`, `datetime`, and `timeofday`.

```php
$chart->columns([
    ['string', 'Month'],
    ['number', 'Sales'],
    ['number', 'Expenses'],
])->rows([
    ['Jan', 1000, 400],
    ['Feb', 1500, 460],
    ['Mar', 1200, 420],
]);
```

You can also append rows one at a time:

```php
$chart->addRow(['Apr', 1700, 500]);
```

To attach a formatted display value alongside the raw value, pass a `['v' => ..., 'f' => ...]` cell:

```php
$chart->rows([
    [['v' => 1000, 'f' => '$1,000']],
]);
```

### Data from arrays and collections

`rows()` and its alias `data()` accept any iterable: a plain array, a Laravel `Collection`, or a
generator, where each item is a row of values.

```php
$rows = collect($orders)
    ->map(fn ($order) => [$order->month, $order->total]);

$chart->columns([['string', 'Month'], ['number', 'Total']])
      ->data($rows);
```

### Data from Eloquent and collections

`dataset()` builds the columns and rows for you from a collection, a list of arrays or objects, or
Eloquent models. Each column is mapped to a source field, and values are read with `data_get()`, so
dot notation works:

```php
$chart->dataset($orders, [
    ['string', 'Month', 'month'],   // [type, label, field]
    ['number', 'Sales', 'total'],
]);
```

Pass a query builder to `fromQuery()` and the query runs for you:

```php
use App\Models\Order;

$chart->fromQuery(
    Order::query()->where('year', 2026)->orderBy('month'),
    [
        ['string', 'Month', 'month'],
        ['number', 'Sales', 'total'],
    ]
);
```

A column field can be a closure for computed values:

```php
$chart->dataset($orders, [
    ['string', 'Month',   'month'],
    ['number', 'Revenue', fn ($order) => $order->price * $order->quantity],
]);
```

If you omit the column map, columns are derived from the keys or attributes of the first item, with
types inferred from their values:

```php
// Columns: month (string), total (number)
$chart->dataset([
    ['month' => 'Jan', 'total' => 1000],
    ['month' => 'Feb', 'total' => 1500],
]);
```

### Options

`options()` accepts any of the
[Google Charts configuration options](https://developers.google.com/chart/interactive/docs/customizing_charts)
and merges them recursively, so you can call it more than once:

```php
$chart->options([
    'height' => 400,
    'colors' => ['#4285F4', '#34A853'],
    'legend' => ['position' => 'bottom'],
]);
```

Set a single nested option with dot notation:

```php
$chart->set('hAxis.title', 'Month')
      ->set('vAxis.title', 'Amount');
```

Convenience shortcuts:

```php
$chart->title('Monthly Sales')   // sets the "title" option
      ->height(500)               // sets "height"
      ->width('100%');            // sets "width"
```

## Rendering Charts

### Inline render

`render()` returns the full HTML: a `<div>` container plus the inline `<script>` that loads the
library and draws the chart.

```blade
{!! $chart->render() !!}
```

Charts are also `Htmlable` and stringable, so this works too:

```blade
{{ $chart }}
```

### Blade component

You can also render a chart with the `<x-google-chart>` component:

```blade
<x-google-chart :chart="$chart" />
```

### Multiple charts on one page

You can render any number of charts on a single page. Each chart is self-contained, and the Google
loader is injected only once and shared between them.

When a page has many charts, preload the loader once in your layout's `<head>` for best
performance:

```blade
@include('google-charts::scripts')
```

## AJAX Charts

A chart can load its data from an endpoint instead of having it serialized into the page. This keeps
the initial response small and lets data refresh without a full reload.

Charts are JSON serializable, so an endpoint can return a chart directly:

```php
use Premmohantyagi\GoogleCharts\Facades\GoogleChart;

Route::get('/charts/sales', function () {
    return GoogleChart::columnChart()
        ->columns([['string', 'Month'], ['number', 'Sales']])
        ->rows(Order::salesByMonth());
});
```

Render a placeholder that fetches that URL with `ajax()`:

```php
$chart = GoogleChart::columnChart('sales')->ajax('/charts/sales');
```

```blade
{!! $chart->render() !!}
```

### Built-in endpoint

The package ships an optional route for serving named charts. Enable it in the config:

```php
// config/google-charts.php
'route' => [
    'enabled' => true,
    'prefix' => 'google-charts',
    'middleware' => ['web'],
    'as' => 'google-charts.',
],
```

Register named charts (for example in a service provider's `boot()` method). The builder receives the
current request:

```php
use Premmohantyagi\GoogleCharts\Facades\GoogleChart;

GoogleChart::define('sales', function ($request) {
    return GoogleChart::columnChart()
        ->columns([['string', 'Month'], ['number', 'Sales']])
        ->rows(Order::salesByMonth($request->integer('year')));
});
```

Render a placeholder pointing at the named chart with `async()`:

```php
$chart = GoogleChart::async('sales', ['year' => 2026]);
```

```blade
{!! $chart->render() !!}
```

## Dashboards and Filter Controls

A dashboard binds filter controls to one or more charts over a single shared data set, so changing a
filter updates every bound chart. Build one with `dashboard()`:

```php
use Premmohantyagi\GoogleCharts\Facades\GoogleChart;

$dashboard = GoogleChart::dashboard('people')
    ->columns([
        ['string', 'Name'],
        ['number', 'Age'],
        ['number', 'Donuts'],
    ])
    ->rows([
        ['Bob', 30, 5],
        ['Alice', 25, 9],
        ['Carol', 40, 3],
    ])
    ->control('CategoryFilter', ['filterColumnLabel' => 'Name'])
    ->control('NumberRangeFilter', ['filterColumnLabel' => 'Age'])
    ->chart(GoogleChart::columnChart()->title('Donuts eaten'));
```

```blade
{!! $dashboard->render() !!}
```

The dashboard shares its data with `columns()`/`rows()` or `dataset()`, exactly like a chart.

- **Controls** are added with `control($type, $options)`, where `$type` is a Google control
  (`CategoryFilter`, `NumberRangeFilter`, `StringFilter`, `DateRangeFilter`, `ChartRangeFilter`) and
  `$options` are its `ControlWrapper` options.
- **Charts** are added with `chart($chart, $config)`. Pass a chart instance (its type and options are
  used) or a Google chart type string. The optional `$config` accepts `options` and a column `view`.
- **Binding** is all controls to all charts by default. Bind specific pairs by index with
  `bind($controlIndexes, $chartIndexes)`.

## Available Charts

Every chart is reachable as a facade method (for example `GoogleChart::columnChart()`) and as a
class under `Premmohantyagi\GoogleCharts\Charts`.

### Core charts (`corechart`)

| Facade method            | Class                | Google type            |
| ------------------------ | -------------------- | ---------------------- |
| `lineChart()`            | `LineChart`          | `LineChart`            |
| `areaChart()`            | `AreaChart`          | `AreaChart`            |
| `barChart()`             | `BarChart`           | `BarChart`             |
| `columnChart()`          | `ColumnChart`        | `ColumnChart`          |
| `pieChart()`             | `PieChart`           | `PieChart`             |
| `donutChart()`           | `DonutChart`         | `PieChart` with `pieHole` |
| `comboChart()`           | `ComboChart`         | `ComboChart`           |
| `scatterChart()`         | `ScatterChart`       | `ScatterChart`         |
| `bubbleChart()`          | `BubbleChart`        | `BubbleChart`          |
| `histogram()`            | `Histogram`          | `Histogram`            |
| `candlestickChart()`     | `CandlestickChart`   | `CandlestickChart`     |
| `steppedAreaChart()`     | `SteppedAreaChart`   | `SteppedAreaChart`     |

### Advanced charts

| Facade method            | Class               | Google type        | Package          |
| ------------------------ | ------------------- | ------------------ | ---------------- |
| `geoChart()`             | `GeoChart`          | `GeoChart`         | `geochart`       |
| `mapChart()`             | `MapChart`          | `Map`              | `map`            |
| `gaugeChart()`           | `GaugeChart`        | `Gauge`            | `gauge`          |
| `tableChart()`           | `TableChart`        | `Table`            | `table`          |
| `timelineChart()`        | `TimelineChart`     | `Timeline`         | `timeline`       |
| `treeMapChart()`         | `TreeMapChart`      | `TreeMap`          | `treemap`        |
| `sankeyChart()`          | `SankeyChart`       | `Sankey`           | `sankey`         |
| `orgChart()`             | `OrgChart`          | `OrgChart`         | `orgchart`       |
| `calendarChart()`        | `CalendarChart`     | `Calendar`         | `calendar`       |
| `ganttChart()`           | `GanttChart`        | `Gantt`            | `gantt`          |
| `wordTreeChart()`        | `WordTreeChart`     | `WordTree`         | `wordtree`       |
| `annotationChart()`      | `AnnotationChart`   | `AnnotationChart`  | `annotationchart`|

The donut chart is a pie chart with a non-zero `pieHole`, since Google Charts has no separate donut
type. `DonutChart` applies a default `pieHole` that you can override.

## Fluent API Reference

All setters return `$this`, so they chain.

| Method | Description |
| ------ | ----------- |
| `id(string $id)` | Set the chart container's DOM id (auto-generated if omitted). |
| `title(string $title)` | Shortcut for the `title` option. |
| `columns(array $columns)` | Define columns as `[type, label]` pairs. |
| `rows(iterable $rows)` | Define all rows. |
| `addRow(array $row)` | Append a single row. |
| `data(iterable $data)` | Alias of `rows()`. |
| `dataset($source, array $columns = [])` | Build columns and rows from a collection, array, or models by mapping columns to fields. |
| `fromQuery($query, array $columns = [])` | Run a query builder and build the chart from the results. |
| `options(array $options)` | Recursively merge chart options. |
| `set(string $key, $value)` | Set one option using dot notation (e.g. `legend.position`). |
| `height(int\|string $height)` | Shortcut for the `height` option. |
| `width(int\|string $width)` | Shortcut for the `width` option. |
| `package(string $package)` | Override the Google Charts package to load. |
| `language(string $language)` | Override the locale used to load the library. |
| `on(string $event, string $jsHandler)` | Register a client-side event handler. |
| `onSelect/onReady/onError/onMouseOver/onMouseOut(string $jsHandler)` | Shortcuts for common events. |
| `exportable(bool $enabled = true, string $label = null)` | Render a "download as PNG" button. |
| `exportFilename(string $filename)` | Set the exported image filename. |
| `ajax(string $url)` | Render a placeholder that loads the chart definition from a URL. |
| `render(): string` | Render the chart to HTML (container and inline script). |
| `toArray(): array` | Export the full chart definition (useful for AJAX or JSON). |
| `toJson($options = 0): string` | Export the chart definition as JSON. |
| `getId()` / `getType()` / `getPackage()` / `getOptions()` / `getDataTable()` | Inspect the built chart. |

## Responsive Charts

When `responsive` is enabled in the config (the default), each chart redraws on window resize so it
adapts to its container. Combine that with a percentage width:

```php
$chart->width('100%');
```

## Events

Attach a Google Charts event by passing the name and a JavaScript handler expression, such as a
function name or an inline function. The handler is called with the chart, its DataTable, and the
native event, so it can read the current selection:

```php
$chart->on('select', 'function (chart, data) {
    var selection = chart.getSelection();
    if (selection.length) {
        console.log(data.getValue(selection[0].row, 0));
    }
}');
```

There are shortcuts for the common events:

```php
$chart->onSelect('handleSelect')   // select
      ->onReady('handleReady')     // ready
      ->onError('handleError')     // error
      ->onMouseOver('handleOver')  // onmouseover
      ->onMouseOut('handleOut');   // onmouseout
```

## Export as Image

Call `exportable()` to render a button that downloads the chart as a PNG:

```php
$chart->exportable();                              // "Download PNG" button
$chart->exportable(true, 'Save image')             // custom label
      ->exportFilename('monthly-sales.png');       // custom filename
```

You can also trigger a download from your own JavaScript:

```js
window.GoogleChartsLaravel.download('chart-id', 'chart.png');
```

Image export uses Google's `getImageURI()`, which is available for the core (SVG-based) charts.

## Visual Builder

The package includes an optional page for building a chart or dashboard visually: choose a chart
type, edit the columns and rows, add filter controls, and see a live preview. The builder generates
the matching PHP and JSON for you to copy into your app.

Enable it in the config:

```php
// config/google-charts.php
'builder' => [
    'enabled' => true,
    'prefix' => 'google-charts',
    'path' => 'builder',
    'middleware' => ['web'],
    'as' => 'google-charts.',
],
```

The builder is then served at `/google-charts/builder`. It is disabled by default; when you enable
it outside local development, protect it with middleware (for example `'auth'`).

To embed the builder in your own page instead of using the route, include the view:

```blade
@include('google-charts::builder', ['chartTypes' => app('google-charts')->chartTypes()])
```

## Testing

```bash
composer install
composer test
```

The suite runs against [Orchestra Testbench](https://github.com/orchestral/testbench) and covers the
data builder, package mapping, the fluent API, and end-to-end rendering.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes.

## License

The MIT License (MIT). See [LICENSE](LICENSE) for details.
