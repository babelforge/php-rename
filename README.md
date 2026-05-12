# PHP Rename

`php-noobs/php-rename` is a semantic PHP symbol rename library.

It uses `php-noobs/member-graph` to locate declarations and usages, then mutates PHPParser AST nodes stored in virtual PHP source files. This keeps rename operations semantic and avoids text-based replacements.

## Requirements

- PHP 8.4 or higher
- Composer

## Installation

```bash
composer require php-noobs/php-rename
```

## Basic Usage

Create a renamer from source directories:

```php
use PhpNoobs\PhpRename\Application\PhpRename;

$renamer = PhpRename::fromDirectory(
    directories: [__DIR__.'/src'],
    cacheFilePath: __DIR__.'/var/member-graph.cache',
);
```

Rename a method:

```php
$result = $renamer->renameMethod(
    className: App\Mailer::class,
    methodName: 'send',
    newMethodName: 'deliver',
);
```

Rename a class and its usages:

```php
$result = $renamer->renameClassFqcn(
    className: 'App\\Mailer',
    newClassName: 'App\\Infrastructure\\Sender',
);
```

`php-rename` mutates the in-memory virtual source files. Physical writing is available through transaction save helpers.

## Transactions

Use a transaction when later renames depend on earlier in-memory changes:

```php
$transaction = $renamer->beginTransaction();

$transaction->renameClassFqcn('App\\Mailer', 'App\\Infrastructure\\Sender');
$transaction->renameMethod('App\\Infrastructure\\Sender', 'send', 'deliver');

$result = $transaction->commitAndSave();
```

Transactions support rollback after blocking diagnostics.

## Supported Renames

`php-rename` supports:

- class-like short and FQCN renames;
- method renames;
- property renames, including promoted properties;
- class constant renames;
- enum case renames;
- function short and FQCN renames;
- namespace-level constant short and FQCN renames;
- method and function parameter renames.

Supported operations include declarations, usages, imports, selected structured docblocks, conflict diagnostics, transaction-local overlays, and transaction-neutral orchestration steps.

## Documentation

Full documentation starts in [doc/README.md](doc/README.md).

The supported rename matrix is available in [doc/07-supported-rename-matrix.md](doc/07-supported-rename-matrix.md).
