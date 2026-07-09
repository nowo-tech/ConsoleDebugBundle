<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\EventSubscriber;

use Nowo\ConsoleDebugBundle\ConsoleDebugRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use const JSON_HEX_AMP;
use const JSON_HEX_APOS;
use const JSON_HEX_QUOT;
use const JSON_HEX_TAG;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * Injects a script tag that prints collected cdbg() entries in the browser console.
 */
final class ConsoleDebugResponseSubscriber implements EventSubscriberInterface
{
    private const SCRIPT_MARKER = 'data-nowo-console-debug';
    private const DATA_MARKER   = 'data-nowo-console-debug-data';

    public function __construct(
        private readonly ConsoleDebugRegistry $registry,
        private readonly string $consoleMethod,
        private readonly string $labelPrefix,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -4096],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || $this->registry->isEmpty()) {
            return;
        }

        $response = $event->getResponse();
        $content  = $response->getContent();

        if ($content === false || $content === '') {
            return;
        }

        if (!$this->isHtmlResponse($response->headers->get('Content-Type', ''))) {
            return;
        }

        if ($this->hasInjectedScript($content)) {
            return;
        }

        $payload = json_encode(
            array_map(static fn (\Nowo\ConsoleDebugBundle\ConsoleDebugEntry $entry): array => $entry->toArray(), $this->registry->all()),
            JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        $method = $this->resolveConsoleMethod();
        $script = $this->buildScript($method, $payload);

        if (preg_match('/<\/body>/i', $content) === 1) {
            $content = preg_replace('/<\/body>/i', $script . '</body>', $content, 1) ?? $content;
        } else {
            $content .= $script;
        }

        $response->setContent($content);
        $this->registry->clear();
    }

    private function isHtmlResponse(string $contentType): bool
    {
        return str_contains(strtolower($contentType), 'text/html')
            || str_contains(strtolower($contentType), 'application/xhtml+xml');
    }

    private function hasInjectedScript(string $content): bool
    {
        return preg_match(
            '/<script\b[^>]*\b' . preg_quote(self::SCRIPT_MARKER, '/') . '\b/i',
            $content,
        ) === 1;
    }

    private function resolveConsoleMethod(): string
    {
        return match ($this->consoleMethod) {
            'log', 'info', 'warn', 'debug', 'error' => $this->consoleMethod,
            default                                 => 'log',
        };
    }

    private function buildScript(string $method, string $payload): string
    {
        $labelPrefix  = json_encode($this->labelPrefix, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $scriptMarker = self::SCRIPT_MARKER;
        $dataMarker   = self::DATA_MARKER;

        return <<<HTML
<script type="application/json" {$dataMarker}>{$payload}</script>
<script {$scriptMarker}>
(function () {
  var dataEl = document.querySelector('script[{$dataMarker}]');
  var entries = dataEl ? JSON.parse(dataEl.textContent) : [];
  var prefix = {$labelPrefix};
  var method = '{$method}';
  var print = (console[method] || console.log).bind(console);

  if (!entries.length) {
    return;
  }

  var styles = {
    badge: 'background:#4f46e5;color:#fff;padding:2px 7px;border-radius:4px;font-weight:700;font-size:11px;letter-spacing:0.02em;',
    meta: 'color:#64748b;font-size:11px;font-weight:500;',
    arg: 'color:#64748b;font-size:11px;font-weight:600;margin-right:4px;'
  };

  var t0 = entries[0].timestamp;
  var countLabel = entries.length === 1 ? '1 entry' : entries.length + ' entries';

  console.log(
    '%c ' + prefix + ' %c ' + countLabel + ' %c Nowo Console Debug',
    styles.badge,
    styles.meta,
    'color:#94a3b8;font-size:10px;'
  );

  entries.forEach(function (entry, index) {
    var offsetMs = Math.round((entry.timestamp - t0) * 1000);
    var header = prefix + ' #' + (index + 1) + ' ' + entry.file + ':' + entry.line;

    if (entry.label) {
      header += ' (' + entry.label + ')';
    }

    if (index > 0) {
      header += ' +' + offsetMs + 'ms';
    }

    console.group(header);

    if (entry.variables.length === 1) {
      print(entry.variables[0]);
    } else if (entry.variables.length > 1) {
      entry.variables.forEach(function (value, argIndex) {
        console.log('%carg' + argIndex, styles.arg);
        print(value);
      });
    } else {
      print('(no variables)');
    }

    console.groupEnd();
  });
})();
</script>

HTML;
    }
}
