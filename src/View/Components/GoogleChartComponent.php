<?php

namespace Premmohantyagi\GoogleCharts\View\Components;

use Illuminate\View\Component;
use Premmohantyagi\GoogleCharts\Contracts\Chart;

class GoogleChartComponent extends Component
{
    /**
     * The chart instance to render.
     */
    public Chart $chart;

    public function __construct(Chart $chart)
    {
        $this->chart = $chart;
    }

    /**
     * Render the component. The chart renders itself (container + inline script).
     *
     * @return \Illuminate\Contracts\Support\Htmlable|string
     */
    public function render()
    {
        return new \Illuminate\Support\HtmlString($this->chart->render());
    }
}
