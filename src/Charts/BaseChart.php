<?php

namespace Premmohantyagi\GoogleCharts\Charts;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;
use Premmohantyagi\GoogleCharts\Contracts\Chart;
use Premmohantyagi\GoogleCharts\Data\DataTable;
use Premmohantyagi\GoogleCharts\Support\ChartIdGenerator;
use Premmohantyagi\GoogleCharts\Support\PackageMapper;
use Traversable;

abstract class BaseChart implements Chart, Htmlable, Jsonable, JsonSerializable
{
    /**
     * Unique DOM id of the chart container.
     */
    protected string $id;

    /**
     * Column definitions (README form: [type, label]).
     *
     * @var array<int, mixed>
     */
    protected array $columns = [];

    /**
     * Row values.
     *
     * @var array<int, mixed>
     */
    protected array $rows = [];

    /**
     * Chart options (merged over config defaults at render time).
     *
     * @var array<string, mixed>
     */
    protected array $options = [];

    /**
     * Per-chart package override.
     */
    protected ?string $package = null;

    /**
     * Per-chart language override.
     */
    protected ?string $language = null;

    /**
     * Registered client-side event handlers, keyed by event name.
     *
     * Reserved for v0.1.5 (event handling); stored now so the structure is complete.
     *
     * @var array<string, string>
     */
    protected array $events = [];

    /**
     * When set, the chart is rendered as a placeholder that loads its data from
     * this URL instead of having the data serialized into the page.
     */
    protected ?string $ajaxUrl = null;

    public function __construct(?string $id = null)
    {
        $this->id = $id ?: ChartIdGenerator::generate();
    }

    /**
     * The Google Visualization class name (e.g. "ColumnChart").
     */
    abstract public function getType(): string;

    /**
     * Set the chart's DOM id.
     */
    public function id(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set the chart title (shortcut for the "title" option).
     */
    public function title(string $title): self
    {
        $this->options['title'] = $title;

        return $this;
    }

    /**
     * Define the chart columns.
     *
     * @param  array<int, mixed>  $columns
     */
    public function columns(array $columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * Define the chart rows.
     *
     * @param  iterable<int, mixed>  $rows
     */
    public function rows(iterable $rows): self
    {
        $this->rows = $this->toArrayValue($rows);

        return $this;
    }

    /**
     * Append a single row.
     *
     * @param  array<int, mixed>  $row
     */
    public function addRow(array $row): self
    {
        $this->rows[] = $row;

        return $this;
    }

    /**
     * Set chart data (rows). Accepts arrays, Collections, or anything iterable.
     *
     * @param  iterable<int, mixed>  $data
     */
    public function data(iterable $data): self
    {
        return $this->rows($data);
    }

    /**
     * Build columns and rows from a dataset of arrays, objects, Eloquent models,
     * a Collection, or a query builder.
     *
     * The $columns argument maps each chart column to a source field. Each spec may be:
     *   - a string field name:            'total'
     *   - an indexed array:               [type, label, field]   (field defaults to label)
     *   - an associative array:           ['type' => ..., 'label' => ..., 'field' => ...]
     *   - a closure for a computed value: ['number', 'Sales', fn ($row) => $row->price * $row->qty]
     *
     * When $columns is omitted, columns are derived from the keys/attributes of the
     * first item. Values are read with Laravel's data_get(), so dot notation works.
     *
     * @param  mixed                  $source
     * @param  array<int, mixed>      $columns
     */
    public function dataset($source, array $columns = []): self
    {
        $items = $this->resolveItems($source);

        if ($columns === []) {
            $columns = $this->deriveDatasetColumns($items);
        }

        $columnDefs = [];
        $fields = [];

        foreach ($columns as $spec) {
            [$type, $label, $field] = $this->normalizeDatasetColumn($spec, $items);
            $columnDefs[] = [$type, $label];
            $fields[] = $field;
        }

        $this->columns = $columnDefs;
        $this->rows = [];

        foreach ($items as $item) {
            $row = [];
            foreach ($fields as $field) {
                $row[] = $field instanceof \Closure ? $field($item) : data_get($item, $field);
            }
            $this->rows[] = $row;
        }

        return $this;
    }

    /**
     * Build columns and rows from a query builder (the query is executed for you).
     *
     * @param  mixed              $query
     * @param  array<int, mixed>  $columns
     */
    public function fromQuery($query, array $columns = []): self
    {
        return $this->dataset($query, $columns);
    }

    /**
     * Merge in chart options.
     *
     * @param  array<string, mixed>  $options
     */
    public function options(array $options): self
    {
        $this->options = array_replace_recursive($this->options, $options);

        return $this;
    }

    /**
     * Set a single option using "dot" notation (e.g. "legend.position").
     *
     * @param  mixed  $value
     */
    public function set(string $key, $value): self
    {
        $segments = explode('.', $key);
        $target = &$this->options;

        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                $target[$segment] = $value;
                break;
            }

            if (! isset($target[$segment]) || ! is_array($target[$segment])) {
                $target[$segment] = [];
            }

            $target = &$target[$segment];
        }

        return $this;
    }

    /**
     * Shortcut for the "height" option.
     *
     * @param  int|string  $height
     */
    public function height($height): self
    {
        $this->options['height'] = $height;

        return $this;
    }

    /**
     * Shortcut for the "width" option.
     *
     * @param  int|string  $width
     */
    public function width($width): self
    {
        $this->options['width'] = $width;

        return $this;
    }

    /**
     * Override the Google Charts package this chart loads.
     */
    public function package(string $package): self
    {
        $this->package = $package;

        return $this;
    }

    /**
     * Override the locale used to load the chart library.
     */
    public function language(string $language): self
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Register a client-side event handler (reserved for v0.1.5).
     */
    public function on(string $event, string $jsHandler): self
    {
        $this->events[$event] = $jsHandler;

        return $this;
    }

    /**
     * The Google Charts package the chart requires.
     */
    public function getPackage(): string
    {
        if ($this->package !== null) {
            return $this->package;
        }

        return PackageMapper::packageFor($this->getType(), $this->config('default_package', 'corechart'));
    }

    /**
     * The unique DOM id of the chart container.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Build the Google DataTable payload for this chart.
     *
     * @return array<string, mixed>
     */
    public function getDataTable(): array
    {
        $table = new DataTable();

        if ($this->columns !== []) {
            $table->setColumns($this->columns);
        }

        $table->setRows($this->rows);

        return $table->toArray();
    }

    /**
     * The chart options merged over the configured defaults.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        $defaults = (array) $this->config('default_options', []);

        return array_replace_recursive($defaults, $this->options);
    }

    /**
     * The locale used to load the chart library.
     */
    public function getLanguage(): string
    {
        return $this->language ?: (string) $this->config('language', 'en');
    }

    /**
     * Registered client-side event handlers.
     *
     * @return array<string, string>
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * Render the chart as a placeholder that loads its data from the given URL.
     *
     * The endpoint is expected to return a chart's toArray() JSON. A controller can
     * return a chart instance directly, since charts are JSON serializable.
     */
    public function ajax(string $url): self
    {
        $this->ajaxUrl = $url;

        return $this;
    }

    /**
     * The URL this chart loads its data from, or null for an inline chart.
     */
    public function getAjaxUrl(): ?string
    {
        return $this->ajaxUrl;
    }

    /**
     * Whether the chart loads its data asynchronously.
     */
    public function isAjax(): bool
    {
        return $this->ajaxUrl !== null;
    }

    /**
     * Export the full chart definition (useful for AJAX/JSON loading).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'type' => $this->getType(),
            'package' => $this->getPackage(),
            'language' => $this->getLanguage(),
            'dataTable' => $this->getDataTable(),
            'options' => $this->getOptions(),
            'events' => $this->getEvents(),
        ];
    }

    /**
     * The chart definition as JSON.
     *
     * @param  int  $options
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Render the chart to an HTML string (container + inline script).
     */
    public function render(): string
    {
        return view('google-charts::chart', [
            'chart' => $this,
            'config' => [
                'version' => $this->config('version', 'current'),
                'loader_url' => $this->config('loader_url', 'https://www.gstatic.com/charts/loader.js'),
                'responsive' => (bool) $this->config('responsive', true),
            ],
        ])->render();
    }

    /**
     * Allow charts to be echoed directly in Blade ({{ $chart }}).
     */
    public function toHtml(): string
    {
        return $this->render();
    }

    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * Read a package config value, falling back when Laravel is unavailable.
     *
     * @param  mixed  $default
     * @return mixed
     */
    protected function config(string $key, $default = null)
    {
        if (function_exists('config') && function_exists('app') && app()->bound('config')) {
            return config('google-charts.' . $key, $default);
        }

        return $default;
    }

    /**
     * Coerce an iterable into a plain array.
     *
     * @param  iterable<int, mixed>  $value
     * @return array<int, mixed>
     */
    protected function toArrayValue(iterable $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value instanceof Arrayable) {
            return $value->toArray();
        }

        if ($value instanceof Traversable) {
            return iterator_to_array($value);
        }

        return (array) $value;
    }

    /**
     * Resolve a dataset source into a plain list of items, executing a query builder
     * and unwrapping a Collection without deep-converting its items to arrays.
     *
     * @param  mixed  $source
     * @return array<int, mixed>
     */
    protected function resolveItems($source): array
    {
        if ($source instanceof \Illuminate\Contracts\Database\Query\Builder
            || $source instanceof \Illuminate\Database\Eloquent\Builder
            || $source instanceof \Illuminate\Database\Query\Builder) {
            $source = $source->get();
        }

        if ($source instanceof \Illuminate\Support\Enumerable) {
            return array_values($source->all());
        }

        if (is_array($source)) {
            return array_values($source);
        }

        return array_values($this->toArrayValue($source));
    }

    /**
     * Derive column specs from the keys/attributes of the first dataset item.
     *
     * @param  array<int, mixed>  $items
     * @return array<int, array{0: string, 1: string}>
     */
    protected function deriveDatasetColumns(array $items): array
    {
        if ($items === []) {
            return [];
        }

        $first = reset($items);

        return array_map(function ($key) use ($first) {
            return [$this->inferType(data_get($first, $key)), (string) $key];
        }, $this->keysOf($first));
    }

    /**
     * Normalize a single dataset column spec into [type, label, field].
     *
     * @param  mixed              $spec
     * @param  array<int, mixed>  $items
     * @return array{0: string, 1: string, 2: mixed}
     */
    protected function normalizeDatasetColumn($spec, array $items): array
    {
        if ($spec instanceof \Closure) {
            return ['string', 'value', $spec];
        }

        if (is_string($spec)) {
            return [$this->inferType($this->sampleValue($items, $spec)), $spec, $spec];
        }

        if (is_array($spec) && $this->isAssoc($spec)) {
            $field = $spec['field'] ?? ($spec['label'] ?? null);
            $label = $spec['label'] ?? (is_string($field) ? $field : 'value');
            $type = $spec['type'] ?? $this->inferType($this->sampleValue($items, $field));

            return [(string) $type, (string) $label, $field];
        }

        $spec = array_values((array) $spec);

        if (count($spec) === 1) {
            $field = $spec[0];
            $label = is_string($field) ? $field : 'value';

            return [$this->inferType($this->sampleValue($items, $field)), $label, $field];
        }

        $type = (string) ($spec[0] ?? 'string');
        $label = (string) ($spec[1] ?? $type);
        $field = array_key_exists(2, $spec) ? $spec[2] : $label;

        return [$type, $label, $field];
    }

    /**
     * Read the first item's value for a field, used for type inference.
     *
     * @param  array<int, mixed>  $items
     * @param  mixed              $field
     * @return mixed
     */
    protected function sampleValue(array $items, $field)
    {
        if ($items === []) {
            return null;
        }

        $first = reset($items);

        return $field instanceof \Closure ? $field($first) : data_get($first, $field);
    }

    /**
     * The keys/attributes of a dataset item.
     *
     * @param  mixed  $item
     * @return array<int, int|string>
     */
    protected function keysOf($item): array
    {
        if (is_array($item)) {
            return array_keys($item);
        }

        if ($item instanceof Arrayable) {
            return array_keys($item->toArray());
        }

        if (is_object($item)) {
            return array_keys(get_object_vars($item));
        }

        return [];
    }

    /**
     * Infer a Google Charts column type from a sample value.
     *
     * @param  mixed  $value
     */
    protected function inferType($value): string
    {
        if (is_int($value) || is_float($value)) {
            return 'number';
        }

        if (is_bool($value)) {
            return 'boolean';
        }

        return 'string';
    }

    /**
     * Determine if an array is associative.
     *
     * @param  array<mixed>  $array
     */
    protected function isAssoc(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }
}
