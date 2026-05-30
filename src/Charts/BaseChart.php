<?php

namespace Premmohantyagi\GoogleCharts\Charts;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;
use Premmohantyagi\GoogleCharts\Concerns\BuildsDataTable;
use Premmohantyagi\GoogleCharts\Contracts\Chart;
use Premmohantyagi\GoogleCharts\Support\ChartIdGenerator;
use Premmohantyagi\GoogleCharts\Support\PackageMapper;

abstract class BaseChart implements Chart, Htmlable, Jsonable, JsonSerializable
{
    use BuildsDataTable;

    /**
     * Unique DOM id of the chart container.
     */
    protected string $id;

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

    /**
     * Whether to render a "download as image" button.
     */
    protected bool $exportable = false;

    /**
     * Custom export button label.
     */
    protected ?string $exportLabel = null;

    /**
     * Custom export filename.
     */
    protected ?string $exportFilename = null;

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
     * Register a client-side event handler. The handler is a JavaScript expression
     * (a function name or an inline function) and is invoked as
     * handler(chart, dataTable, event) when the event fires.
     */
    public function on(string $event, string $jsHandler): self
    {
        $this->events[$event] = $jsHandler;

        return $this;
    }

    /**
     * Handle the "select" event (a user clicks a point, bar, slice, etc.).
     */
    public function onSelect(string $jsHandler): self
    {
        return $this->on('select', $jsHandler);
    }

    /**
     * Handle the "ready" event (the chart has finished drawing).
     */
    public function onReady(string $jsHandler): self
    {
        return $this->on('ready', $jsHandler);
    }

    /**
     * Handle the "error" event.
     */
    public function onError(string $jsHandler): self
    {
        return $this->on('error', $jsHandler);
    }

    /**
     * Handle the "onmouseover" event.
     */
    public function onMouseOver(string $jsHandler): self
    {
        return $this->on('onmouseover', $jsHandler);
    }

    /**
     * Handle the "onmouseout" event.
     */
    public function onMouseOut(string $jsHandler): self
    {
        return $this->on('onmouseout', $jsHandler);
    }

    /**
     * Render a "download as PNG" button next to the chart.
     */
    public function exportable(bool $enabled = true, ?string $label = null): self
    {
        $this->exportable = $enabled;

        if ($label !== null) {
            $this->exportLabel = $label;
        }

        return $this;
    }

    /**
     * Set the filename used when the chart is exported as an image.
     */
    public function exportFilename(string $filename): self
    {
        $this->exportFilename = $filename;

        return $this;
    }

    /**
     * Whether an export button should be rendered.
     */
    public function isExportable(): bool
    {
        return $this->exportable;
    }

    /**
     * The export button label.
     */
    public function getExportLabel(): string
    {
        return $this->exportLabel ?: 'Download PNG';
    }

    /**
     * The filename used when exporting the chart as an image.
     */
    public function getExportFilename(): string
    {
        return $this->exportFilename ?: $this->id . '.png';
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
}
