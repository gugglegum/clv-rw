<?php

declare(strict_types=1);

namespace gugglegum\ClvRw;

use Iterator;

/**
 * Reader for text files in "Constant Length Values" format.
 *
 * @implements Iterator<int, array<string, string>>
 */
class Reader implements Iterator
{
    /**
     * Columns definitions for parsing CLV files
     */
    private ColumnsSet $columns;

    /**
     * Padding character, used to fill excess space in cells
     */
    private string $padding = ' ';

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
     * Current number of row starting from 0
     */
    private int $currentIndex = -1;

    /**
     * Current row array
     */
    private ?array $currentRow = null;

    /**
     * Indicates whether reader initialized or not
     */
    private bool $isInitialized = false;

    /**
     * Reader option: do not abort reading if CLV file contains empty row (not just finishes with empty new line)
     * Some services may produce such bad formed data. This option will help you. Note this option skips empty lines
     * in data section, not before header line.
     */
    private bool $ignoreEmptyDataLines = false;

    /**
     * Opens CLV file or URL/stream in read mode
     *
     * @param string $fileName File name or URL/stream
     * @param ColumnsSet $columns Headers to use if CLV without header-line or to override CLV headers
     * @return static
     * @throws Exception
     */
    public function open(string $fileName, ColumnsSet $columns): static
    {
        if (($fileHandle = @fopen($fileName, 'r')) === false) {
            throw new Exception("Can't open file \"$fileName\" for reading");
        }
        $this->assign($fileHandle, $columns);
        return $this;
    }

    /**
     * Closes CLV file or URL/stream and resets internal state. This method should be called after `open()` method if
     * you no more want to read.
     *
     * @throws Exception
     */
    public function close(): void
    {
        fclose($this->getValidFileHandle());
        $this->unAssign();
    }

    /**
     * Assigns existing file handle (resource) to read CLV data from it. Can be used to read data from "STDIN".
     *
     * @param resource $fileHandle  Opened file handle
     * @param ColumnsSet $columns   Headers to use if CLV without header-line or to override CLV headers
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
     * Un-assigns file handle from CLV reader. This method should be called after `assign()` method if you no more want
     * to read.
     */
    public function unAssign(): void
    {
        $this->fileHandle = null;
        unset($this->columns);
        $this->isInitialized = false;
    }

    /**
     * Initializes internal state of newly opened or assigned file
     *
     * @throws Exception
     */
    private function init(): void
    {
        $this->lineNumber = 0;
        $this->currentIndex = -1;
        $this->currentRow = null;
        $this->isInitialized = true;
        $this->next();
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
     * @throws Exception
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
     * Returns current row if it exists, null otherwise. When non-empty CLV file just opened or assigned this method
     * returns its first row. If column headers are set the row represents an associative array, ordered array otherwise.
     *
     * @return array<string, string>|null
     * @throws Exception
     */
    public function current(): ?array
    {
        if (!$this->isInitialized) {
            $this->init();
        }
        return $this->currentRow;
    }

    /**
     * Returns a number of current row (starting from 0). When CLV file just opened or assigned this method returns 0
     * (no matter is CLV file empty or not).
     *
     * @throws Exception
     */
    public function key(): int
    {
        if (!$this->isInitialized) {
            $this->init();
        }
        return $this->currentIndex;
    }

    /**
     * Returns TRUE if current row is valid. It returns FALSE if and only if `key()` pointing to end of file.
     *
     * @throws Exception
     */
    public function valid(): bool
    {
        if (!$this->isInitialized) {
            $this->init();
        }
        return $this->currentRow !== null;
    }

    /**
     * Reads a row from CLV file and updates current iterator state. This method should be used to iterate CLV file
     * rows.
     *
     * @throws Exception
     */
    public function next(): void
    {
        if (!$this->isInitialized) {
            $this->init();
        }

        while (($row = $this->readRow()) !== false) {
            $this->currentIndex++;

            if ($row === [] && $this->isIgnoreEmptyDataLines()) {
                continue;
            }

            $this->currentRow = $row;
            break;
        }
        if ($row === false) {
            $this->currentRow = null;
        }
    }

    /**
     * Returns all rows from CLV file
     *
     * @return array<int, array<string, string>>
     */
    public function getAllRows(): array
    {
        $rows = [];
        foreach ($this as $row) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Reads a row from CLV file.
     *
     * @return array<string, string>|false Associative row data; empty array on empty line; false on EOF.
     * @throws Exception
     */
    private function readRow(): array|false
    {
        $fileHandle = $this->getValidFileHandle();

        $this->lineNumber++;
        if (($s = fgets($fileHandle)) === false) {
            if (feof($fileHandle)) {
                return false;
            } else {
                throw new Exception("Failed to read from CLV file/stream at line $this->lineNumber");
            }
        }

        // Trim <CR> or <CR>+<LF> at the end of string
        $s = rtrim($s, "\r\n");

        // Return empty array if line empty
        if ($s === '') {
            return [];
        }

        $row = [];
        $startPos = 0;
        foreach ($this->getValidColumns() as $column) {
            $length = $column->getLength();
            $value = substr($s, $startPos, $length);
            $row[$column->getName()] = rtrim($value, $this->padding);
            $startPos += $length;
        }

        return $row;
    }

    /**
     * Returns file position to the beginning of CLV file
     *
     * @throws Exception
     */
    public function rewind(): void
    {
        if ($this->isInitialized) {
            $fileHandle = $this->getValidFileHandle();
            if (stream_get_meta_data($fileHandle)['seekable']) {
                rewind($fileHandle);
            } else {
                throw new Exception("Cannot rewind not seekable stream");
            }
        }
        $this->init();
    }

    /**
     * Returns file handle CLV Reader associated with. You may use this method to make something with file handle.
     * But in most cases you don't need this.
     *
     * @return resource|null
     */
    public function getFileHandle()
    {
        return $this->fileHandle;
    }

    /**
     * Returns valid file handle CLV reader associated with or raises exception otherwise.
     *
     * @return resource
     * @throws Exception
     */
    private function getValidFileHandle()
    {
        if ($this->fileHandle === null) {
            throw new Exception("CLV reader not associated with any file or stream");
        }
        if (!is_resource($this->fileHandle)) {
            throw new Exception("CLV reader associated with not valid file handle");
        }
        return $this->fileHandle;
    }

    /**
     * Returns valid columns set associated with this reader or raises exception otherwise.
     *
     * @throws Exception
     */
    private function getValidColumns(): ColumnsSet
    {
        if (!isset($this->columns)) {
            throw new Exception("CLV reader not associated with columns set");
        }
        return $this->columns;
    }

    /**
     * Returns whether empty data lines should be skipped.
     */
    public function isIgnoreEmptyDataLines(): bool
    {
        return $this->ignoreEmptyDataLines;
    }

    /**
     * Sets whether empty data lines should be skipped.
     */
    public function setIgnoreEmptyDataLines(bool $ignoreEmptyDataLines): static
    {
        $this->ignoreEmptyDataLines = $ignoreEmptyDataLines;
        return $this;
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
}
