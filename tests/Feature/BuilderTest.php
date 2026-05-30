<?php

namespace Premmohantyagi\GoogleCharts\Tests\Feature;

use Premmohantyagi\GoogleCharts\Tests\TestCase;

class BuilderTest extends TestCase
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('google-charts.builder.enabled', true);
        $app['config']->set('google-charts.builder.middleware', []);
    }

    public function test_builder_page_is_served_when_enabled(): void
    {
        $response = $this->get('/google-charts/builder');

        $response->assertOk();
        $response->assertSee('Chart &amp; Dashboard Builder', false);
        $response->assertSee('class="gc-builder"', false);
        $response->assertSee('class="gcb-preview"', false);
    }

    public function test_builder_lists_chart_types(): void
    {
        $response = $this->get('/google-charts/builder');

        $response->assertSee('value="columnChart"', false);
        $response->assertSee('Column Chart', false);
        $response->assertSee('value="geoChart"', false);
        $response->assertSee('data-package="geochart"', false);
    }

    public function test_builder_includes_the_runtime_and_code_outputs(): void
    {
        $response = $this->get('/google-charts/builder');

        $response->assertSee('window.GoogleChartsLaravel', false);
        $response->assertSee('class="gcb-php"', false);
        $response->assertSee('class="gcb-json"', false);
    }
}
