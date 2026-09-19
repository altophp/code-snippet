# Installation

Code Snippet is currently distributed from its development branch. Add the
repository explicitly, then require `dev-main`:

```bash
composer config repositories.alto-code-snippet vcs https://github.com/altophp/code-snippet
composer require alto/code-snippet:dev-main
```

Code Snippet requires PHP 8.4 or later and installs `alto/language`.

Create a snippet from code and an optional language slug:

```php
use Alto\Code\Snippet\CodeSnippet;

$snippet = CodeSnippet::fromCode($code, 'php');
```

You may pass an `Alto\Language\Language` object instead of a slug when the language has already
been resolved.
