<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Tests\Unit\Gate;

use Nowo\ConsoleDebugBundle\Contract\ConsoleDebugGateInterface;
use Nowo\ConsoleDebugBundle\Gate\EnabledConsoleDebugGate;
use Nowo\ConsoleDebugBundle\Gate\QueryParamConsoleDebugGate;
use Nowo\ConsoleDebugBundle\Gate\RoleBasedConsoleDebugGate;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class GateTest extends TestCase
{
    public function testEnabledGate(): void
    {
        self::assertTrue((new EnabledConsoleDebugGate(true))->isEnabled());
        self::assertFalse((new EnabledConsoleDebugGate(false))->isEnabled());
    }

    public function testRoleGateRequiresAuthenticationAndRole(): void
    {
        $inner = new EnabledConsoleDebugGate(true);
        $auth  = $this->createMock(AuthorizationCheckerInterface::class);
        $auth->method('isGranted')->willReturnCallback(static function (string $attribute): bool {
            return $attribute === 'IS_AUTHENTICATED' || $attribute === 'ROLE_CONSOLE_DEBUG';
        });

        $gate = new RoleBasedConsoleDebugGate($inner, $auth, ['ROLE_CONSOLE_DEBUG']);
        self::assertTrue($gate->isEnabled());
    }

    public function testRoleGateRejectsAnonymousUser(): void
    {
        $inner = new EnabledConsoleDebugGate(true);
        $auth  = $this->createMock(AuthorizationCheckerInterface::class);
        $auth->method('isGranted')->willReturnCallback(static fn (string $attribute): bool => $attribute !== 'IS_AUTHENTICATED');

        $gate = new RoleBasedConsoleDebugGate($inner, $auth, ['ROLE_CONSOLE_DEBUG']);
        self::assertFalse($gate->isEnabled());
    }

    public function testRoleGateRejectsWhenNoRoleMatches(): void
    {
        $inner = new EnabledConsoleDebugGate(true);
        $auth  = $this->createMock(AuthorizationCheckerInterface::class);
        $auth->method('isGranted')->willReturnCallback(static function (string $attribute): bool {
            return $attribute === 'IS_AUTHENTICATED';
        });

        $gate = new RoleBasedConsoleDebugGate($inner, $auth, ['ROLE_CONSOLE_DEBUG']);
        self::assertFalse($gate->isEnabled());
    }

    public function testRoleGateRejectsWhenNoRolesConfigured(): void
    {
        $inner = new EnabledConsoleDebugGate(true);
        $auth  = $this->createMock(AuthorizationCheckerInterface::class);
        $auth->method('isGranted')->willReturn(true);

        $gate = new RoleBasedConsoleDebugGate($inner, $auth, []);
        self::assertFalse($gate->isEnabled());
    }

    public function testRoleGateAcceptsAnyMatchingRole(): void
    {
        $inner = new EnabledConsoleDebugGate(true);
        $auth  = $this->createMock(AuthorizationCheckerInterface::class);
        $auth->method('isGranted')->willReturnCallback(static function (string $attribute): bool {
            return match ($attribute) {
                'IS_AUTHENTICATED' => true,
                'ROLE_A'           => false,
                'ROLE_B'           => true,
                default            => false,
            };
        });

        $gate = new RoleBasedConsoleDebugGate($inner, $auth, ['ROLE_A', 'ROLE_B']);
        self::assertTrue($gate->isEnabled());
    }

    public function testRoleGateDisabledWhenInnerGateIsOff(): void
    {
        $auth = $this->createMock(AuthorizationCheckerInterface::class);
        $auth->method('isGranted')->willReturn(true);

        $gate = new RoleBasedConsoleDebugGate(new EnabledConsoleDebugGate(false), $auth, ['ROLE_X']);
        self::assertFalse($gate->isEnabled());
    }

    public function testQueryParamGateRequiresParameter(): void
    {
        $inner = $this->createMock(ConsoleDebugGateInterface::class);
        $inner->method('isEnabled')->willReturn(true);

        $request = Request::create('/demo?console_debug=1');
        $stack   = new RequestStack();
        $stack->push($request);

        $gate = new QueryParamConsoleDebugGate($inner, $stack, 'console_debug');
        self::assertTrue($gate->isEnabled());

        $stack->pop();
        $stack->push(Request::create('/demo'));
        self::assertFalse($gate->isEnabled());
    }

    public function testQueryParamGateRespectsInnerGate(): void
    {
        $inner = $this->createMock(ConsoleDebugGateInterface::class);
        $inner->method('isEnabled')->willReturn(false);

        $stack = new RequestStack();
        $stack->push(Request::create('/demo?console_debug=1'));

        $gate = new QueryParamConsoleDebugGate($inner, $stack, 'console_debug');
        self::assertFalse($gate->isEnabled());
    }

    public function testQueryParamGateWithoutMainRequest(): void
    {
        $inner = $this->createMock(ConsoleDebugGateInterface::class);
        $inner->method('isEnabled')->willReturn(true);

        $gate = new QueryParamConsoleDebugGate($inner, new RequestStack(), 'console_debug');
        self::assertFalse($gate->isEnabled());
    }
}
