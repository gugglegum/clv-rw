<?php

declare(strict_types=1);

namespace gugglegum\ClvRw;

/**
 * Column definition for the Reader of CLV files (Constant-Length Values)
 *
 * @package ActiveFreedom\Drivers\BodyWrappers\Ftp\Reader
 */
class Column
{
    /**
     * Column name
     */
    private string $name;

    /**
     * Column length (size)
     */
    private int $length;

    /**
     * Creates a column with static call
     *
     * @param string $name
     * @param int $length
     * @return Column
     */
    public static function create(string $name, int $length): Column
    {
        return (new self())->setName($name)->setLength($length);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function setLength(int $length): self
    {
        $this->length = $length;
        return $this;
    }
}
