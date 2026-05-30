<?php

namespace Premmohantyagi\GoogleCharts\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Premmohantyagi\GoogleCharts\Data\DataTable;

class DataTableTest extends TestCase
{
    public function test_it_builds_cols_from_readme_style_columns(): void
    {
        $table = (new DataTable())
            ->setColumns([
                ['string', 'Month'],
                ['number', 'Sales'],
            ])
            ->setRows([
                ['Jan', 1000],
                ['Feb', 1500],
            ]);

        $array = $table->toArray();

        $this->assertSame(
            [
                ['type' => 'string', 'label' => 'Month'],
                ['type' => 'number', 'label' => 'Sales'],
            ],
            $array['cols']
        );
    }

    public function test_it_wraps_row_values_in_cells(): void
    {
        $table = (new DataTable())
            ->setColumns([['string', 'Month'], ['number', 'Sales']])
            ->setRows([['Jan', 1000]]);

        $array = $table->toArray();

        $this->assertSame(
            [['c' => [['v' => 'Jan'], ['v' => 1000]]]],
            $array['rows']
        );
    }

    public function test_it_preserves_explicit_value_format_cells(): void
    {
        $table = (new DataTable())
            ->setColumns([['number', 'Sales']])
            ->setRows([[['v' => 1000, 'f' => '$1,000']]]);

        $array = $table->toArray();

        $this->assertSame(
            [['c' => [['v' => 1000, 'f' => '$1,000']]]],
            $array['rows']
        );
    }

    public function test_it_supports_associative_column_definitions(): void
    {
        $table = (new DataTable())->setColumns([
            ['type' => 'string', 'label' => 'Task', 'id' => 'task'],
        ]);

        $array = $table->toArray();

        $this->assertSame(
            [['type' => 'string', 'label' => 'Task', 'id' => 'task']],
            $array['cols']
        );
    }
}
