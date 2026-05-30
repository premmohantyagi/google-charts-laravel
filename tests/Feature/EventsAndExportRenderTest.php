<?php

namespace Premmohantyagi\GoogleCharts\Tests\Feature;

use Premmohantyagi\GoogleCharts\Facades\GoogleChart;
use Premmohantyagi\GoogleCharts\Tests\TestCase;

class EventsAndExportRenderTest extends TestCase
{
    protected function chart()
    {
        return GoogleChart::columnChart('sales')
            ->columns([['string', 'Month'], ['number', 'Sales']])
            ->rows([['Jan', 1000]]);
    }

    public function test_event_handlers_are_rendered_into_the_chart_script(): void
    {
        $html = $this->chart()
            ->onSelect('function (chart, data) { console.log(chart.getSelection()); }')
            ->render();

        $this->assertStringContainsString('"select":', $html);
        $this->assertStringContainsString('chart.getSelection()', $html);
    }

    public function test_exportable_renders_a_download_button(): void
    {
        $html = $this->chart()->exportable(true, 'Save image')->render();

        $this->assertStringContainsString('data-google-chart-export="sales"', $html);
        $this->assertStringContainsString('data-google-chart-filename="sales.png"', $html);
        $this->assertStringContainsString('Save image', $html);
    }

    public function test_no_export_button_without_exportable(): void
    {
        $html = $this->chart()->render();

        // The runtime always references the attribute name, so assert the button itself is absent.
        $this->assertStringNotContainsString('class="google-chart-export"', $html);
    }

    public function test_runtime_exposes_export_helpers(): void
    {
        $html = $this->chart()->exportable()->render();

        $this->assertStringContainsString('getImageURI', $html);
        $this->assertStringContainsString('download:', $html);
    }
}
