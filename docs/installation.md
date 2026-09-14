# Installation

Install CodeSnippet with Composer:

```bash
composer require alto/code-snippet
```

CodeSnippet requires PHP 8.4 or later and `alto/language`.

Create a snippet from code and an optional language slug:

```php
use Alto\Code\Snippet\CodeSnippet;

$snippet = CodeSnippet::fromCode($code, 'php');
```

You may pass an `Alto\Language\Language` object instead of a slug when the language has already
been resolved.
