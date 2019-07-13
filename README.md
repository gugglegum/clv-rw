# Reader & Writer for text files with Constant Length Values

This is a very rarely used data format, which is a text file with fixed-length lines. It's a table data with rows and cells. Rows are text lines separated by `LF` or `CR`+`LF` (`\n` or `\r\n`). Cells have a fixed size, so no special separator character is used. Excess space is filled with spaces.

This format has more disadvantages than advantages, so use it only to interact with older systems. Do not use this format in new services. CSV or JSON is better in every way. The main limitation of this format is that value cannot be greater than predefined size of column. But if you'll define big size of column — many cells will be filled mostly with spaces.

This library written so you can work both with files and streams. So you can read from `stdin`, write to `stdout` or work with temporary files created by `tmpfile()`.

I encountered this format during integration with API of the Hanes (clothing vendor). I don't know if this file format has any well-established name. I called it "CLV" which is abbreviation for "Constant Length Values". I could call it "Fixed Length Values" but the abbreviation "FLV" is already taken for "Flash Video". 

## Installation

```
composer require gugglegum/clv-rw
```

## Usage

See `/examples` section.

## Troubleshooting

If you have troubles with MAC's line-endings `\r`, you may turn on PHP option `auto_detect_line_endings`:

> When turned on, PHP will examine the data read by fgets() and file() to see if it is using Unix, MS-Dos or Macintosh 
> line-ending conventions.

It may be turned on from you PHP code. Just add this before reading MAC files:
```php
ini_set('auto_detect_line_endings', true);
```
