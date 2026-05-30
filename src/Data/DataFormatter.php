<?php

namespace Premmohantyagi\GoogleCharts\Data;

/**
 * Column value formatters (number, date, pattern, arrow, bar, color).
 *
 * This is a placeholder for the v0.1.2+ formatting feature. Formatters declared
 * here are serialized into the chart payload and applied client-side before the
 * chart is drawn. The full implementation is intentionally deferred to a later
 * release; the class exists now so the package structure is complete.
 */
class DataFormatter
{
    /**
     * Declared formatters, keyed by column index.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $formatters = [];

    /**
     * Apply a NumberFormat to a column.
     *
     * @param  array<string, mixed>  $options
     */
    public function number(int $column, array $options = []): self
    {
        return $this->add($column, 'NumberFormat', $options);
    }

    /**
     * Apply a DateFormat to a column.
     *
     * @param  array<string, mixed>  $options
     */
    public function date(int $column, array $options = []): self
    {
        return $this->add($column, 'DateFormat', $options);
    }

    /**
     * Apply a PatternFormat to a set of source columns.
     *
     * @param  array<int, int>  $sourceColumns
     */
    public function pattern(array $sourceColumns, int $destinationColumn, string $pattern): self
    {
        $this->formatters[] = [
            'type' => 'PatternFormat',
            'sourceColumns' => $sourceColumns,
            'destinationColumn' => $destinationColumn,
            'pattern' => $pattern,
        ];

        return $this;
    }

    /**
     * Register a formatter for a column.
     *
     * @param  array<string, mixed>  $options
     */
    public function add(int $column, string $type, array $options = []): self
    {
        $this->formatters[] = [
            'type' => $type,
            'column' => $column,
            'options' => $options,
        ];

        return $this;
    }

    /**
     * Whether any formatters have been declared.
     */
    public function isEmpty(): bool
    {
        return $this->formatters === [];
    }

    /**
     * Export the declared formatters.
     *
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        return $this->formatters;
    }
}
