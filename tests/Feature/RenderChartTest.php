<?php

namespace Premmohantyagi\GoogleCharts\Tests\Feature;

use Premmohantyagi\GoogleCharts\Charts\ColumnChart;
use Premmohantyagi\GoogleCharts\Facades\GoogleChart;
use Premmohantyagi\GoogleCharts\GoogleChartFactory;
use Premmohantyagi\GoogleCharts\Tests\TestCase;

class RenderChartTest extends TestCase
{
    public function test_facade_resolves_chart_instances(): void
    {
        $chart = GoogleChart::columnChart();

        $this->assertInstanceOf(ColumnChart::class, $chart);
    }

    public function test_factory_is_bound_as_singleton(): void
    {
        $this->assertInstanceOf(GoogleChartFactory::class, $this->app->make('google-charts'));
    }

    public function test_render_outputs_container_and_script(): void
    {
        $chart = GoogleChart::columnChart('sales-chart')
            ->title('Monthly Sales')
            ->columns([['string', 'Month'], ['number', 'Sales']])
            ->rows([['Jan', 1000], ['Feb', 1500], ['Mar', 1200]])
            ->options(['height' => 400, 'legend' => ['position' => 'bottom']]);

        $html = $chart->render();

        $this->assertStringContainsString('id="sales-chart"', $html);
        $this->assertStringContainsString('google.charts.load', $html);
        $this->assertStringContainsString('new google.visualization.ColumnChart', $html);
        $this->assertStringContainsString('Monthly Sales', $html);
        $this->assertStringContainsString('"v":"Jan"', $html);
    }

    public function test_default_options_are_merged_from_config(): void
    {
        $chart = GoogleChart::columnChart();

        // From config/google-charts.php default_options.
        $this->assertSame(400, $chart->getOptions()['height']);
        $this->assertSame('100%', $chart->getOptions()['width']);
    }

    public function test_chart_renders_via_blade_component(): void
    {
        $chart = GoogleChart::pieChart('pie-1')
            ->columns([['string', 'Task'], ['number', 'Hours']])
            ->rows([['Work', 8], ['Sleep', 8]]);

        $rendered = \Illuminate\Support\Facades\Blade::render(
            '<x-google-chart :chart="$chart" />',
            ['chart' => $chart]
        );

        $this->assertStringContainsString('id="pie-1"', $rendered);
        $this->assertStringContainsString('new google.visualization.PieChart', $rendered);
    }
}
