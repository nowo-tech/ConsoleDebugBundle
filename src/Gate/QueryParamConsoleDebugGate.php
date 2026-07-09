<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Gate;

use Nowo\ConsoleDebugBundle\Contract\ConsoleDebugGateInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Example gate that requires a query parameter in addition to the inner gate rules.
 *
 * Wire it in config with `gate_service` or compose it in your application services.
 */
final class QueryParamConsoleDebugGate implements ConsoleDebugGateInterface
{
    public function __construct(
        private readonly ConsoleDebugGateInterface $innerGate,
        private readonly RequestStack $requestStack,
        private readonly string $queryParam,
    ) {
    }

    public function isEnabled(): bool
    {
        if (!$this->innerGate->isEnabled()) {
            return false;
        }

        $request = $this->requestStack->getMainRequest();
        if (!$request instanceof \Symfony\Component\HttpFoundation\Request) {
            return false;
        }

        return $request->query->has($this->queryParam);
    }
}
