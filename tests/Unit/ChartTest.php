<?php

namespace Premmohantyagi\GoogleCharts\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Premmohantyagi\GoogleCharts\Charts\ColumnChart;
use Premmohantyagi\GoogleCharts\Charts\DonutChart;
use Premmohantyagi\GoogleCharts\Charts\GeoChart;

class ChartTest extends TestCase
{
    public function test_fluent_api_returns_self_and_builds_definition(): void
    {
        $chart = (new ColumnChart('my-chart'))
            ->title('Monthly Sales')
            ->columns([['string', 'Month'], ['number', 'Sales']])
            ->rows([['Jan', 1000], ['Feb', 1500]])
            ->options(['legend' => ['position' => 'bottom']]);

        $array = $chart->toArray();

        $this->assertSame('my-chart', $array['id']);
        $this->assertSame('ColumnChart', $array['type']);
        $this->assertSame('corechart', $array['package']);
        $this->assertSame('Monthly Sales', $array['options']['title']);
        $this->assertSame('bottom', $array['options']['legend']['position']);
        $this->assertCount(2, $array['dataTable']['rows']);
    }

    public function test_set_uses_dot_notation(): void
    {
        $chart = (new ColumnChart())->set('hAxis.title', 'Month');

        $this->assertSame('Month', $chart->getOptions()['hAxis']['title']);
    }

    public function test_donut_chart_defaults_to_pie_with_hole(): void
    {
        $chart = new DonutChart();

        $this->assertSame('PieChart', $chart->getType());
        $this->assertSame(0.4, $chart->getOptions()['pieHole']);
    }

    public function test_geo_chart_resolves_geochart_package(): void
    {
        $this->assertSame('geochart', (new GeoChart())->getPackage());
    }

    public function test_package_can_be_overridden(): void
    {
        $chart = (new ColumnChart())->package('custompackage');

        $this->assertSame('custompackage', $chart->getPackage());
    }
}
