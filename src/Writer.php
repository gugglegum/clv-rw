<?php

declare(strict_types=1);

namespace gugglegum\ClvRw;

use gugglegum\mb_str_pad\MbString;

/**
 * Writer for text files in "Constant Length Values" format.
 */
class Writer
{
    /**
     * Columns definitions for writing CLV files
     */
    private ColumnsSet $columns;

    /**
     * Padding character, used to fill excess space in cells
     */
    private string $padding = ' ';

    /**
     * If enabled too long values will be silently trimmed, otherwise an exception will be thrown
     */
    private bool $trimTooLongValues = false;

    /**
     * Current line number in CLV file
     */
    private int $lineNumber = 0;

    /**
     * Opened CLV file handle
     *
     * @var resource|null
     */
    private $fileHandle = null;

    /**
     * Indicates whether writer initialized or not
     */
    private bool $isInitialized = false;

    /**
     * Opens CLV file or URL/stream in write mode
     *
     * @param string $fileName File name or URL/stream
     * @param ColumnsSet $columns Column definitions to write rows with
     * @return static
     * @throws Exception
     */
    public function open(string $fileName, ColumnsSet $columns): static
    {
        if (($fileHandle = @fopen($fileName, 'w')) === false) {
            throw new Exception("Can't open file \"$fileName\" for writing");
        }
        $this->assign($fileHandle, $columns);
        return $this;
    }

    /**
     * Closes CLV file or URL/stream and resets internal state. This method should be called after `open()` method if
     * you no more want to write.
     *
     * @throws Exception
     */
    public function close(): void
    {
        fclose($this->getValidFileHandle());
        $this->unAssign();
    }

    /**
     * Assigns existing file handle (resource) to write CLV data to it. Can be used to write data to "STDOUT".
     *
     * @param resource $fileHandle Opened file handle
     * @param ColumnsSet $columns
     * @return static
     */
    public function assign($fileHandle, ColumnsSet $columns): static
    {
        $this->fileHandle = $fileHandle;
        $this->columns = $columns;
        $this->isInitialized = false;
        return $this;
    }

    /**
     * Un-assigns file handle from CLV writer. This method should be called after `assign()` method if you no more want
     * to write.
     */
    public function unAssign(): void
    {
        $this->fileHandle = null;
        unset($this->columns);
        $this->isInitialized = false;
    }

    /**
     * Initializes internal state of newly opened or assigned file
     */
    private function init(): void
    {
        $this->lineNumber = 0;
        $this->isInitialized = true;
    }

    /**
     * Returns current line number
     *
     * @return int
     */
    public function getLineNumber(): int
    {
        return $this->lineNumber;
    }

    /**
     * Returns column names.
     *
     * @return string[]
     */
    public function getColumnNames(): array
    {
        if (!$this->isInitialized) {
            $this->init();
        }
        $columnNames = [];
        foreach ($this->getValidColumns() as $column) {
            $columnNames[] = $column->getName();
        }
        return $columnNames;
    }

    /**
     * Writes a CLV row to file or stream.
     *
     * Passed array must be associative, where keys are configured column names.
     *
     * @param array<string, mixed> $row Associative row data to write in CLV
     * @throws Exception
     */
    public function writeRow(array $row): void
    {
        if (!$this->isInitialized) {
            $this->init();
        }
        if (empty($row)) {
            throw new Exception('Attempt to write empty row in CLV file');
        }
        $columnNames = $this->getColumnNames();
        if ($unexpected = array_diff(array_keys($row), $columnNames)) {
            throw new Exception('Passed data for CLV contains unexpected field(s): "' . implode('", "', $unexpected) . '" (expected: "' . implode('", "', $columnNames) . '")');
        }
        if ($missing = array_diff($columnNames, array_keys($row))) {
            throw new Exception('Passed data for CLV missing field(s): "' . implode('", "', $missing) . '" (expected: "' . implode('", "', $columnNames) . '")');
        }

        $line = '';
        foreach ($this->getValidColumns() as $column) {
            $columnName = $column->getName();
            $value = (string) $row[$columnName];
            $length = mb_strlen($value, 'UTF-8');

            if ($length > $column->getLength()) {
                if ($this->trimTooLongValues) {
                    $value = mb_substr($value, 0, $column->getLength());
                } else {
                    throw new Exception("Too long value \"$value\" for column $columnName (max {$column->getLength()} characters, got $length)");
                }
            }
            $line .= MbString::mb_str_pad($value, $column->getLength(), $this->padding);
        }
        $this->lineNumber++;
        if (fputs($this->getValidFileHandle(), $line . "\n") === false) {
            throw new Exception("Failed to write CLV row at line $this->lineNumber");
        }
    }

    /**
     * Returns file handle CLV writer associated with. You may use this method to make something with file handle.
     * But in most cases you don't need this.
     *
     * @return null|resource
     */
    public function getFileHandle()
    {
        return $this->fileHandle;
    }

    /**
     * Returns valid file handle CLV writer associated with or raises exception otherwise.
     *
     * @return resource
     * @throws Exception
     */
    private function getValidFileHandle()
    {
        if ($this->fileHandle === null) {
            throw new Exception("CLV writer not associated with any file or stream");
        }
        if (!is_resource($this->fileHandle)) {
            throw new Exception("CLV writer associated with not valid file handle");
        }
        return $this->fileHandle;
    }

    /**
     * Returns valid columns set associated with this writer or raises exception otherwise.
     *
     * @throws Exception
     */
    private function getValidColumns(): ColumnsSet
    {
        if (!isset($this->columns)) {
            throw new Exception("CLV writer not associated with columns set");
        }
        return $this->columns;
    }

    /**
     * Returns the padding character.
     */
    public function getPadding(): string
    {
        return $this->padding;
    }

    /**
     * Sets the padding character.
     */
    public function setPadding(string $padding): static
    {
        $this->padding = $padding;
        return $this;
    }

    /**
     * Returns whether too long values should be silently trimmed.
     */
    public function isTrimTooLongValues(): bool
    {
        return $this->trimTooLongValues;
    }

    /**
     * Sets whether too long values should be silently trimmed.
     */
    public function setTrimTooLongValues(bool $trimTooLongValues): static
    {
        $this->trimTooLongValues = $trimTooLongValues;
        return $this;
    }
}
