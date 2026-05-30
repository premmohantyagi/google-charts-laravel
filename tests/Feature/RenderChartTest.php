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
        $this->assertStringContainsString('window.GoogleChartsLaravel.render(', $html);
        $this->assertStringContainsString('type: "ColumnChart"', $html);
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
        $this->assertStringContainsString('type: "PieChart"', $rendered);
    }

    public function test_runtime_is_emitted_once_for_multiple_charts(): void
    {
        $a = GoogleChart::columnChart('chart-a')
            ->columns([['string', 'Month'], ['number', 'Sales']])
            ->rows([['Jan', 1000]]);

        $b = GoogleChart::lineChart('chart-b')
            ->columns([['string', 'Month'], ['number', 'Sales']])
            ->rows([['Jan', 1000]]);

        $html = \Illuminate\Support\Facades\Blade::render(
            '{!! $a->render() !!}{!! $b->render() !!}',
            ['a' => $a, 'b' => $b]
        );

        // The shared runtime is defined exactly once, but each chart registers itself.
        $this->assertSame(1, substr_count($html, 'window.GoogleChartsLaravel = runtime'));
        $this->assertSame(2, substr_count($html, 'window.GoogleChartsLaravel.render('));
        $this->assertStringContainsString('id="chart-a"', $html);
        $this->assertStringContainsString('id="chart-b"', $html);
    }
}
