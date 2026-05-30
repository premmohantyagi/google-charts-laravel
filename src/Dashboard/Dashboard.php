<?php

namespace Premmohantyagi\GoogleCharts\Dashboard;

use Illuminate\Contracts\Support\Htmlable;
use Premmohantyagi\GoogleCharts\Charts\BaseChart;
use Premmohantyagi\GoogleCharts\Concerns\BuildsDataTable;
use Premmohantyagi\GoogleCharts\Support\ChartIdGenerator;
use Premmohantyagi\GoogleCharts\Support\PackageMapper;

/**
 * A Google Charts dashboard: one shared DataTable, one or more filter controls,
 * and one or more charts, bound together so the controls drive the charts.
 *
 * Uses the Google "controls" package (Dashboard, ControlWrapper, ChartWrapper).
 */
class Dashboard implements Htmlable
{
    use BuildsDataTable;

    /**
     * Unique DOM id of the dashboard container.
     */
    protected string $id;

    /**
     * Filter controls. Each entry: ['controlType' => ..., 'options' => [...], 'id' => ?].
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $controls = [];

    /**
     * Charts. Each entry: ['chartType' => ..., 'options' => [...], 'view' => ?, 'id' => ?].
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $charts = [];

    /**
     * Explicit control/chart bindings by index. Defaults to all-to-all.
     *
     * @var array<int, array{controls: array<int,int>, charts: array<int,int>}>
     */
    protected array $bindings = [];

    /**
     * Per-dashboard language override.
     */
    protected ?string $language = null;

    public function __construct(?string $id = null)
    {
        $this->id = $id ?: ChartIdGenerator::generate('gdash');
    }

    /**
     * Set the dashboard's DOM id.
     */
    public function id(string $id): self
    {
        $this->id = $id;

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
     * Add a filter control (e.g. CategoryFilter, NumberRangeFilter, StringFilter).
     *
     * @param  array<string, mixed>  $options  ControlWrapper options (e.g. filterColumnLabel).
     */
    public function control(string $type, array $options = [], ?string $id = null): self
    {
        $this->controls[] = [
            'controlType' => $type,
            'options' => $options,
            'id' => $id,
        ];

        return $this;
    }

    /**
     * Add a chart to the dashboard. Accepts a chart instance (its type and options are
     * used; its data comes from the shared dashboard DataTable) or a Google chart type
     * string. The $config array may include 'options' and a column 'view'.
     *
     * @param  BaseChart|string      $chart
     * @param  array<string, mixed>  $config
     */
    public function chart($chart, array $config = []): self
    {
        if ($chart instanceof BaseChart) {
            $type = $chart->getType();
            $options = array_replace_recursive($chart->getOptions(), $config['options'] ?? []);
        } else {
            $type = (string) $chart;
            $options = $config['options'] ?? [];
        }

        $entry = [
            'chartType' => $type,
            'options' => $options,
            'id' => $config['id'] ?? null,
        ];

        if (isset($config['view'])) {
            $entry['view'] = $config['view'];
        }

        $this->charts[] = $entry;

        return $this;
    }

    /**
     * Bind controls to charts by their index. Without an explicit binding, every
     * control drives every chart.
     *
     * @param  int|array<int,int>  $controls
     * @param  int|array<int,int>  $charts
     */
    public function bind($controls, $charts): self
    {
        $this->bindings[] = [
            'controls' => array_values((array) $controls),
            'charts' => array_values((array) $charts),
        ];

        return $this;
    }

    /**
     * The unique DOM id of the dashboard container.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * The Google Charts packages this dashboard needs (always includes "controls").
     *
     * @return array<int, string>
     */
    public function getPackages(): array
    {
        $packages = ['controls'];

        foreach ($this->charts as $chart) {
            $packages[] = PackageMapper::packageFor($chart['chartType'], 'corechart');
        }

        return array_values(array_unique($packages));
    }

    /**
     * Control definitions with their container ids resolved.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getControls(): array
    {
        $controls = [];

        foreach ($this->controls as $i => $control) {
            $controls[] = [
                'controlType' => $control['controlType'],
                'options' => (object) ($control['options'] ?: []),
                'containerId' => $control['id'] ?: $this->id . '__control_' . $i,
            ];
        }

        return $controls;
    }

    /**
     * Chart definitions with their container ids resolved.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCharts(): array
    {
        $charts = [];

        foreach ($this->charts as $i => $chart) {
            $entry = [
                'chartType' => $chart['chartType'],
                'options' => (object) ($chart['options'] ?: []),
                'containerId' => $chart['id'] ?: $this->id . '__chart_' . $i,
            ];

            if (isset($chart['view'])) {
                $entry['view'] = $chart['view'];
            }

            $charts[] = $entry;
        }

        return $charts;
    }

    /**
     * Control/chart bindings by index, defaulting to all controls driving all charts.
     *
     * @return array<int, array{controls: array<int,int>, charts: array<int,int>}>
     */
    public function getBindings(): array
    {
        if ($this->bindings !== []) {
            return $this->bindings;
        }

        if ($this->controls === [] || $this->charts === []) {
            return [];
        }

        return [[
            'controls' => range(0, count($this->controls) - 1),
            'charts' => range(0, count($this->charts) - 1),
        ]];
    }

    /**
     * The locale used to load the chart library.
     */
    public function getLanguage(): string
    {
        return $this->language ?: (string) $this->config('language', 'en');
    }

    /**
     * Render the dashboard to an HTML string (containers + inline script).
     */
    public function render(): string
    {
        return view('google-charts::dashboard', [
            'dashboard' => $this,
            'config' => [
                'version' => $this->config('version', 'current'),
                'loader_url' => $this->config('loader_url', 'https://www.gstatic.com/charts/loader.js'),
                'responsive' => (bool) $this->config('responsive', true),
            ],
        ])->render();
    }

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
