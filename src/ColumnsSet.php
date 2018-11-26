<?php

declare(strict_types=1);

namespace gugglegum\ClvRw;

/**
 * Set of columns for inventory file
 *
 * Inventory file on Body Wrappers FTP contains a data in "constant length values" format. Data columns have fixed
 * size with space padding without any additional separator. This class stores a definition of columns and its sizes.
 *
 * @package ActiveFreedom\Drivers\BodyWrappers\Ftp\Reader
 */
class ColumnsSet implements \Iterator, \Countable
{
    /**
     * @var int
     */
    private $position = 0;

    /**
     * @var Column[]
     */
    private $columns = [];

    public function __construct(array $columns)
    {
        $this->setColumns($columns);
    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function setColumns(array $columns)
    {
        $this->clearColumns();
        foreach ($columns as $column) {
            $this->addColumn($column);
        }
        return $this;
    }

    public function clearColumns(): self
    {
        $this->columns = [];
        return $this;
    }

    public function addColumn(Column $column): self
    {
        $this->columns[] = $column;
        return $this;
    }

    public function __toArray()
    {
        return $this->columns;
    }

    // Iterator interface implementation:

    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * @return Column
     */
    public function current()
    {
        return $this->columns[$this->position];
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        $this->position++;
    }

    public function valid()
    {
        return array_key_exists($this->position, $this->columns);
    }

    // Countable interface implementation:

    public function count()
    {
        return count($this->columns);
    }

}
