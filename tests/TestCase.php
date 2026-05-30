<?php

namespace Premmohantyagi\GoogleCharts\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Premmohantyagi\GoogleCharts\GoogleChartsServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            GoogleChartsServiceProvider::class,
        ];
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app): array
    {
        return [
            'GoogleChart' => \Premmohantyagi\GoogleCharts\Facades\GoogleChart::class,
        ];
    }
}
