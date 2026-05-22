<?php

declare(strict_types=1);

namespace gugglegum\ClvRw\Tests;

use gugglegum\ClvRw\Column;
use gugglegum\ClvRw\ColumnsSet;
use gugglegum\ClvRw\Exception;
use gugglegum\ClvRw\Reader;
use gugglegum\ClvRw\Writer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Writer::class)]
final class WriterTest extends TestCase
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
    private function memoryStream()
    {
        return fopen('php://memory', 'r+');
    }

    private function readBack($handle): string
    {
        rewind($handle);
        return stream_get_contents($handle);
    }

    public function testWriteRowPadsValuesToColumnWidth(): void
    {
        $handle = $this->memoryStream();
        $writer = (new Writer())->assign($handle, $this->columns());

        $writer->writeRow(['A' => 'a', 'B' => 'bb', 'C' => 'ccc']);

        $this->assertSame("a bb ccc \n", $this->readBack($handle));
    }

    public function testMultipleRowsAreWrittenInOrder(): void
    {
        $handle = $this->memoryStream();
        $writer = (new Writer())->assign($handle, $this->columns());

        $writer->writeRow(['A' => 'a', 'B' => 'bb', 'C' => 'ccc']);
        $writer->writeRow(['A' => 'zz', 'B' => 'xxx', 'C' => 'yyyy']);

        $this->assertSame("a bb ccc \nzzxxxyyyy\n", $this->readBack($handle));
        $this->assertSame(2, $writer->getLineNumber());
    }

    public function testEmptyRowThrows(): void
    {
        $writer = (new Writer())->assign($this->memoryStream(), $this->columns());

        $this->expectException(Exception::class);
        $writer->writeRow([]);
    }

    public function testUnexpectedFieldThrows(): void
    {
        $writer = (new Writer())->assign($this->memoryStream(), $this->columns());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('unexpected field');
        $writer->writeRow(['A' => 'a', 'B' => 'bb', 'C' => 'ccc', 'D' => 'd']);
    }

    public function testTooLongValueThrowsByDefault(): void
    {
        $writer = (new Writer())->assign($this->memoryStream(), $this->columns());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('got 10');
        $writer->writeRow(['A' => 'a', 'B' => 'bb', 'C' => 'cccccccccc']);
    }

    public function testMissingFieldThrows(): void
    {
        $writer = (new Writer())->assign($this->memoryStream(), $this->columns());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('missing field');
        $writer->writeRow(['A' => 'a', 'B' => 'bb']);
    }

    public function testTrimTooLongValuesTruncatesInsteadOfThrowing(): void
    {
        $handle = $this->memoryStream();
        $writer = (new Writer())
            ->assign($handle, $this->columns())
            ->setTrimTooLongValues(true);

        $this->assertTrue($writer->isTrimTooLongValues());
        $writer->writeRow(['A' => 'a', 'B' => 'bb', 'C' => 'ccccccc']);

        $this->assertSame("a bb cccc\n", $this->readBack($handle));
    }

    public function testCustomPaddingIsUsed(): void
    {
        $handle = $this->memoryStream();
        $writer = (new Writer())
            ->assign($handle, $this->columns())
            ->setPadding('.');

        $this->assertSame('.', $writer->getPadding());
        $writer->writeRow(['A' => 'a', 'B' => 'bb', 'C' => 'ccc']);

        $this->assertSame("a.bb.ccc.\n", $this->readBack($handle));
    }

    public function testWriteRowPadsMultibyteValuesByCharacterWidth(): void
    {
        $handle = $this->memoryStream();
        $writer = (new Writer())->assign($handle, $this->columns());

        $writer->writeRow(['A' => 'я', 'B' => 'ёж', 'C' => '中']);

        $this->assertSame("я ёж 中   \n", $this->readBack($handle));
    }

    public function testGetColumnNamesReflectsConfiguredColumns(): void
    {
        $writer = (new Writer())->assign($this->memoryStream(), $this->columns());

        $this->assertSame(['A', 'B', 'C'], $writer->getColumnNames());
    }

    public function testOpenWritesToFile(): void
    {
        $fileName = tempnam(sys_get_temp_dir(), 'clv-rw-');
        if ($fileName === false) {
            self::fail('Failed to create temporary file');
        }

        try {
            $writer = (new Writer())->open($fileName, $this->columns());
            $writer->writeRow(['A' => 'a', 'B' => 'bb', 'C' => 'ccc']);
            $writer->close();

            $this->assertSame("a bb ccc \n", file_get_contents($fileName));
        } finally {
            if (is_file($fileName)) {
                unlink($fileName);
            }
        }
    }

    public function testWriteRowWithoutAssignThrows(): void
    {
        $this->expectException(Exception::class);
        (new Writer())->writeRow(['A' => 'a', 'B' => 'bb', 'C' => 'ccc']);
    }

    public function testInvalidAssignedHandleThrows(): void
    {
        $writer = (new Writer())->assign('not-a-resource', $this->columns());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('not valid file handle');
        $writer->writeRow(['A' => 'a', 'B' => 'bb', 'C' => 'ccc']);
    }

    public function testRoundTripWithReaderProducesOriginalRows(): void
    {
        $handle = fopen('php://temp', 'r+');
        $columns = $this->columns();

        $writer = (new Writer())->assign($handle, $columns);
        $writer->writeRow(['A' => 'a', 'B' => 'bb', 'C' => 'ccc']);
        $writer->writeRow(['A' => 'zz', 'B' => 'xxx', 'C' => 'yyyy']);

        rewind($handle);

        $reader = (new Reader())->assign($handle, $columns);
        $this->assertSame([
            ['A' => 'a', 'B' => 'bb', 'C' => 'ccc'],
            ['A' => 'zz', 'B' => 'xxx', 'C' => 'yyyy'],
        ], $reader->getAllRows());
    }

    public function testGetFileHandleReturnsAssignedResource(): void
    {
        $handle = $this->memoryStream();
        $writer = (new Writer())->assign($handle, $this->columns());

        $this->assertSame($handle, $writer->getFileHandle());

        $writer->unAssign();
        $this->assertNull($writer->getFileHandle());
    }
}
