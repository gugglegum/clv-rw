<?php

declare(strict_types=1);

namespace gugglegum\ClvRw;

use Countable;
use Iterator;

/**
 * Stores an ordered set of CLV column definitions.
 *
 * @implements Iterator<int, Column>
 */
final class ColumnsSet implements Iterator, Countable
{
    /**
     * Current iterator position.
     */
    private int $position = 0;

    /**
     * Ordered column definitions.
     *
     * @var Column[]
     */
    private array $columns = [];

    /**
     * Creates a columns set.
     *
     * @param Column[] $columns Ordered column definitions.
     */
    public function __construct(array $columns)
    {
        $this->setColumns($columns);
    }

    /**
     * Returns all column definitions.
     *
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Replaces all column definitions.
     *
     * @param Column[] $columns Ordered column definitions.
     * @return self
     */
    public function setColumns(array $columns): self
    {
        $this->clearColumns();
        foreach ($columns as $column) {
            $this->addColumn($column);
        }
        return $this;
    }

    /**
     * Removes all column definitions.
     */
    public function clearColumns(): self
    {
        $this->columns = [];
        return $this;
    }

    /**
     * Appends a column definition.
     *
     * @param Column $column Column definition to append.
     * @return self
     */
    public function addColumn(Column $column): self
    {
        $this->columns[] = $column;
        return $this;
    }

    /**
     * Returns all column definitions as an array.
     *
     * @return Column[]
     */
    public function __toArray(): array
    {
        return $this->columns;
    }

    // Iterator interface implementation:

    /**
     * Rewinds the iterator to the first column.
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * Returns the current column.
     */
    public function current(): Column
    {
        return $this->columns[$this->position];
    }

    /**
     * Returns the current iterator key.
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * Moves the iterator to the next column.
     */
    public function next(): void
    {
        $this->position++;
    }

    /**
     * Checks whether the current iterator position is valid.
     */
    public function valid(): bool
    {
        return array_key_exists($this->position, $this->columns);
    }

    // Countable interface implementation:

    /**
     * Returns the number of column definitions.
     */
    public function count(): int
    {
        return count($this->columns);
    }

}
