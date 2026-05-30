<?php

namespace Premmohantyagi\GoogleCharts\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Premmohantyagi\GoogleCharts\Charts\ColumnChart;

class EventsAndExportTest extends TestCase
{
    public function test_on_registers_a_handler(): void
    {
        $chart = (new ColumnChart())->on('select', 'handler');

        $this->assertSame(['select' => 'handler'], $chart->getEvents());
    }

    public function test_event_convenience_methods_map_to_event_names(): void
    {
        $chart = (new ColumnChart())
            ->onSelect('a')
            ->onReady('b')
            ->onError('c')
            ->onMouseOver('d')
            ->onMouseOut('e');

        $this->assertSame(
            [
                'select' => 'a',
                'ready' => 'b',
                'error' => 'c',
                'onmouseover' => 'd',
                'onmouseout' => 'e',
            ],
            $chart->getEvents()
        );
    }

    public function test_export_is_off_by_default(): void
    {
        $this->assertFalse((new ColumnChart())->isExportable());
    }

    public function test_exportable_sets_flag_label_and_filename(): void
    {
        $chart = (new ColumnChart('sales'))->exportable();

        $this->assertTrue($chart->isExportable());
        $this->assertSame('Download PNG', $chart->getExportLabel());
        $this->assertSame('sales.png', $chart->getExportFilename());
    }

    public function test_export_label_and_filename_can_be_customized(): void
    {
        $chart = (new ColumnChart('sales'))
            ->exportable(true, 'Save image')
            ->exportFilename('monthly-sales.png');

        $this->assertSame('Save image', $chart->getExportLabel());
        $this->assertSame('monthly-sales.png', $chart->getExportFilename());
    }
}
