<?php

namespace Premmohantyagi\GoogleCharts\Support;

class PackageMapper
{
    /**
     * Map of Google Visualization chart types to the package that provides them.
     *
     * @var array<string, string>
     */
    protected static array $map = [
        // Core charts (corechart package)
        'LineChart' => 'corechart',
        'AreaChart' => 'corechart',
        'BarChart' => 'corechart',
        'ColumnChart' => 'corechart',
        'PieChart' => 'corechart',
        'ComboChart' => 'corechart',
        'ScatterChart' => 'corechart',
        'BubbleChart' => 'corechart',
        'Histogram' => 'corechart',
        'CandlestickChart' => 'corechart',
        'SteppedAreaChart' => 'corechart',

        // Advanced charts
        'GeoChart' => 'geochart',
        'Map' => 'map',
        'Gauge' => 'gauge',
        'Table' => 'table',
        'Timeline' => 'timeline',
        'TreeMap' => 'treemap',
        'Sankey' => 'sankey',
        'OrgChart' => 'orgchart',
        'Calendar' => 'calendar',
        'Gantt' => 'gantt',
        'WordTree' => 'wordtree',
        'AnnotationChart' => 'annotationchart',
    ];

    /**
     * Resolve the package required by a given chart type.
     */
    public static function packageFor(string $type, string $default = 'corechart'): string
    {
        return static::$map[$type] ?? $default;
    }

    /**
     * Register or override the package for a chart type.
     */
    public static function register(string $type, string $package): void
    {
        static::$map[$type] = $package;
    }

    /**
     * Get the full type-to-package map.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return static::$map;
    }
}
