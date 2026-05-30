<?php

namespace Premmohantyagi\GoogleCharts\Contracts;

interface Chart
{
    /**
     * The Google Visualization class name used to draw the chart
     * (e.g. "ColumnChart", "LineChart", "PieChart").
     */
    public function getType(): string;

    /**
     * The Google Charts package the chart requires (e.g. "corechart", "table").
     */
    public function getPackage(): string;

    /**
     * The unique DOM id of the chart container.
     */
    public function getId(): string;

    /**
     * Render the chart to an HTML string (container + inline script).
     */
    public function render(): string;

    /**
     * Export the chart definition as an array (useful for AJAX/JSON loading).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
