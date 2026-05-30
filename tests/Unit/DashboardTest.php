<?php

namespace Premmohantyagi\GoogleCharts\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Premmohantyagi\GoogleCharts\Charts\ColumnChart;
use Premmohantyagi\GoogleCharts\Charts\TableChart;
use Premmohantyagi\GoogleCharts\Dashboard\Dashboard;

class DashboardTest extends TestCase
{
    protected function sampleDashboard(): Dashboard
    {
        return (new Dashboard('dash'))
            ->columns([['string', 'Name'], ['number', 'Age'], ['number', 'Donuts']])
            ->rows([['Bob', 30, 5], ['Alice', 25, 9]])
            ->control('CategoryFilter', ['filterColumnLabel' => 'Name'])
            ->chart(new ColumnChart());
    }

    public function test_packages_always_include_controls_and_are_unique(): void
    {
        $dashboard = (new Dashboard('dash'))
            ->chart(new ColumnChart())
            ->chart(new ColumnChart())
            ->chart(new TableChart());

        $packages = $dashboard->getPackages();

        $this->assertContains('controls', $packages);
        $this->assertContains('corechart', $packages);
        $this->assertContains('table', $packages);
        $this->assertSame($packages, array_values(array_unique($packages)));
    }

    public function test_controls_get_container_ids(): void
    {
        $controls = $this->sampleDashboard()->getControls();

        $this->assertSame('CategoryFilter', $controls[0]['controlType']);
        $this->assertSame('dash__control_0', $controls[0]['containerId']);
        $this->assertEquals((object) ['filterColumnLabel' => 'Name'], $controls[0]['options']);
    }

    public function test_charts_use_instance_type_and_get_container_ids(): void
    {
        $charts = $this->sampleDashboard()->getCharts();

        $this->assertSame('ColumnChart', $charts[0]['chartType']);
        $this->assertSame('dash__chart_0', $charts[0]['containerId']);
    }

    public function test_chart_accepts_a_type_string_and_a_view(): void
    {
        $dashboard = (new Dashboard('dash'))->chart('LineChart', ['view' => ['columns' => [0, 1]]]);

        $charts = $dashboard->getCharts();

        $this->assertSame('LineChart', $charts[0]['chartType']);
        $this->assertSame(['columns' => [0, 1]], $charts[0]['view']);
    }

    public function test_bindings_default_to_all_controls_driving_all_charts(): void
    {
        $dashboard = (new Dashboard('dash'))
            ->control('CategoryFilter', ['filterColumnLabel' => 'Name'])
            ->control('NumberRangeFilter', ['filterColumnLabel' => 'Age'])
            ->chart(new ColumnChart())
            ->chart(new ColumnChart());

        $this->assertSame(
            [['controls' => [0, 1], 'charts' => [0, 1]]],
            $dashboard->getBindings()
        );
    }

    public function test_explicit_bindings_are_kept(): void
    {
        $dashboard = $this->sampleDashboard()->bind(0, 0);

        $this->assertSame(
            [['controls' => [0], 'charts' => [0]]],
            $dashboard->getBindings()
        );
    }

    public function test_no_bindings_without_controls_or_charts(): void
    {
        $this->assertSame([], (new Dashboard('dash'))->getBindings());
    }
}
