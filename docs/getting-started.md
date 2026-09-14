# Getting started

Create a snippet with its original source position, select lines, then attach generic byte-range
annotations:

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
```

`selectLines()` uses one-based positions inside the snippet. Every `CodeLine` also carries its
original source line number.

```php
$line = $snippet->lines()[2];

$line->index;    // 3
$line->number;   // 26
$line->selected; // true
```

Annotations on the complete snippet use byte offsets relative to `code()`. Line annotations are
clipped and shifted to line-relative offsets. `segments()` then exposes contiguous text regions
with stable annotation sets.
