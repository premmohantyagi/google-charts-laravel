<?php

namespace Premmohantyagi\GoogleCharts\Support;

class ChartIdGenerator
{
    /**
     * Running counter to guarantee uniqueness within a single request.
     */
    protected static int $counter = 0;

    /**
     * Generate a unique, DOM-safe chart id.
     */
    public static function generate(string $prefix = 'gchart'): string
    {
        static::$counter++;

        return $prefix . '_' . static::$counter . '_' . substr(md5(uniqid((string) static::$counter, true)), 0, 8);
    }
}
