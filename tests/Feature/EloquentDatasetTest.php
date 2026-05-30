<?php

namespace Premmohantyagi\GoogleCharts\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Premmohantyagi\GoogleCharts\Facades\GoogleChart;
use Premmohantyagi\GoogleCharts\Tests\TestCase;

class EloquentDatasetTest extends TestCase
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['db']->connection()->getSchemaBuilder()->create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('month');
            $table->integer('total');
        });

        DatasetOrder::insert([
            ['month' => 'Jan', 'total' => 1000],
            ['month' => 'Feb', 'total' => 1500],
            ['month' => 'Mar', 'total' => 1200],
        ]);
    }

    public function test_chart_can_be_built_from_a_query_builder(): void
    {
        $chart = GoogleChart::columnChart()->fromQuery(
            DatasetOrder::query()->orderBy('id'),
            [
                ['string', 'Month', 'month'],
                ['number', 'Sales', 'total'],
            ]
        );

        $rows = $chart->getDataTable()['rows'];

        $this->assertCount(3, $rows);
        $this->assertSame(['v' => 'Jan'], $rows[0]['c'][0]);
        $this->assertSame(['v' => 1000], $rows[0]['c'][1]);
        $this->assertSame(['v' => 1200], $rows[2]['c'][1]);
    }

    public function test_chart_can_be_built_from_an_eloquent_collection(): void
    {
        $chart = GoogleChart::lineChart()->dataset(
            DatasetOrder::orderBy('id')->get(),
            [
                ['string', 'Month', 'month'],
                ['number', 'Sales', 'total'],
            ]
        );

        $cols = $chart->getDataTable()['cols'];
        $rows = $chart->getDataTable()['rows'];

        $this->assertSame(
            [['type' => 'string', 'label' => 'Month'], ['type' => 'number', 'label' => 'Sales']],
            $cols
        );
        $this->assertSame(['v' => 'Feb'], $rows[1]['c'][0]);
    }

    public function test_closure_column_maps_model_attributes(): void
    {
        $chart = GoogleChart::columnChart()->dataset(
            DatasetOrder::orderBy('id')->get(),
            [
                ['string', 'Label', fn (DatasetOrder $o) => strtoupper($o->month)],
                ['number', 'Doubled', fn (DatasetOrder $o) => $o->total * 2],
            ]
        );

        $rows = $chart->getDataTable()['rows'];

        $this->assertSame(['v' => 'JAN'], $rows[0]['c'][0]);
        $this->assertSame(['v' => 2000], $rows[0]['c'][1]);
    }
}

class DatasetOrder extends Model
{
    protected $table = 'orders';

    protected $guarded = [];

    public $timestamps = false;
}
