<?php

namespace Premmohantyagi\GoogleCharts\Tests\Feature;

use Premmohantyagi\GoogleCharts\Dashboard\Dashboard;
use Premmohantyagi\GoogleCharts\Facades\GoogleChart;
use Premmohantyagi\GoogleCharts\Tests\TestCase;

class RenderDashboardTest extends TestCase
{
    public function test_factory_creates_a_dashboard(): void
    {
        $this->assertInstanceOf(Dashboard::class, GoogleChart::dashboard());
    }

    public function test_dashboard_renders_containers_and_script(): void
    {
        $dashboard = GoogleChart::dashboard('sales-dashboard')
            ->columns([['string', 'Name'], ['number', 'Age'], ['number', 'Donuts']])
            ->rows([['Bob', 30, 5], ['Alice', 25, 9], ['Carol', 40, 3]])
            ->control('CategoryFilter', ['filterColumnLabel' => 'Name'])
            ->chart(GoogleChart::columnChart()->title('Donuts eaten'));

        $html = $dashboard->render();

        $this->assertStringContainsString('id="sales-dashboard"', $html);
        $this->assertStringContainsString('id="sales-dashboard__control_0"', $html);
        $this->assertStringContainsString('id="sales-dashboard__chart_0"', $html);
        $this->assertStringContainsString('window.GoogleChartsLaravel.dashboard(', $html);
        $this->assertStringContainsString('"controlType":"CategoryFilter"', $html);
        $this->assertStringContainsString('"chartType":"ColumnChart"', $html);
        $this->assertStringContainsString('"controls"', $html);
        $this->assertStringContainsString('Donuts eaten', $html);
    }

    public function test_dashboard_loads_the_controls_package(): void
    {
        $dashboard = GoogleChart::dashboard()
            ->columns([['string', 'Name'], ['number', 'Age']])
            ->rows([['Bob', 30]])
            ->control('NumberRangeFilter', ['filterColumnLabel' => 'Age'])
            ->chart(GoogleChart::columnChart());

        $html = $dashboard->render();

        $this->assertStringContainsString('controls', $html);
        $this->assertStringContainsString('google.visualization.Dashboard', $html);
    }
}
