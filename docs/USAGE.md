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

## Using it from a DDD / hexagonal application

ConsoleDebugBundle is **developer tooling** at the HTTP edge: values recorded during a request are printed in the
browser console of the HTML response. It has no entities, no events, no Doctrine dependency and never changes your
model, so both host kinds use the same configuration.

**Standard Symfony project (default).** Call `cdbg()` (or the Twig `{% cdbg %}` tag) wherever you need it while
debugging; the gate decides whether anything is collected.

**DDD / hexagonal project (opt-in).**

- Do not leave `cdbg()` calls in Domain or Application code: it is a global function from the bundle namespace, and it
  finds the service through a static per-request holder, so the call makes that layer depend on the bundle. Use it
  only while debugging, or inject the service into Infrastructure classes (controllers, adapters) and call `log()`:

  ```php
  use Nowo\ConsoleDebugBundle\ConsoleDebug;

  final readonly class OrderController
  {
      public function __construct(private ConsoleDebug $debug) {}

      public function __invoke(PlaceOrderCommand $command): Response
      {
          $this->debug->log('command', $command); // service id `nowo.console_debug`
          // ...
      }
  }
  ```

- Objects are not expanded by reflection. Aggregates and entities appear as class + object hash; value objects and
  DTOs that implement `JsonSerializable` or `__toString()` appear with their content. Pass IDs, scalar fields or a
  read-model DTO to inspect an aggregate.
- **Port to type-hint / replace:** access is decided by `Nowo\ConsoleDebugBundle\Contract\ConsoleDebugGateInterface`
  (`isEnabled(): bool`). Implement it in Infrastructure (for example on top of your own feature-flag or access policy
  service) and point `gate_service` at it; the bundle aliases the interface to that service and drops its built-in
  enabled + roles (+ query param) chain. To keep the built-in chain and add a condition, decorate the alias instead:

  ```yaml
  services:
      App\Infrastructure\Debug\FeatureFlagConsoleDebugGate:
          decorates: Nowo\ConsoleDebugBundle\Contract\ConsoleDebugGateInterface
          arguments: ['@.inner']
  ```

- No events and no IDs: the bundle dispatches nothing your domain could listen to.

What stays standard-only: the global `cdbg()` helper, the Twig tag and the response subscriber are edge/dev tools; keep
them out of the domain layer (a deptrac rule forbidding `Nowo\ConsoleDebugBundle\*` in Domain makes this explicit).

### Known limitations

- Inject `Nowo\ConsoleDebugBundle\Contract\ConsoleDebugInterface` (`log()`, `logAt()`), not the final `ConsoleDebug` class. A host
  replaces the implementation by re-aliasing the interface to its own service; the old `ConsoleDebug` id keeps working.
  Keep it in Infrastructure only.
- The `cdbg()` helper finds the service through a static, per-request holder (service locator by design).
