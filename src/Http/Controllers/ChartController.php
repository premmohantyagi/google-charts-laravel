<?php

namespace Premmohantyagi\GoogleCharts\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Premmohantyagi\GoogleCharts\Contracts\Chart;
use Premmohantyagi\GoogleCharts\GoogleChartFactory;

class ChartController
{
    /**
     * Return the JSON definition of a named chart.
     */
    public function show(Request $request, GoogleChartFactory $charts, string $name): JsonResponse
    {
        abort_unless($charts->defined($name), 404);

        $chart = $charts->build($name, $request);

        $payload = $chart instanceof Chart ? $chart->toArray() : $chart;

        return new JsonResponse($payload);
    }
}
