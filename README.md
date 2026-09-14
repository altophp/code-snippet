# ALTO CodeSnippet

Represent immutable code snippets with source lines, selections, and presentation-neutral
annotations.

&nbsp; ![PHP Version](https://img.shields.io/badge/PHP-8.4%2B-00B7FF?logoColor=00B7FF&labelColor=050608)
&nbsp; ![CI](https://img.shields.io/github/actions/workflow/status/altophp/code-snippet/CI.yml?branch=main&label=Tests&labelColor=050608&color=00B7FF)
&nbsp; [![Packagist](https://img.shields.io/packagist/v/alto/code-snippet?label=Packagist&labelColor=050608&color=00B7FF)](https://packagist.org/packages/alto/code-snippet)
&nbsp; ![License](https://img.shields.io/github/license/altophp/code-snippet?label=License&labelColor=050608&color=00B7FF)
&nbsp; [![GitHub Sponsors](https://img.shields.io/github/sponsors/smnandre?logo=githubsponsors&logoColor=00B7FF&label=%20Sponsor&labelColor=050608&color=00B7FF)](https://github.com/sponsors/smnandre)

CodeSnippet turns source code into a portable model with original line numbers, selected lines,
and generic byte-range annotations. Renderers can consume that model without coupling this package
to HTML, SVG, terminals, slides, or a syntax highlighter.

```php
use Alto\Code\Snippet\CodeSnippet;

$snippet = CodeSnippet::fromCode($code, 'php', startLine: 24)
    ->selectLines(3);

echo $snippet->lines()[2]->number; // 26
```

## Installation

Install ALTO CodeSnippet with Composer:

```bash
composer require alto/code-snippet
```

CodeSnippet requires PHP 8.4 or later and `alto/language`.

## Quick Start

Create a snippet, select a line, and attach an application-defined annotation:

```php
use Alto\Code\Snippet\CodeAnnotation;
use Alto\Code\Snippet\CodeSnippet;

$snippet = CodeSnippet::fromCode(
    "public function run(): void\n{\n    execute();\n}",
    'php',
    sourceName: 'src/Runner.php',
    startLine: 24,
)
    ->selectLines(3)
    ->annotate(new CodeAnnotation(
        offset: 0,
        length: 6,
        type: 'syntax',
        data: ['scope' => 'keyword'],
    ));

echo json_encode($snippet, JSON_THROW_ON_ERROR);
```

Every transformation returns a new value. The original snippet remains unchanged.

## Lines and selections

`lines()` returns `CodeLine` values with both coordinate systems:

- `index` is one-based and relative to the snippet;
- `number` refers to the original source;
- `code` excludes the line break;
- `selected` carries line-level emphasis;
- `annotations()` contains line-relative annotations.

```php
$line = $snippet->lines()[2];

$line->index;         // 3
$line->number;        // 26
$line->code;          // "    execute();"
$line->selected;      // true
$line->annotations();
$line->segments();
```

Source content and LF, CRLF, or CR line endings remain byte-for-byte identical.

## Annotations and segments

`CodeAnnotation` describes a byte range relative to `code()`, an application-defined type, and
optional generic data:

```php
$annotated = $snippet->annotate(
    new CodeAnnotation(0, 6, 'syntax', ['scope' => 'keyword']),
    new CodeAnnotation(16, 3, 'emphasis', ['name' => 'primary']),
);
```

Annotations may overlap or cross line breaks. Each `CodeLine` clips and shifts them to its own
content. `segments()` derives contiguous text regions with stable annotation sets.

## Slicing and indentation

`slice()` projects an already annotated snippet onto a half-open byte range:

```php
$projected = $annotatedSource
    ->slice($range->start, $range->end)
    ->dedent();
```

This lets a consumer analyze a complete source before projecting the selected region. Crossing
annotations are clipped and shifted, while source line numbers and selections are retained.

Use `dedent()` or its `unindent()` alias to remove common indentation. `indent()` adds spaces to
non-empty lines. These explicit transformations update annotation offsets and preserve the original
line-ending style.

## Export

`CodeSnippet`, `CodeLine`, `CodeAnnotation`, and `CodeSegment` implement `JsonSerializable`.
`toArray()` exports code, provenance, selections, and annotations:

```php
$payload = $snippet->toArray();
$json = json_encode($snippet, JSON_THROW_ON_ERROR);
```

## Package boundary

CodeSnippet does not read files, detect languages, locate declarations, tokenize code, or render
output. It only owns the immutable, presentation-neutral data model passed between those steps.

See the [documentation](docs/index.md) for installation, a guided example, and the public API.

## Contributing

Contributions of all kinds are welcome. Visit the
[project on GitHub](https://github.com/altophp/code-snippet) to
[report a bug](https://github.com/altophp/code-snippet/issues/new),
[suggest a feature](https://github.com/altophp/code-snippet/issues/new), or
[open a pull request](https://github.com/altophp/code-snippet/pulls). Before submitting code, run:

```bash
# Runs PHP CS Fixer, PHPStan, and PHPUnit
composer qa
```

Changes to public behavior should include tests and documentation.

## Support

ALTO CodeSnippet is open source. You can support its continued development through
[GitHub Sponsors](https://github.com/sponsors/smnandre).

Sharing this package with others or
[starring it on GitHub](https://github.com/altophp/code-snippet) is also much appreciated.

## License

ALTO CodeSnippet is released by [ALTO PHP](https://altophp.com) under the
[MIT License](LICENSE).
