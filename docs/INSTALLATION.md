# Installation

## Requirements

- PHP `>=8.1` (<8.6). Symfony **8.0** and **8.1** require **PHP 8.4+**.
- Symfony **7.4**, **8.0**, or **8.1** (mandatory minimum minors). The bundle also supports Symfony 6.x and 7.0–7.3 when constraints resolve.
- `symfony/security-bundle` (or equivalent) in your application for role-based gates.
- `twig/twig` optional — required only for `{{ cdbg() }}` and `{% cdbg %}` Twig integration.

## Composer

```bash
composer require nowo-tech/console-debug-bundle
```

## Enable the bundle

### With Symfony Flex

The recipe enables the bundle and adds `config/packages/nowo_console_debug.yaml`. Adjust roles and options as needed.

### Without Flex

1. Register the bundle in `config/bundles.php`:

```php
return [
    // ...
    Nowo\ConsoleDebugBundle\NowoConsoleDebugBundle::class => ['all' => true],
];
```

2. Create `config/packages/nowo_console_debug.yaml`:

```yaml
nowo_console_debug:
    enabled: true
    roles:
        - ROLE_CONSOLE_DEBUG
    console_method: log
    label_prefix: '[cdbg]'
    shorten_paths: true
```

3. Grant a debug role to trusted users:

```yaml
security:
  role_hierarchy:
    ROLE_CONSOLE_DEBUG: ROLE_USER
```

## Next steps

- [Configuration](CONFIGURATION.md)
- [Usage](USAGE.md)
