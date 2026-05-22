<?php

declare(strict_types=1);

namespace gugglegum\ClvRw\Tests;

use gugglegum\ClvRw\Column;
use gugglegum\ClvRw\ColumnsSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests ordered column set storage and iterator behavior.
 */
#[CoversClass(ColumnsSet::class)]
final class ColumnsSetTest extends TestCase
{
    public function testConstructorStoresColumnsInOrder(): void
    {
        $a = Column::create('A', 1);
        $b = Column::create('B', 2);
        $set = new ColumnsSet([$a, $b]);

        $this->assertSame([$a, $b], $set->getColumns());
        $this->assertCount(2, $set);
    }

    public function testAddColumnAppends(): void
    {
        $set = new ColumnsSet([]);
        $a = Column::create('A', 1);
        $b = Column::create('B', 2);

        $set->addColumn($a)->addColumn($b);

        $this->assertSame([$a, $b], $set->getColumns());
    }

    public function testSetColumnsReplacesExistingColumns(): void
    {
        $set = new ColumnsSet([Column::create('A', 1)]);
        $b = Column::create('B', 2);

        $set->setColumns([$b]);

        $this->assertSame([$b], $set->getColumns());
        $this->assertCount(1, $set);
    }

    public function testClearColumnsRemovesAll(): void
    {
        $set = new ColumnsSet([Column::create('A', 1), Column::create('B', 2)]);

        $set->clearColumns();

        $this->assertSame([], $set->getColumns());
        $this->assertCount(0, $set);
    }

    public function testIterationYieldsColumnsInInsertionOrder(): void
    {
        $a = Column::create('A', 1);
        $b = Column::create('B', 2);
        $c = Column::create('C', 3);
        $set = new ColumnsSet([$a, $b, $c]);

        $collected = [];
        foreach ($set as $key => $column) {
            $collected[$key] = $column;
        }

        $this->assertSame([0 => $a, 1 => $b, 2 => $c], $collected);
    }

    public function testIteratorIsRewindable(): void
    {
        $set = new ColumnsSet([Column::create('A', 1), Column::create('B', 2)]);

        iterator_to_array($set);
        $second = iterator_to_array($set);

        $this->assertCount(2, $second);
    }

    public function testToArrayReturnsColumns(): void
    {
        $a = Column::create('A', 1);
        $set = new ColumnsSet([$a]);

        $this->assertSame([$a], $set->__toArray());
    }
}
