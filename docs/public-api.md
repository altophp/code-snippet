# Public API

## `CodeSnippet`

Create a snippet with `CodeSnippet::fromCode()`. Read its code and metadata with `code()`,
`language()`, `sourceName()`, `startLine()`, `endLine()`, and `lineCount()`.

- `selectLines()` marks one-based snippet lines.
- `annotate()` attaches generic byte-range annotations.
- `slice()` projects a half-open byte range and preserves matching metadata.
- `dedent()` and `unindent()` remove common indentation.
- `indent()` adds spaces to non-empty lines.
- `lines()` returns `CodeLine` values.
- `toArray()` and `jsonSerialize()` export the complete model.

Source code and line endings remain byte-for-byte identical until an explicit indentation
transformation is applied.

## `CodeLine`

A line exposes its snippet-relative `index`, original source `number`, plain `code`, selection
state, line-relative annotations, and derived `segments()`.

## `CodeAnnotation`

An annotation contains a byte `offset`, positive `length`, application-defined `type`, and generic
`data`. Its end offset is available through `endOffset()`.

## `CodeSegment`

A segment contains contiguous text and the annotations active across that complete region.

All four value objects implement `JsonSerializable`.
