<?php

namespace Premmohantyagi\GoogleCharts;

use BadMethodCallException;
use InvalidArgumentException;
use Premmohantyagi\GoogleCharts\Charts\AjaxChart;
use Premmohantyagi\GoogleCharts\Charts\BaseChart;

/**
 * Entry point behind the GoogleChart facade.
 *
 * Provides a fluent factory method for every chart type, e.g.
 *   GoogleChart::columnChart()
 *   GoogleChart::lineChart()
 *   GoogleChart::donutChart()
 *
 * @method \Premmohantyagi\GoogleCharts\Charts\LineChart         lineChart(string $id = null)
 * @method \Premmohantyagi\GoogleCharts\Charts\AreaChart         areaChart(string $id = null)
 * @method \Premmohantyagi\GoogleCharts\Charts\BarChart          barChart(string $id = null)
 * @method \Premmohantyagi\GoogleCharts\Charts\ColumnChart       columnChart(string $id = null)
 * @method \Premmohantyagi\GoogleCharts\Charts\PieChart          pieChart(string $id = null)
 * @method \Premmohantyagi\GoogleCharts\Charts\DonutChart        donutChart(string $id = null)
 * @method \Premmohantyagi\GoogleCharts\Charts\ComboChart        comboChart(string $id = null)
 * @method \Premmohantyagi\GoogleCharts\Charts\ScatterChart      scatterChart(string $id = null)
 * @method \Premmohantyagi\GoogleCharts\Charts\BubbleChart       bubbleChart(string $id = null)
 * @method \Premmohantyagi\GoogleCharts\Charts\Histogram         histogram(string $id = null)
 * @method \Premmohantyagi\GoogleCharts\Charts\CandlestickChart  candlestickChart(string $id = null)
 * @method \Premmohantyagi\GoogleCharts\Charts\SteppedAreaChart  steppedAreaChart(string $id = null)
 * @method \Premmohantyagi\GoogleCharts\Charts\GeoChart          geoChart(string $id = null)
 * @method \Premmohantyagi\GoogleCharts\Charts\MapChart          mapChart(string $id = null)
 * @method \Premmohantyagi\GoogleCharts\Charts\GaugeChart        gaugeChart(string $id = null)
 * @method \Premmohantyagi\GoogleCharts\Charts\TableChart        tableChart(string $id = null)
 * @method \Premmohantyagi\GoogleCharts\Charts\TimelineChart     timelineChart(string $id = null)
 * @method \Premmohantyagi\GoogleCharts\Charts\TreeMapChart      treeMapChart(string $id = null)
 * @method \Premmohantyagi\GoogleCharts\Charts\SankeyChart       sankeyChart(string $id = null)
 * @method \Premmohantyagi\GoogleCharts\Charts\OrgChart          orgChart(string $id = null)
 * @method \Premmohantyagi\GoogleCharts\Charts\CalendarChart     calendarChart(string $id = null)
 * @method \Premmohantyagi\GoogleCharts\Charts\GanttChart        ganttChart(string $id = null)
 * @method \Premmohantyagi\GoogleCharts\Charts\WordTreeChart     wordTreeChart(string $id = null)
 * @method \Premmohantyagi\GoogleCharts\Charts\AnnotationChart   annotationChart(string $id = null)
 */
class GoogleChartFactory
{
    /**
     * Map of factory method name => chart class.
     *
     * @var array<string, class-string<BaseChart>>
     */
    protected array $charts = [
        'lineChart' => Charts\LineChart::class,
        'areaChart' => Charts\AreaChart::class,
        'barChart' => Charts\BarChart::class,
        'columnChart' => Charts\ColumnChart::class,
        'pieChart' => Charts\PieChart::class,
        'donutChart' => Charts\DonutChart::class,
        'comboChart' => Charts\ComboChart::class,
        'scatterChart' => Charts\ScatterChart::class,
        'bubbleChart' => Charts\BubbleChart::class,
        'histogram' => Charts\Histogram::class,
        'candlestickChart' => Charts\CandlestickChart::class,
        'steppedAreaChart' => Charts\SteppedAreaChart::class,
        'geoChart' => Charts\GeoChart::class,
        'mapChart' => Charts\MapChart::class,
        'gaugeChart' => Charts\GaugeChart::class,
        'tableChart' => Charts\TableChart::class,
        'timelineChart' => Charts\TimelineChart::class,
        'treeMapChart' => Charts\TreeMapChart::class,
        'sankeyChart' => Charts\SankeyChart::class,
        'orgChart' => Charts\OrgChart::class,
        'calendarChart' => Charts\CalendarChart::class,
        'ganttChart' => Charts\GanttChart::class,
        'wordTreeChart' => Charts\WordTreeChart::class,
        'annotationChart' => Charts\AnnotationChart::class,
    ];

    /**
     * Named chart builders, used by the AJAX endpoint.
     *
     * @var array<string, callable>
     */
    protected array $definitions = [];

    /**
     * Create a chart instance by its registered method name.
     */
    public function make(string $chart, ?string $id = null): BaseChart
    {
        if (! isset($this->charts[$chart])) {
            throw new BadMethodCallException("Unknown chart type [{$chart}].");
        }

        $class = $this->charts[$chart];

        return new $class($id);
    }

    /**
     * Register a custom chart type.
     *
     * @param  class-string<BaseChart>  $class
     */
    public function register(string $method, string $class): self
    {
        $this->charts[$method] = $class;

        return $this;
    }

    /**
     * The registered chart factory methods.
     *
     * @return array<string, class-string<BaseChart>>
     */
    public function available(): array
    {
        return $this->charts;
    }

    /**
     * Define a named chart builder for the AJAX endpoint. The callback receives any
     * parameters passed to build() (the HTTP request when called from the route) and
     * should return a chart instance.
     */
    public function define(string $name, callable $builder): self
    {
        $this->definitions[$name] = $builder;

        return $this;
    }

    /**
     * Whether a named chart builder exists.
     */
    public function defined(string $name): bool
    {
        return isset($this->definitions[$name]);
    }

    /**
     * Build a named chart, passing through any parameters to its builder.
     *
     * @param  mixed  ...$parameters
     * @return mixed
     */
    public function build(string $name, ...$parameters)
    {
        if (! isset($this->definitions[$name])) {
            throw new InvalidArgumentException("No chart named [{$name}] is defined.");
        }

        return ($this->definitions[$name])(...$parameters);
    }

    /**
     * Create a placeholder chart that loads a named chart from the built-in route.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function async(string $name, array $parameters = [], ?string $id = null): AjaxChart
    {
        $routeName = (string) config('google-charts.route.as', 'google-charts.') . 'show';
        $url = route($routeName, array_merge(['name' => $name], $parameters));

        return (new AjaxChart($id))->ajax($url);
    }

    /**
     * @param  array<int, mixed>  $arguments
     */
    public function __call(string $method, array $arguments): BaseChart
    {
        if (! isset($this->charts[$method])) {
            throw new BadMethodCallException(
                sprintf('Method %s::%s does not exist.', static::class, $method)
            );
        }

        return $this->make($method, $arguments[0] ?? null);
    }
}
