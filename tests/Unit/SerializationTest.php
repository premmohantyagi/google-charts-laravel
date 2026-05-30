<?php

namespace Premmohantyagi\GoogleCharts\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Premmohantyagi\GoogleCharts\Charts\ColumnChart;

class SerializationTest extends TestCase
{
    public function test_chart_serializes_to_array(): void
    {
        $chart = (new ColumnChart('c1'))
            ->columns([['string', 'Month'], ['number', 'Sales']])
            ->rows([['Jan', 1000]]);

        $array = $chart->toArray();

        $this->assertSame('c1', $array['id']);
        $this->assertSame('ColumnChart', $array['type']);
        $this->assertSame('corechart', $array['package']);
        $this->assertArrayHasKey('dataTable', $array);
        $this->assertArrayHasKey('options', $array);
    }

    public function test_chart_serializes_to_json(): void
    {
        $chart = (new ColumnChart('c1'))
            ->columns([['string', 'Month'], ['number', 'Sales']])
            ->rows([['Jan', 1000]]);

        $decoded = json_decode($chart->toJson(), true);

        $this->assertSame($chart->toArray(), $decoded);
        $this->assertSame($chart->toArray(), $chart->jsonSerialize());
    }

    public function test_ajax_url_marks_chart_async(): void
    {
        $chart = new ColumnChart('c1');

        $this->assertFalse($chart->isAjax());
        $this->assertNull($chart->getAjaxUrl());

        $chart->ajax('/charts/sales');

        $this->assertTrue($chart->isAjax());
        $this->assertSame('/charts/sales', $chart->getAjaxUrl());
    }
}
