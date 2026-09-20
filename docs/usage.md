# Usage

Code Snippet represents source text and presentation hints with four immutable
values. Transformations return a new value, so the original snippet remains
available to another consumer.

| Value | Role |
| --- | --- |
| `CodeSnippet` | Complete code, provenance, selections, and annotations |
| `CodeLine` | One line with snippet and original source coordinates |
| `CodeAnnotation` | Typed metadata attached to a byte range |
| `CodeSegment` | Contiguous line text with one stable annotation set |

All four values implement `JsonSerializable`.

## Snippets

Create a `CodeSnippet` from code. Supply a language when known, a source name
for provenance, and the original first line number when the excerpt came from a
larger file.

```php
use Alto\Code\Snippet\CodeSnippet;

$snippet = CodeSnippet::fromCode(
    "one\ntwo\nthree",
    'php',
    sourceName: 'src/Example.php',
    startLine: 20,
)->selectLines(2);
```

The code is stored verbatim, including LF, CRLF, or CR line endings. Read it
with `code()`, `language()`, `sourceName()`, `startLine()`, `endLine()`, and
`lineCount()`. An empty snippet has zero lines.

## Lines

`lines()` returns `CodeLine` values with two coordinate systems:

| Value | Coordinate system | Example |
| --- | --- | --- |
| `index` | One-based position inside the snippet | `2` |
| `number` | One-based position in the original source | `21` when `startLine` is `20` |
| `code` | Line content without its line break | `two` |
| `selected` | Presentation-neutral line marker | `true` |

```php
$line = $snippet->lines()[1];

printf(
    "index=%d source=%d selected=%s code=%s\n",
    $line->index,
    $line->number,
    $line->selected ? 'true' : 'false',
    $line->code,
);
```

This prints `index=2 source=21 selected=true code=two`. Selected line numbers
must exist inside the snippet.

## Annotations

A `CodeAnnotation` attaches an application-defined type and optional metadata
to a half-open byte range: the start offset is included and the end offset is
excluded.

```php
use Alto\Code\Snippet\CodeAnnotation;

$annotated = $snippet->annotate(
    new CodeAnnotation(4, 3, 'focus'),
    new CodeAnnotation(5, 5, 'warning', ['label' => 'Review']),
);
```

Snippet offsets are relative to `code()`. `CodeLine::annotations()` clips an
annotation to the line and shifts its offset to the beginning of that line.
Annotations may overlap or cross line breaks.

```text
Snippet "abc\ndef": annotation [2, 5)
  line 1 "abc": [2, 3) covers "c"
  line 2 "def": [0, 1) covers "d"
```

Offsets use PHP byte semantics. For UTF-8, byte positions can differ from
character positions. Offsets must be non-negative, lengths positive, types
non-blank, and ranges contained in the snippet.

## Segments

`CodeLine::segments()` splits a line wherever its active annotations change.
Overlapping annotations remain active together; the consumer decides how to
combine their visual styles.

```php
$overlap = CodeSnippet::fromCode('abcd')->annotate(
    new CodeAnnotation(0, 3, 'focus'),
    new CodeAnnotation(1, 2, 'warning'),
);

foreach ($overlap->lines()[0]->segments() as $segment) {
    $types = array_map(
        static fn(CodeAnnotation $annotation): string => $annotation->type,
        $segment->annotations,
    );
    printf("%d %s [%s]\n", $segment->offset, $segment->text, implode(',', $types));
}
```

The result is:

```text
0 a [focus]
1 bc [focus,warning]
3 d []
```

Segment offsets are line-relative. Unannotated text is still a segment; an
empty line has no segments.

## Transformations

`slice($start, $end)` projects a half-open byte range from an annotated
snippet. Crossing annotations are clipped and shifted. Original line numbers
and selections follow the remaining code.

```php
$slice = $snippet->slice(4, 7);
$line = $slice->lines()[0];
```

Here `$line->code` is `two`, its snippet index is `1`, its original source
number is `21`, and it remains selected.

Use `dedent()` or its `unindent()` alias to remove common indentation.
`indent($spaces)` adds spaces to non-empty lines. Both operations preserve
source line numbers and move annotations with the surviving text. Annotations
inside removed indentation disappear, and annotations crossing it are clipped.

## Export

`toArray()` exports code, language, provenance, selections, lines, and
annotations. `json_encode()` produces the same portable model through
`JsonSerializable`.

```php
$payload = $snippet->toArray();
$json = json_encode($snippet, JSON_THROW_ON_ERROR);
```

The package does not provide a JSON import factory or a renderer.
[Code Slicer](https://altophp.com/code-slicer) can locate source declarations,
[Code Highlight](https://altophp.com/code-highlight) can tokenize code, and
[Code Kit](https://altophp.com/code-kit) can connect those stages to this model.

## Invalid input

Invalid source positions, selected lines, slice ranges, annotation ranges,
language identifiers, or indentation values raise `InvalidArgumentException`.
Use `lineCount()` and `strlen($snippet->code())` to check the corresponding
line and byte boundaries before applying user-provided coordinates.
