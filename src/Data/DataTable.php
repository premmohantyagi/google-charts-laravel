<?php

namespace Premmohantyagi\GoogleCharts\Data;

use Illuminate\Contracts\Support\Arrayable as ArrayableContract;
use Traversable;

class DataTable
{
    /**
     * Column definitions in Google's DataTable JSON form.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $cols = [];

    /**
     * Row definitions in Google's DataTable JSON form.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $rows = [];

    /**
     * Add a single column.
     *
     * @param  string       $type   string|number|boolean|date|datetime|timeofday
     * @param  string|null  $label  Human readable column label.
     * @param  string|null  $id     Optional column id.
     * @param  string|null  $role   Optional column role (e.g. "annotation", "tooltip").
     */
    public function addColumn(string $type, ?string $label = null, ?string $id = null, ?string $role = null): self
    {
        $col = ['type' => $type];

        if ($label !== null) {
            $col['label'] = $label;
        }

        if ($id !== null) {
            $col['id'] = $id;
        }

        if ($role !== null) {
            $col['role'] = $role;
            $col['p'] = ['role' => $role];
        }

        $this->cols[] = $col;

        return $this;
    }

    /**
     * Define all columns at once.
     *
     * Each entry may be:
     *   - an indexed array [type, label, id?, role?]   (matches README usage)
     *   - an associative array ['type' => ..., 'label' => ..., 'id' => ..., 'role' => ...]
     *   - a plain string (treated as the column type)
     *
     * @param  array<int, mixed>  $columns
     */
    public function setColumns(array $columns): self
    {
        $this->cols = [];

        foreach ($columns as $column) {
            $this->addColumn(...$this->normalizeColumn($column));
        }

        return $this;
    }

    /**
     * Add a single row of values.
     *
     * Each value may be a scalar, or an array ['v' => value, 'f' => formatted].
     *
     * @param  array<int, mixed>  $values
     */
    public function addRow(array $values): self
    {
        $cells = [];

        foreach ($values as $value) {
            $cells[] = $this->normalizeCell($value);
        }

        $this->rows[] = ['c' => $cells];

        return $this;
    }

    /**
     * Define all rows at once.
     *
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    public function setRows(iterable $rows): self
    {
        $this->rows = [];

        foreach ($rows as $row) {
            $this->addRow($this->toPlainArray($row));
        }

        return $this;
    }

    /**
     * Whether any columns have been defined.
     */
    public function hasColumns(): bool
    {
        return $this->cols !== [];
    }

    /**
     * The DataTable as a Google-compatible array ({cols, rows}).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'cols' => $this->cols,
            'rows' => $this->rows,
        ];
    }

    /**
     * Normalize a column definition into addColumn() arguments.
     *
     * @param  mixed  $column
     * @return array{0: string, 1: ?string, 2: ?string, 3: ?string}
     */
    protected function normalizeColumn($column): array
    {
        if (is_string($column)) {
            return [$column, null, null, null];
        }

        if (is_array($column) && $this->isAssoc($column)) {
            return [
                (string) ($column['type'] ?? 'string'),
                isset($column['label']) ? (string) $column['label'] : null,
                isset($column['id']) ? (string) $column['id'] : null,
                isset($column['role']) ? (string) $column['role'] : null,
            ];
        }

        // Indexed array [type, label, id?, role?] (README form).
        $column = array_values((array) $column);

        return [
            (string) ($column[0] ?? 'string'),
            isset($column[1]) ? (string) $column[1] : null,
            isset($column[2]) ? (string) $column[2] : null,
            isset($column[3]) ? (string) $column[3] : null,
        ];
    }

    /**
     * Normalize a single cell value into Google's {v, f} form.
     *
     * @param  mixed  $value
     * @return array<string, mixed>
     */
    protected function normalizeCell($value): array
    {
        if (is_array($value) && (array_key_exists('v', $value) || array_key_exists('f', $value))) {
            return $value;
        }

        return ['v' => $value];
    }

    /**
     * Coerce an iterable row into a plain indexed array of values.
     *
     * @param  mixed  $row
     * @return array<int, mixed>
     */
    protected function toPlainArray($row): array
    {
        if ($row instanceof ArrayableContract) {
            $row = $row->toArray();
        } elseif ($row instanceof Traversable) {
            $row = iterator_to_array($row);
        }

        return array_values((array) $row);
    }

    /**
     * Determine if an array is associative.
     *
     * @param  array<mixed>  $array
     */
    protected function isAssoc(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }
}
