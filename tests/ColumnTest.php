<?php

declare(strict_types=1);

namespace gugglegum\ClvRw\Tests;

use gugglegum\ClvRw\Column;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests column definition value object behavior.
 */
#[CoversClass(Column::class)]
final class ColumnTest extends TestCase
{
    public function testCreateReturnsConfiguredColumn(): void
    {
        $column = Column::create('Style Number', 12);

        $this->assertSame('Style Number', $column->getName());
        $this->assertSame(12, $column->getLength());
    }

    public function testSettersAreFluent(): void
    {
        $column = new Column();

        $this->assertSame($column, $column->setName('foo'));
        $this->assertSame($column, $column->setLength(7));
        $this->assertSame('foo', $column->getName());
        $this->assertSame(7, $column->getLength());
    }
}
