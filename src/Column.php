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
     *
     * @var string
     */
    private $name;

    /**
     * Column length (size)
     *
     * @var int
     */
    private $length;

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

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return Column
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param mixed $length
     * @return Column
     */
    public function setLength($length)
    {
        $this->length = $length;
        return $this;
    }
}
