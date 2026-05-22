<?php

declare(strict_types=1);

namespace gugglegum\ClvRw\Tests;

use gugglegum\ClvRw\Column;
use gugglegum\ClvRw\ColumnsSet;
use gugglegum\ClvRw\Exception;
use gugglegum\ClvRw\Reader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Reader::class)]
final class ReaderTest extends TestCase
{
    private function columns(): ColumnsSet
    {
        return new ColumnsSet([
            Column::create('A', 2),
            Column::create('B', 3),
            Column::create('C', 4),
        ]);
    }

    /**
     * @return resource
     */
    private function streamFrom(string $content)
    {
        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $content);
        rewind($handle);
        return $handle;
    }

    public function testIterationYieldsAssociativeRowsTrimmedOfPadding(): void
    {
        $reader = (new Reader())->assign(
            $this->streamFrom("a bb ccc \nzzxxxyyyy\n"),
            $this->columns(),
        );

        $rows = iterator_to_array($reader, false);

        $this->assertSame([
            ['A' => 'a', 'B' => 'bb', 'C' => 'ccc'],
            ['A' => 'zz', 'B' => 'xxx', 'C' => 'yyyy'],
        ], $rows);
    }

    public function testGetAllRowsReturnsAllRows(): void
    {
        $reader = (new Reader())->assign(
            $this->streamFrom("a bb ccc \nzzxxxyyyy\n"),
            $this->columns(),
        );

        $this->assertSame([
            ['A' => 'a', 'B' => 'bb', 'C' => 'ccc'],
            ['A' => 'zz', 'B' => 'xxx', 'C' => 'yyyy'],
        ], $reader->getAllRows());
    }

    public function testCrlfLineEndingsAreHandled(): void
    {
        $reader = (new Reader())->assign(
            $this->streamFrom("a bb ccc \r\nzzxxxyyyy\r\n"),
            $this->columns(),
        );

        $this->assertCount(2, $reader->getAllRows());
    }

    public function testEmptyStreamProducesNoRows(): void
    {
        $reader = (new Reader())->assign($this->streamFrom(''), $this->columns());

        $this->assertSame([], $reader->getAllRows());
        $this->assertFalse($reader->valid());
        $this->assertNull($reader->current());
    }

    public function testEmptyDataLineProducesEmptyRowByDefault(): void
    {
        $reader = (new Reader())->assign(
            $this->streamFrom("a bb ccc \n\nzzxxxyyyy\n"),
            $this->columns(),
        );

        $rows = [];
        foreach ($reader as $row) {
            $rows[] = $row;
        }

        $this->assertSame([
            ['A' => 'a', 'B' => 'bb', 'C' => 'ccc'],
            [],
            ['A' => 'zz', 'B' => 'xxx', 'C' => 'yyyy'],
        ], $rows);
    }

    public function testIgnoreEmptyDataLinesSkipsBlankRows(): void
    {
        $reader = (new Reader())
            ->assign($this->streamFrom("a bb ccc \n\nzzxxxyyyy\n"), $this->columns())
            ->setIgnoreEmptyDataLines(true);

        $this->assertTrue($reader->isIgnoreEmptyDataLines());
        $this->assertSame([
            ['A' => 'a', 'B' => 'bb', 'C' => 'ccc'],
            ['A' => 'zz', 'B' => 'xxx', 'C' => 'yyyy'],
        ], $reader->getAllRows());
    }

    public function testGetColumnNamesReflectsConfiguredColumns(): void
    {
        $reader = (new Reader())->assign($this->streamFrom(''), $this->columns());

        $this->assertSame(['A', 'B', 'C'], $reader->getColumnNames());
    }

    public function testLineNumberAdvancesWithReads(): void
    {
        $reader = (new Reader())->assign(
            $this->streamFrom("a bb ccc \nzzxxxyyyy\n"),
            $this->columns(),
        );

        $reader->current();
        $this->assertSame(1, $reader->getLineNumber());
        $reader->next();
        $this->assertSame(2, $reader->getLineNumber());
    }

    public function testKeyTracksRowIndex(): void
    {
        $reader = (new Reader())->assign(
            $this->streamFrom("a bb ccc \nzzxxxyyyy\n"),
            $this->columns(),
        );

        $this->assertSame(0, $reader->key());
        $reader->next();
        $this->assertSame(1, $reader->key());
    }

    public function testCustomPaddingIsTrimmed(): void
    {
        $reader = (new Reader())
            ->assign($this->streamFrom("a.bb.ccc.\n"), $this->columns())
            ->setPadding('.');

        $this->assertSame('.', $reader->getPadding());
        $this->assertSame(
            [['A' => 'a', 'B' => 'bb', 'C' => 'ccc']],
            $reader->getAllRows(),
        );
    }

    public function testRewindRestartsIteration(): void
    {
        $reader = (new Reader())->assign(
            $this->streamFrom("a bb ccc \nzzxxxyyyy\n"),
            $this->columns(),
        );

        iterator_to_array($reader);
        $reader->rewind();

        $this->assertSame(0, $reader->key());
        $this->assertSame(['A' => 'a', 'B' => 'bb', 'C' => 'ccc'], $reader->current());
    }

    public function testOpenThrowsWhenFileMissing(): void
    {
        $this->expectException(Exception::class);
        (new Reader())->open('/nonexistent/path/missing.clv', $this->columns());
    }

    public function testOpenReadsFromExampleSampleFile(): void
    {
        $reader = (new Reader())->open(__DIR__ . '/../example/sample.clv', new ColumnsSet([
            Column::create('Style Number', 12),
            Column::create('Style Description', 25),
            Column::create('Color Code', 3),
            Column::create('Color Description', 25),
            Column::create('Piece', 6),
            Column::create('Width', 3),
            Column::create('Size', 4),
            Column::create('Sold Out Reason', 2),
            Column::create('Sold Out Date', 8),
            Column::create('UPC', 12),
            Column::create('Inventory', 7),
            Column::create('Next Delivery Availability', 7),
            Column::create('Next Delivery Date', 8),
            Column::create('USD List Price', 9),
            Column::create('HSCode', 10),
            Column::create('HSCountry', 3),
        ]));

        $rows = $reader->getAllRows();
        $reader->close();

        $this->assertNotEmpty($rows);
        $this->assertSame('9104', $rows[0]['Style Number']);
        $this->assertSame('HOT SHORT', $rows[0]['Style Description']);
        $this->assertSame('BLK', $rows[0]['Color Code']);
        $this->assertSame('L', $rows[0]['Size']);
    }

    public function testCallingMethodsWithoutAssignThrows(): void
    {
        $this->expectException(Exception::class);
        (new Reader())->current();
    }

    public function testCloseUnassignsFileHandle(): void
    {
        $handle = $this->streamFrom("a bb ccc \n");
        $reader = (new Reader())->assign($handle, $this->columns());
        iterator_to_array($reader);

        $reader->close();

        $this->assertNull($reader->getFileHandle());
    }
}
