# Usage

Import the helper:

```php
use function Nowo\ConsoleDebugBundle\cdbg;
```

Call it anywhere during a request that returns HTML:

```php
cdbg($entity);
cdbg('after save', $entity->getId(), $form->getData());
```

Behavior:

- Does **not** stop execution (unlike `dd()`).
- Ignores calls when the gate is disabled or the user is unauthorized.
- Skips injection on JSON, XML, downloads, and empty responses.
- First string argument is treated as an optional label when followed by more values.

## Objects and entities

`cdbg()` serializes values to **JSON-safe** structures for the browser console. It does **not** use VarDumper reflection, so Doctrine entities are not expanded property-by-property by default.

| What you pass | What appears in the console |
| --- | --- |
| Plain entity / object (no `__toString`) | `{ "object": "App/Entity/User", "hash": "0000000000000a1b2c3d4e5f" }` — class + identity, not private fields |
| Object with `__toString()` | The string returned by `__toString()` (e.g. `"Customer #7 (Jane)"`) |
| `JsonSerializable` (DTO, value object) | Output of `jsonSerialize()`, normalized recursively |
| `DateTimeInterface` | ISO-8601 string (`2026-07-09T12:00:00+00:00`) |
| `Throwable` | `{ exception, message, code, file, line }` |
| Nested arrays | Recursed up to **depth 8**, then `[max depth reached]` |

**Practical tip for entities:** dump the fields you care about instead of the whole object:

```php
cdbg('user saved', [
    'id'    => $user->getId(),
    'email' => $user->getEmail(),
    'roles' => $user->getRoles(),
]);
```

Or implement `__toString()` on the entity for a quick readable label when passing `$user` directly.

See [Performance](PERFORMANCE.md) for overhead by scenario (gate closed vs open, HTML injection, large payloads).

See README for a custom `ConsoleDebugGateInterface` example combining roles and query parameters.
