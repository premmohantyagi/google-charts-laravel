<?php

namespace Premmohantyagi\GoogleCharts\Concerns;

use Illuminate\Contracts\Support\Arrayable;
use Premmohantyagi\GoogleCharts\Data\DataTable;
use Traversable;

/**
 * Shared column/row building used by both charts and dashboards. Provides the
 * fluent data API (columns, rows, data, dataset, fromQuery) and produces the
 * Google DataTable payload.
 */
trait BuildsDataTable
{
    /**
     * Column definitions (README form: [type, label]).
     *
     * @var array<int, mixed>
     */
    protected array $columns = [];

    /**
     * Row values.
     *
     * @var array<int, mixed>
     */
    protected array $rows = [];

    /**
     * Define the columns.
     *
     * @param  array<int, mixed>  $columns
     */
    public function columns(array $columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * Define the rows.
     *
     * @param  iterable<int, mixed>  $rows
     */
    public function rows(iterable $rows): self
    {
        $this->rows = $this->toArrayValue($rows);

        return $this;
    }

    /**
     * Append a single row.
     *
     * @param  array<int, mixed>  $row
     */
    public function addRow(array $row): self
    {
        $this->rows[] = $row;

        return $this;
    }

    /**
     * Set data (rows). Accepts arrays, Collections, or anything iterable.
     *
     * @param  iterable<int, mixed>  $data
     */
    public function data(iterable $data): self
    {
        return $this->rows($data);
    }

    /**
     * Build columns and rows from a dataset of arrays, objects, Eloquent models,
     * a Collection, or a query builder.
     *
     * The $columns argument maps each column to a source field. Each spec may be:
     *   - a string field name:            'total'
     *   - an indexed array:               [type, label, field]   (field defaults to label)
     *   - an associative array:           ['type' => ..., 'label' => ..., 'field' => ...]
     *   - a closure for a computed value: ['number', 'Sales', fn ($row) => $row->price * $row->qty]
     *
     * When $columns is omitted, columns are derived from the keys/attributes of the
     * first item. Values are read with Laravel's data_get(), so dot notation works.
     *
     * @param  mixed              $source
     * @param  array<int, mixed>  $columns
     */
    public function dataset($source, array $columns = []): self
    {
        $items = $this->resolveItems($source);

        if ($columns === []) {
            $columns = $this->deriveDatasetColumns($items);
        }

        $columnDefs = [];
        $fields = [];

        foreach ($columns as $spec) {
            [$type, $label, $field] = $this->normalizeDatasetColumn($spec, $items);
            $columnDefs[] = [$type, $label];
            $fields[] = $field;
        }

        $this->columns = $columnDefs;
        $this->rows = [];

        foreach ($items as $item) {
            $row = [];
            foreach ($fields as $field) {
                $row[] = $field instanceof \Closure ? $field($item) : data_get($item, $field);
            }
            $this->rows[] = $row;
        }

        return $this;
    }

    /**
     * Build columns and rows from a query builder (the query is executed for you).
     *
     * @param  mixed              $query
     * @param  array<int, mixed>  $columns
     */
    public function fromQuery($query, array $columns = []): self
    {
        return $this->dataset($query, $columns);
    }

    /**
     * Build the Google DataTable payload.
     *
     * @return array<string, mixed>
     */
    public function getDataTable(): array
    {
        $table = new DataTable();

        if ($this->columns !== []) {
            $table->setColumns($this->columns);
        }

        $table->setRows($this->rows);

        return $table->toArray();
    }

    /**
     * Coerce an iterable into a plain array.
     *
     * @param  iterable<int, mixed>  $value
     * @return array<int, mixed>
     */
    protected function toArrayValue(iterable $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value instanceof Arrayable) {
            return $value->toArray();
        }

        if ($value instanceof Traversable) {
            return iterator_to_array($value);
        }

        return (array) $value;
    }

    /**
     * Resolve a dataset source into a plain list of items, executing a query builder
     * and unwrapping a Collection without deep-converting its items to arrays.
     *
     * @param  mixed  $source
     * @return array<int, mixed>
     */
    protected function resolveItems($source): array
    {
        if ($source instanceof \Illuminate\Contracts\Database\Query\Builder
            || $source instanceof \Illuminate\Database\Eloquent\Builder
            || $source instanceof \Illuminate\Database\Query\Builder) {
            $source = $source->get();
        }

        if ($source instanceof \Illuminate\Support\Enumerable) {
            return array_values($source->all());
        }

        if (is_array($source)) {
            return array_values($source);
        }

        return array_values($this->toArrayValue($source));
    }

    /**
     * Derive column specs from the keys/attributes of the first dataset item.
     *
     * @param  array<int, mixed>  $items
     * @return array<int, array{0: string, 1: string}>
     */
    protected function deriveDatasetColumns(array $items): array
    {
        if ($items === []) {
            return [];
        }

        $first = reset($items);

        return array_map(function ($key) use ($first) {
            return [$this->inferType(data_get($first, $key)), (string) $key];
        }, $this->keysOf($first));
    }

    /**
     * Normalize a single dataset column spec into [type, label, field].
     *
     * @param  mixed              $spec
     * @param  array<int, mixed>  $items
     * @return array{0: string, 1: string, 2: mixed}
     */
    protected function normalizeDatasetColumn($spec, array $items): array
    {
        if ($spec instanceof \Closure) {
            return ['string', 'value', $spec];
        }

        if (is_string($spec)) {
            return [$this->inferType($this->sampleValue($items, $spec)), $spec, $spec];
        }

        if (is_array($spec) && $this->isAssoc($spec)) {
            $field = $spec['field'] ?? ($spec['label'] ?? null);
            $label = $spec['label'] ?? (is_string($field) ? $field : 'value');
            $type = $spec['type'] ?? $this->inferType($this->sampleValue($items, $field));

            return [(string) $type, (string) $label, $field];
        }

        $spec = array_values((array) $spec);

        if (count($spec) === 1) {
            $field = $spec[0];
            $label = is_string($field) ? $field : 'value';

            return [$this->inferType($this->sampleValue($items, $field)), $label, $field];
        }

        $type = (string) ($spec[0] ?? 'string');
        $label = (string) ($spec[1] ?? $type);
        $field = array_key_exists(2, $spec) ? $spec[2] : $label;

        return [$type, $label, $field];
    }

    /**
     * Read the first item's value for a field, used for type inference.
     *
     * @param  array<int, mixed>  $items
     * @param  mixed              $field
     * @return mixed
     */
    protected function sampleValue(array $items, $field)
    {
        if ($items === []) {
            return null;
        }

        $first = reset($items);

        return $field instanceof \Closure ? $field($first) : data_get($first, $field);
    }

    /**
     * The keys/attributes of a dataset item.
     *
     * @param  mixed  $item
     * @return array<int, int|string>
     */
    protected function keysOf($item): array
    {
        if (is_array($item)) {
            return array_keys($item);
        }

        if ($item instanceof Arrayable) {
            return array_keys($item->toArray());
        }

        if (is_object($item)) {
            return array_keys(get_object_vars($item));
        }

        return [];
    }

    /**
     * Infer a Google Charts column type from a sample value.
     *
     * @param  mixed  $value
     */
    protected function inferType($value): string
    {
        if (is_int($value) || is_float($value)) {
            return 'number';
        }

        if (is_bool($value)) {
            return 'boolean';
        }

        return 'string';
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
