<?php

namespace Premmohantyagi\GoogleCharts\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Premmohantyagi\GoogleCharts\Support\PackageMapper;

class PackageMapperTest extends TestCase
{
    public function test_core_charts_map_to_corechart(): void
    {
        $this->assertSame('corechart', PackageMapper::packageFor('ColumnChart'));
        $this->assertSame('corechart', PackageMapper::packageFor('LineChart'));
        $this->assertSame('corechart', PackageMapper::packageFor('PieChart'));
    }

    public function test_advanced_charts_map_to_their_packages(): void
    {
        $this->assertSame('geochart', PackageMapper::packageFor('GeoChart'));
        $this->assertSame('gauge', PackageMapper::packageFor('Gauge'));
        $this->assertSame('table', PackageMapper::packageFor('Table'));
        $this->assertSame('sankey', PackageMapper::packageFor('Sankey'));
    }

    public function test_unknown_type_falls_back_to_default(): void
    {
        $this->assertSame('corechart', PackageMapper::packageFor('SomethingNew'));
        $this->assertSame('custom', PackageMapper::packageFor('SomethingNew', 'custom'));
    }
}
