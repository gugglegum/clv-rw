# Reader & Writer for text files with Constant Length Values

CLV is a text-table format with fixed-width cells and no delimiter character. Each row is a text line separated by `LF` or `CRLF` (`\n` or `\r\n`). Each cell has a predefined character width, and missing characters are padded with spaces by default.

This format is mostly useful when integrating with older systems. For new services, CSV or JSON is usually a better choice. The main limitation is that values cannot be longer than their configured column width unless you explicitly enable trimming.

The library can work with both files and streams, so it can read from `STDIN`, write to `STDOUT`, or work with temporary streams such as `php://temp`.

Column widths are measured in UTF-8 characters, not bytes.

## Requirements

- PHP 8.3 or newer
- `ext-mbstring`

## Installation

```bash
composer require gugglegum/clv-rw
```

## Reading

```php
use gugglegum\ClvRw\Column;
use gugglegum\ClvRw\ColumnsSet;
use gugglegum\ClvRw\Reader;

$columns = new ColumnsSet([
    Column::create('Code', 4),
    Column::create('Name', 10),
]);

$reader = (new Reader())->open('input.clv', $columns);

foreach ($reader as $row) {
    echo $row['Code'], ': ', $row['Name'], PHP_EOL;
}

$reader->close();
```

## Writing

```php
use gugglegum\ClvRw\Column;
use gugglegum\ClvRw\ColumnsSet;
use gugglegum\ClvRw\Writer;

$columns = new ColumnsSet([
    Column::create('Code', 4),
    Column::create('Name', 10),
]);

$writer = (new Writer())->open('output.clv', $columns);
$writer->writeRow(['Code' => 'A1', 'Name' => 'Widget']);
$writer->close();
```

`Writer::open()` opens the target file in write mode and truncates existing contents. Use `Writer::assign()` if you need to write to an already opened stream, for example `STDOUT` or `php://temp`.

By default, values that exceed their column width throw an exception. You can trim them instead:

```php
$writer->setTrimTooLongValues(true);
```

## Examples

See the `example/` directory:

```bash
php example/reader.php
php example/reader.php | php example/writer.php
```

## Line Endings

The reader handles `LF` and `CRLF` line endings. Classic Mac `CR`-only files should be normalized before reading. PHP's `auto_detect_line_endings` option is deprecated, so it is better not to rely on it for new code.

## Exceptions

Package errors are thrown as `gugglegum\ClvRw\Exception`, which extends PHP's `RuntimeException`.
