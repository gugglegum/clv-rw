<?php

declare(strict_types=1);

namespace gugglegum\ClvRw;

use gugglegum\mb_str_pad\MbString;

class Writer
{
    /**
     * Columns definitions for parsing CLV files
     *
     * @var ColumnsSet
     */
    private $columns;

    /**
     * Padding character, used to fill excess space in cells
     *
     * @var string
     */
    private $padding = ' ';

    /**
     * If enabled too long values will be silently trimmed, otherwise an exception will be thrown
     *
     * @var bool
     */
    private $trimTooLongValues = false;

    /**
     * Current line number in CLV file
     *
     * @var int
     */
    private $lineNumber;

    /**
     * Opened CLV file handle
     *
     * @var resource
     */
    private $fileHandle;

    /**
     * Indicates whether writer initialized or not
     *
     * @var bool
     */
    private $isInitialized;

    /**
     * Opens CLV file or URL/stream in read mode
     *
     * @param string $fileName    File name or URL/steam
     * @param ColumnsSet $columns Headers to use if CLV without header-line or to override CLV headers
     * @return Writer
     * @throws Exception
     */
    public function open(string $fileName, ColumnsSet $columns): Writer
    {
        if (!$fileHandle = @fopen($fileName, 'r')) {
            throw new Exception("Can't open file \"{$fileName}\" for reading");
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
    public function close()
    {
        fclose($this->getValidFileHandle());
        $this->unAssign();
    }

    /**
     * Assigns existing file handle (resource) to read CSV data from it. Can be used to read data from "STDIN".
     *
     * @param resource $fileHandle Opened file handle
     * @param ColumnsSet $columns
     * @return $this
     */
    public function assign($fileHandle, ColumnsSet $columns)
    {
        $this->fileHandle = $fileHandle;
        $this->columns = $columns;
        $this->isInitialized = false;
        return $this;
    }

    /**
     * Un-assigns file handle from CLV writer. This method should be called after `assign()` method if you no more want
     * to read.
     */
    public function unAssign()
    {
        $this->fileHandle = null;
        $this->columns = null;
        $this->isInitialized = false;
    }

    /**
     * Initializes internal state of newly opened or assigned file
     */
    private function init()
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
     * Returns a names of columns
     *
     * @return array
     */
    public function getColumnNames()
    {
        if (!$this->isInitialized) {
            $this->init();
        }
        $columnNames = [];
        foreach ($this->columns as $column) {
            $columnNames[] = $column->getName();
        }
        return $columnNames;
    }

    /**
     * Writes a CSV row to file (or stream)
     *
     * If headers for CSV are defined passed array must be associative array where keys are header names. The amount of
     * array elements must be equal to amount of headers. If headers are not defined the array must be ordered
     * (contain keys 0, 1, 2, ...). Amount of elements must be the same for all rows.
     *
     * @param array $row Associative or ordered array with data of row to write in CSV
     * @throws Exception
     */
    public function writeRow(array $row)
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

        $line = '';
        $missing = [];
        foreach ($this->columns as $column) {
            if (!array_key_exists($column->getName(), $row)) {
                $missing[] = $column->getName();
            }
            if ($length = mb_strlen((string) $row[$column->getName()], 'UTF-8') > $column->getLength()) {
                if ($this->trimTooLongValues) {
                    $row[$column->getName()] = mb_substr((string) $row[$column->getName()], 0, $column->getLength());
                } else {
                    throw new Exception("Too long value \"{$row[$column->getName()]}\" for column {$column->getName()} (max {$column->getLength()} characters, got " . ($length) . ')');
                }
            }
            $line .= MbString::mb_str_pad((string) $row[$column->getName()], $column->getLength(), $this->padding);
        }
        if (!empty($missing)) {
            throw new Exception('Passed data for CSV missing field(s): "' . implode('", "', $missing) . '" (expected: "' . implode('", "', $columnNames) . '")');
        }
        $this->lineNumber++;
        if (!fputs($this->getValidFileHandle(), $line . "\n")) {
            throw new Exception("Failed to write CLV row at line {$this->lineNumber}");
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
        if (!$this->fileHandle) {
            throw new Exception("CLV writer not associated with any file or stream");
        }
        if (!is_resource($this->fileHandle)) {
            throw new Exception("CLV writer associated with not valid file handle");
        }
        return $this->fileHandle;
    }

    /**
     * @return string
     */
    public function getPadding(): string
    {
        return $this->padding;
    }

    /**
     * @param string $padding
     * @return Writer
     */
    public function setPadding(string $padding): Writer
    {
        $this->padding = $padding;
        return $this;
    }

    /**
     * @return bool
     */
    public function isTrimTooLongValues(): bool
    {
        return $this->trimTooLongValues;
    }

    /**
     * @param bool $trimTooLongValues
     * @return Writer
     */
    public function setTrimTooLongValues(bool $trimTooLongValues): Writer
    {
        $this->trimTooLongValues = $trimTooLongValues;
        return $this;
    }
}
