<?php

namespace Premmohantyagi\GoogleCharts\Charts;

/**
 * A placeholder chart whose definition is loaded from a URL at draw time.
 *
 * The chart type, packages, data, and options all come from the endpoint
 * response, so this class only carries the container id, dimensions, and the
 * URL to fetch. It is returned by GoogleChart::async() and is always used with
 * ajax().
 */
class AjaxChart extends BaseChart
{
    public function getType(): string
    {
        return 'AjaxChart';
    }
}
