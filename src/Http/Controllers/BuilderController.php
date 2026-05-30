<?php

namespace Premmohantyagi\GoogleCharts\Http\Controllers;

use Illuminate\Contracts\View\View;
use Premmohantyagi\GoogleCharts\GoogleChartFactory;

class BuilderController
{
    /**
     * Render the visual dashboard builder page.
     */
    public function index(GoogleChartFactory $charts): View
    {
        return view('google-charts::builder-page', [
            'chartTypes' => $charts->chartTypes(),
        ]);
    }
}
