<?php

namespace Premmohantyagi\GoogleCharts\Tests\Feature;

use Premmohantyagi\GoogleCharts\Tests\TestCase;

class BuilderDisabledTest extends TestCase
{
    public function test_builder_route_is_absent_when_disabled(): void
    {
        // Builder is disabled by default; the route should not be registered.
        $this->get('/google-charts/builder')->assertNotFound();
    }
}
