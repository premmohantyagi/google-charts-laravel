<?php

namespace Premmohantyagi\GoogleCharts\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Premmohantyagi\GoogleCharts\GoogleChartFactory;

class ChartTypesTest extends TestCase
{
    public function test_chart_types_metadata_is_returned(): void
    {
        $types = (new GoogleChartFactory())->chartTypes();

        $byMethod = [];
        foreach ($types as $type) {
            $byMethod[$type['method']] = $type;
        }

        $this->assertSame('Column Chart', $byMethod['columnChart']['label']);
        $this->assertSame('ColumnChart', $byMethod['columnChart']['type']);
        $this->assertSame('corechart', $byMethod['columnChart']['package']);

        $this->assertSame('Geo Chart', $byMethod['geoChart']['label']);
        $this->assertSame('geochart', $byMethod['geoChart']['package']);
    }
}
