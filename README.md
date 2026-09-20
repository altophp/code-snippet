# ALTO Code Snippet

Represent immutable code snippets with source lines, selections, and presentation-neutral
annotations.

&nbsp; ![PHP Version](https://img.shields.io/badge/PHP-8.4%2B-00B7FF?logoColor=00B7FF&labelColor=050608)
&nbsp; ![CI](https://img.shields.io/github/actions/workflow/status/altophp/code-snippet/CI.yml?branch=main&label=Tests&labelColor=050608&color=00B7FF)
&nbsp; [![Packagist](https://img.shields.io/packagist/v/alto/code-snippet?label=Packagist&labelColor=050608&color=00B7FF)](https://packagist.org/packages/alto/code-snippet)
&nbsp; ![License](https://img.shields.io/github/license/altophp/code-snippet?label=License&labelColor=050608&color=00B7FF)
&nbsp; [![GitHub Sponsors](https://img.shields.io/github/sponsors/smnandre?logo=githubsponsors&logoColor=00B7FF&label=%20Sponsor&labelColor=050608&color=00B7FF)](https://github.com/sponsors/smnandre)

Code Snippet turns source code into a portable model with original line numbers, selected lines,
and generic byte-range annotations. Renderers can consume that model without coupling this package
to HTML, SVG, terminals, slides, or a syntax highlighter.

```php
use Alto\Code\Snippet\CodeSnippet;

$snippet = CodeSnippet::fromCode($code, 'php', startLine: 24)
    ->selectLines(3);

echo $snippet->lines()[2]->number; // 26
```

## Installation

```bash
composer require alto/code-snippet
```

Code Snippet requires PHP 8.4 or later and installs `alto/language`.

## Quick start

Create a snippet, keep its original source position, and select one line:

```php
use Alto\Code\Snippet\CodeSnippet;

$snippet = CodeSnippet::fromCode(
    "public function run(): void\n{\n    execute();\n}",
    'php',
    sourceName: 'src/Runner.php',
    startLine: 24,
)->selectLines(3);

$line = $snippet->lines()[2];
printf("line=%d selected=%s %s\n", $line->number, $line->selected ? 'true' : 'false', $line->code);
```

The result is:

```text
line=26 selected=true     execute();
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

When the caller knows the text instead of its byte offsets, `highlight('sum')` adds `focus`
annotations to every literal match. `annotateText()` accepts another type, optional data, and a
one-based occurrence number.

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

Code Snippet does not read files, detect languages, locate declarations, tokenize code, or render
output. It only owns the immutable, presentation-neutral data model passed between those steps.

## Documentation

- [Installation](docs/installation.md): install the development package and verify it.
- [Getting started](docs/getting-started.md): create a snippet and inspect a selected line.
- [Usage](docs/usage.md): work with snippets, lines, annotations, segments, and transformations.
- [Documentation index](docs/index.md): read the package overview and boundaries.

## Contributing

Contributions of all kinds are welcome. Visit the
[project on GitHub](https://github.com/altophp/code-snippet) to
[report a bug](https://github.com/altophp/code-snippet/issues/new),
[suggest a feature](https://github.com/altophp/code-snippet/issues/new), or
[open a pull request](https://github.com/altophp/code-snippet/pulls).

Before submitting code, run:

```bash
# Runs PHP CS Fixer, PHPStan, and PHPUnit
composer qa
```

Changes to public behavior should include tests and documentation.

## Support

ALTO Code Snippet is open source and independently maintained by
[Simon André](https://smnandre.dev). If it is useful to your work, you can
support its continued development through
[GitHub Sponsors](https://github.com/sponsors/smnandre).

Sharing the package or
[starring it on GitHub](https://github.com/altophp/code-snippet) also helps.

## License

ALTO Code Snippet is released by [ALTO PHP](https://altophp.com) under the
[MIT License](LICENSE).
