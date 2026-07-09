<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Gate;

use Nowo\ConsoleDebugBundle\Contract\ConsoleDebugGateInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Allows console debug only for authenticated users with at least one configured role.
 */
final class RoleBasedConsoleDebugGate implements ConsoleDebugGateInterface
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        private readonly ConsoleDebugGateInterface $innerGate,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly array $roles,
    ) {
    }

    public function isEnabled(): bool
    {
        if (!$this->innerGate->isEnabled()) {
            return false;
        }

        if ($this->roles === []) {
            return false;
        }

        if (!$this->authorizationChecker->isGranted('IS_AUTHENTICATED')) {
            return false;
        }

        return $this->hasAnyRole();
    }

    private function hasAnyRole(): bool
    {
        foreach ($this->roles as $role) {
            if ($this->authorizationChecker->isGranted($role)) {
                return true;
            }
        }

        return false;
    }
}
