# Code Snippet

Code Snippet stores code, original line numbers, selections, and annotations as
immutable values. Use it to pass an excerpt between extraction, analysis, and
presentation without tying the data to a renderer.

```php
use Alto\Code\Snippet\CodeSnippet;

$snippet = CodeSnippet::fromCode("first\nsecond\nthird", 'php', startLine: 24)
    ->selectLines(3);

echo $snippet->lines()[2]->number; // 26
```

## Documentation

- [Installation](installation.md): install the package and verify it can create a snippet.
- [Getting started](getting-started.md): create a snippet and inspect a selected line.
- [Usage](usage.md): work with snippets, lines, annotations, segments, and transformations.

The package owns the portable snippet model. It does not read files, detect
languages, locate declarations, tokenize code, or render output.
