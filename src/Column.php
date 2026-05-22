<?php

declare(strict_types=1);

namespace gugglegum\ClvRw;

/**
 * Defines one fixed-width column in a CLV row.
 */
final class Column
{
    /**
     * Column name used as the row field key.
     */
    private string $name;

    /**
     * Fixed column width in characters.
     */
    private int $length;

    /**
     * Creates a configured column instance.
     *
     * @param string $name Column name used as the row field key.
     * @param int $length Fixed column width in characters.
     * @return Column
     */
    public static function create(string $name, int $length): Column
    {
        return (new self())->setName($name)->setLength($length);
    }

    /**
     * Returns the column name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets the column name.
     *
     * @param string $name Column name used as the row field key.
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Returns the fixed column width.
     */
    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * Sets the fixed column width.
     *
     * @param int $length Fixed column width in characters.
     * @return self
     */
    public function setLength(int $length): self
    {
        $this->length = $length;
        return $this;
    }
}
