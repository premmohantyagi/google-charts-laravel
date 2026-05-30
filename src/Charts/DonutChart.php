<?php

namespace Premmohantyagi\GoogleCharts\Charts;

/**
 * Google Charts has no dedicated "donut" type. A donut is a PieChart with a
 * non-zero "pieHole", so this subclass applies a default pieHole that can still
 * be overridden via options().
 */
class DonutChart extends PieChart
{
    public function __construct(?string $id = null)
    {
        parent::__construct($id);

        $this->options['pieHole'] = 0.4;
    }
}
