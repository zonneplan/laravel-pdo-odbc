<?php

namespace LaravelPdoOdbc\Flavours\Snowflake\Concerns;

use Illuminate\Support\Str;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\ColumnDefinition;
use LaravelPdoOdbc\Flavours\Snowflake\Processor;
use Illuminate\Database\Grammar;

use function count;

/**
 * This code is shared between the Query and Schema grammar.
 * Mainly for correcting the values and columns.
 *
 * Values: are wrapped within single qoutes.
 * Columns and Table names: are wrapped within double qoutes.
 *
 * @mixin Grammar
 */
trait GrammarHelper
{
    /**
     * Convert an array of column names into a delimited string.
     */
    public function columnize(array $columns): string
    {
        return implode(', ', array_map([$this, 'wrapColumn'], $columns));
    }

    /**
     * Wrap a table in keyword identifiers.
     *
     * @param \Illuminate\Database\Query\Expression|string $table
     *
     * @return string
     */
    public function wrapTable($table)
    {
        if ($this->isExpression($table)) {
            return $this->getValue($table);
        }

        $table = Processor::wrapTable($table);

        return $this->wrap($this->tablePrefix.$table, true);
    }

    /**
     * Wrap the given value segments.
     *
     * @param array $segments
     *
     * @return string
     */
    protected function wrapSegments($segments)
    {
        return collect($segments)->map(function ($segment, $key) use ($segments) {
            return 0 === $key && count($segments) > 1
                ? $this->wrapTable($segment)
                // Original ->wraValue, but this is always called for columns segments
                : $this->wrapColumn($segment);
        })->implode('.');
    }

    /**
     * Wrap a single string in keyword identifiers.
     *
     * @param string|Expression $value
     *
     * @return string
     */
    protected function wrapColumn($column)
    {
        if ($this->isExpression($column)) {
            return $column->getValue($this);
        }

        if ($column instanceof ColumnDefinition) {
            $column = $column->get('name');
        }

        if ('*' !== $column) {
            if (! env('SNOWFLAKE_COLUMNS_CASE_SENSITIVE', false)) {
                return str_replace('"', '', Str::upper($column));
            }

            return '"'.str_replace('"', '""', $column).'"';
        }

        return $column;
    }

    /**
     * Wrap a single string in keyword identifiers.
     *
     * @param string $value
     *
     * @return string
     */
    protected function wrapValue($value)
    {
        if ('*' !== $value) {
            return "'".str_replace("'", "''", $value)."'";
        }

        return $value;
    }
}
