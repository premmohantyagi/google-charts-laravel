<?php

namespace Premmohantyagi\GoogleCharts\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Premmohantyagi\GoogleCharts\Charts\ColumnChart;

class DatasetTest extends TestCase
{
    public function test_dataset_maps_array_rows_with_explicit_columns(): void
    {
        $chart = (new ColumnChart())->dataset(
            [
                ['month' => 'Jan', 'total' => 1000],
                ['month' => 'Feb', 'total' => 1500],
            ],
            [
                ['string', 'Month', 'month'],
                ['number', 'Sales', 'total'],
            ]
        );

        $table = $chart->getDataTable();

        $this->assertSame(
            [['type' => 'string', 'label' => 'Month'], ['type' => 'number', 'label' => 'Sales']],
            $table['cols']
        );
        $this->assertSame(
            [
                ['c' => [['v' => 'Jan'], ['v' => 1000]]],
                ['c' => [['v' => 'Feb'], ['v' => 1500]]],
            ],
            $table['rows']
        );
    }

    public function test_dataset_accepts_a_collection_of_objects(): void
    {
        $items = collect([
            (object) ['name' => 'A', 'count' => 5],
            (object) ['name' => 'B', 'count' => 9],
        ]);

        $chart = (new ColumnChart())->dataset($items, [
            ['string', 'Name', 'name'],
            ['number', 'Count', 'count'],
        ]);

        $rows = $chart->getDataTable()['rows'];

        $this->assertSame(['v' => 'A'], $rows[0]['c'][0]);
        $this->assertSame(['v' => 9], $rows[1]['c'][1]);
    }

    public function test_dataset_supports_closure_columns(): void
    {
        $chart = (new ColumnChart())->dataset(
            [['price' => 10, 'qty' => 3]],
            [
                ['string', 'Line', fn ($row) => 'item'],
                ['number', 'Revenue', fn ($row) => $row['price'] * $row['qty']],
            ]
        );

        $rows = $chart->getDataTable()['rows'];

        $this->assertSame(['v' => 'item'], $rows[0]['c'][0]);
        $this->assertSame(['v' => 30], $rows[0]['c'][1]);
    }

    public function test_dataset_derives_columns_and_infers_types_when_omitted(): void
    {
        $chart = (new ColumnChart())->dataset([
            ['month' => 'Jan', 'total' => 1000],
        ]);

        $cols = $chart->getDataTable()['cols'];

        $this->assertSame(
            [
                ['type' => 'string', 'label' => 'month'],
                ['type' => 'number', 'label' => 'total'],
            ],
            $cols
        );
    }

    public function test_dataset_reads_dot_notation_fields(): void
    {
        $chart = (new ColumnChart())->dataset(
            [['label' => 'X', 'meta' => ['value' => 42]]],
            [
                ['string', 'Label', 'label'],
                ['number', 'Value', 'meta.value'],
            ]
        );

        $rows = $chart->getDataTable()['rows'];

        $this->assertSame(['v' => 42], $rows[0]['c'][1]);
    }

    public function test_short_string_column_specs_use_field_as_label(): void
    {
        $chart = (new ColumnChart())->dataset(
            [['month' => 'Jan', 'total' => 1000]],
            ['month', 'total']
        );

        $cols = $chart->getDataTable()['cols'];

        $this->assertSame(
            [
                ['type' => 'string', 'label' => 'month'],
                ['type' => 'number', 'label' => 'total'],
            ],
            $cols
        );
    }
}
