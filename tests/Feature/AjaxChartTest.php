<?php

namespace Premmohantyagi\GoogleCharts\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Premmohantyagi\GoogleCharts\Charts\AjaxChart;
use Premmohantyagi\GoogleCharts\Facades\GoogleChart;
use Premmohantyagi\GoogleCharts\Tests\TestCase;

class AjaxChartTest extends TestCase
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('google-charts.route.enabled', true);
        $app['config']->set('google-charts.route.middleware', []);
    }

    protected function defineSalesChart(): void
    {
        GoogleChart::define('sales', function ($request) {
            return GoogleChart::columnChart('sales')
                ->title($request->query('title', 'Sales'))
                ->columns([['string', 'Month'], ['number', 'Sales']])
                ->rows([['Jan', 1000], ['Feb', 1500]]);
        });
    }

    public function test_ajax_chart_renders_a_loader_placeholder(): void
    {
        $chart = GoogleChart::columnChart('async-1')->ajax('/charts/sales');

        $html = $chart->render();

        $this->assertStringContainsString('id="async-1"', $html);
        $this->assertStringContainsString('window.GoogleChartsLaravel.load(', $html);
        // The URL is emitted as a JSON string (slashes are escaped, which JS reads as "/").
        $this->assertStringContainsString(json_encode('/charts/sales'), $html);
        // The data is not serialized into the page for an async chart.
        $this->assertStringNotContainsString('window.GoogleChartsLaravel.render(', $html);
    }

    public function test_named_chart_is_served_as_json(): void
    {
        $this->defineSalesChart();

        $response = $this->getJson('/google-charts/sales');

        $response->assertOk();
        $response->assertJsonPath('type', 'ColumnChart');
        $response->assertJsonPath('options.title', 'Sales');
        $response->assertJsonStructure(['id', 'type', 'package', 'dataTable' => ['cols', 'rows'], 'options']);
    }

    public function test_request_parameters_reach_the_builder(): void
    {
        $this->defineSalesChart();

        $response = $this->getJson('/google-charts/sales?title=Revenue');

        $response->assertJsonPath('options.title', 'Revenue');
    }

    public function test_unknown_chart_returns_404(): void
    {
        $this->getJson('/google-charts/does-not-exist')->assertNotFound();
    }

    public function test_async_helper_builds_placeholder_pointing_at_the_route(): void
    {
        $chart = GoogleChart::async('sales');

        $this->assertInstanceOf(AjaxChart::class, $chart);
        $this->assertTrue($chart->isAjax());
        $this->assertSame(route('google-charts.show', ['name' => 'sales']), $chart->getAjaxUrl());
    }

    public function test_chart_returned_from_a_controller_is_json(): void
    {
        Route::get('/_test/inline-chart', function () {
            return GoogleChart::pieChart('p1')
                ->columns([['string', 'Task'], ['number', 'Hours']])
                ->rows([['Work', 8]]);
        });

        $this->getJson('/_test/inline-chart')
            ->assertOk()
            ->assertJsonPath('type', 'PieChart');
    }
}
