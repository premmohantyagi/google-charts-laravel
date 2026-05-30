<?php

namespace Premmohantyagi\GoogleCharts\Facades;

use Illuminate\Support\Facades\Facade;
use Premmohantyagi\GoogleCharts\Charts\BaseChart;

/**
 * @method static BaseChart make(string $chart, string $id = null)
 * @method static \Premmohantyagi\GoogleCharts\GoogleChartFactory define(string $name, callable $builder)
 * @method static bool defined(string $name)
 * @method static mixed build(string $name, ...$parameters)
 * @method static \Premmohantyagi\GoogleCharts\Charts\AjaxChart async(string $name, array $parameters = [], string $id = null)
 * @method static \Premmohantyagi\GoogleCharts\Dashboard\Dashboard dashboard(string $id = null)
 * @method static \Premmohantyagi\GoogleCharts\Charts\LineChart        lineChart(string $id = null)
 * @method static \Premmohantyagi\GoogleCharts\Charts\AreaChart        areaChart(string $id = null)
 * @method static \Premmohantyagi\GoogleCharts\Charts\BarChart         barChart(string $id = null)
 * @method static \Premmohantyagi\GoogleCharts\Charts\ColumnChart      columnChart(string $id = null)
 * @method static \Premmohantyagi\GoogleCharts\Charts\PieChart         pieChart(string $id = null)
 * @method static \Premmohantyagi\GoogleCharts\Charts\DonutChart       donutChart(string $id = null)
 * @method static \Premmohantyagi\GoogleCharts\Charts\ComboChart       comboChart(string $id = null)
 * @method static \Premmohantyagi\GoogleCharts\Charts\ScatterChart     scatterChart(string $id = null)
 * @method static \Premmohantyagi\GoogleCharts\Charts\BubbleChart      bubbleChart(string $id = null)
 * @method static \Premmohantyagi\GoogleCharts\Charts\Histogram        histogram(string $id = null)
 * @method static \Premmohantyagi\GoogleCharts\Charts\CandlestickChart candlestickChart(string $id = null)
 * @method static \Premmohantyagi\GoogleCharts\Charts\SteppedAreaChart steppedAreaChart(string $id = null)
 * @method static \Premmohantyagi\GoogleCharts\Charts\GeoChart         geoChart(string $id = null)
 * @method static \Premmohantyagi\GoogleCharts\Charts\MapChart         mapChart(string $id = null)
 * @method static \Premmohantyagi\GoogleCharts\Charts\GaugeChart       gaugeChart(string $id = null)
 * @method static \Premmohantyagi\GoogleCharts\Charts\TableChart       tableChart(string $id = null)
 * @method static \Premmohantyagi\GoogleCharts\Charts\TimelineChart    timelineChart(string $id = null)
 * @method static \Premmohantyagi\GoogleCharts\Charts\TreeMapChart     treeMapChart(string $id = null)
 * @method static \Premmohantyagi\GoogleCharts\Charts\SankeyChart      sankeyChart(string $id = null)
 * @method static \Premmohantyagi\GoogleCharts\Charts\OrgChart         orgChart(string $id = null)
 * @method static \Premmohantyagi\GoogleCharts\Charts\CalendarChart    calendarChart(string $id = null)
 * @method static \Premmohantyagi\GoogleCharts\Charts\GanttChart       ganttChart(string $id = null)
 * @method static \Premmohantyagi\GoogleCharts\Charts\WordTreeChart    wordTreeChart(string $id = null)
 * @method static \Premmohantyagi\GoogleCharts\Charts\AnnotationChart  annotationChart(string $id = null)
 *
 * @see \Premmohantyagi\GoogleCharts\GoogleChartFactory
 */
class GoogleChart extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'google-charts';
    }
}
