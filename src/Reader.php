<?php

declare(strict_types=1);

namespace gugglegum\ClvRw;

use Iterator;

/**
 * Reader for text files in "Constant Length Values" format
 *
 * @package ActiveFreedom\Drivers\BodyWrappers\Ftp\Reader
 */
class Reader implements Iterator
{
    /**
     * Columns definitions for parsing CLV files
     *
     * @var ColumnsSet
     */
    private $columns;

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
     * Current number of row starting from 0
     *
     * @var int
     */
    private $currentIndex;

    /**
     * Current row array
     *
     * @var null|array
     */
    private $currentRow;

    /**
     * Indicates whether reader initialized or not
     *
     * @var bool
     */
    private $isInitialized;

    /**
     * Reader option: do not abort reading if CLV file contains empty row (not just finishes with empty new line)
     * Some services may produce such bad formed data. This option will help you. Note this option skips empty lines
     * in data section, not before header line.
     *
     * @var bool
     */
    private $ignoreEmptyDataLines = false;

    /**
     * Opens CLV file or URL/stream in read mode
     *
     * @param string $fileName    File name or URL/steam
     * @param ColumnsSet $columns Headers to use if CLV without header-line or to override CLV headers
     * @return Reader
     * @throws Exception
     */
    public function open(string $fileName, ColumnsSet $columns): Reader
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
     * Assigns existing file handle (resource) to read CLV data from it. Can be used to read data from "STDIN".
     *
     * @param resource $fileHandle  Opened file handle
     * @param ColumnsSet $columns   Headers to use if CLV without header-line or to override CLV headers
     * @return Reader
     */
    public function assign($fileHandle, ColumnsSet $columns): Reader
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
    public function unAssign()
    {
        $this->fileHandle = null;
        $this->columns = null;
        $this->isInitialized = false;
    }

    /**
     * Initializes internal state of newly opened or assigned file
     *
     * @throws Exception
     */
    private function init()
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
     * Returns a names of columns
     *
     * @return array
     * @throws Exception
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
     * Returns current row if it exists, null otherwise. When non-empty CLV file just opened or assigned this method
     * returns its first row. If column headers are set the row represents an associative array, ordered array otherwise.
     *
     * @return array|null
     * @throws Exception
     */
    public function current()
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
     * @return int|null
     * @throws Exception
     */
    public function key()
    {
        if (!$this->isInitialized) {
            $this->init();
        }
        return $this->currentIndex;
    }

    /**
     * Returns TRUE if current row is valid. It returns FALSE if and only if `key()` pointing to end of file.
     *
     * @return bool
     * @throws Exception
     */
    public function valid()
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
    public function next()
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
     * @return array
     */
    public function getAllRows()
    {
        $rows = [];
        foreach ($this as $row) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Reads a row from CLV file
     *
     * @return false|array       Assoc array with data; empty array on empty line; false on EOF
     * @throws Exception
     */
    private function readRow()
    {
        $fileHandle = $this->getValidFileHandle();

        $this->lineNumber++;
        if (($s = fgets($fileHandle)) === false) {
            if (feof($fileHandle)) {
                return false;
            } else {
                throw new Exception("Failed to read from CLV file/stream at line {$this->lineNumber}");
            }
        }

        // Return empty array if line empty
        if (trim($s) === '') {
            return [];
        }

        $row = [];
        $startPos = 0;
        foreach ($this->columns as $column) {
            $length = $column->getLength();
            if (($value = substr($s, $startPos, $length)) === false) {
                throw new Exception("Failed to parse CLV file/stream at line {$this->lineNumber}");
            }
            $row[$column->getName()] = rtrim($value);
            $startPos += $length;
        }

        return $row;
    }

    /**
     * Returns file position to the beginning of CLV file
     *
     * @throws Exception
     */
    public function rewind()
    {
        if ($this->lineNumber !== null) {
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
     * @throws ReaderException
     */
    private function getValidFileHandle()
    {
        if (!$this->fileHandle) {
            throw new Exception("CLV reader not associated with any file or stream");
        }
        if (!is_resource($this->fileHandle)) {
            throw new Exception("CLV reader associated with not valid file handle");
        }
        return $this->fileHandle;
    }

    /**
     * @return bool
     */
    public function isIgnoreEmptyDataLines(): bool
    {
        return $this->ignoreEmptyDataLines;
    }

    /**
     * @param bool $ignoreEmptyDataLines
     * @return Reader
     */
    public function setIgnoreEmptyDataLines(bool $ignoreEmptyDataLines): Reader
    {
        $this->ignoreEmptyDataLines = $ignoreEmptyDataLines;
        return $this;
    }
}
