<?php

declare(strict_types=1);

namespace App\Controller;

use App\Demo\DemoCustomer;
use App\Demo\DemoEntity;
use App\Demo\DemoPriority;
use Nowo\ConsoleDebugBundle\ConsoleDebugRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function Nowo\ConsoleDebugBundle\cdbg;

final class DemoController extends AbstractController
{
    #[Route(path: '/', name: 'homepage', methods: ['GET'])]
    public function home(): Response
    {
        return $this->render('demo/home.html.twig');
    }

    #[Route(path: '/debug', name: 'demo_debug', methods: ['GET'])]
    public function debug(Request $request, ConsoleDebugRegistry $registry): Response
    {
        $user = $this->getUser()?->getUserIdentifier();

        $order = [
            'id'     => 42,
            'total'  => 99.5,
            'labels' => ['express', 'gift-wrap'],
        ];

        $demoMeta = [
            'bundle'  => 'nowo-tech/console-debug-bundle',
            'symfony' => 'demo',
        ];

        // PHP · single variable (no label)
        cdbg($order);

        // PHP · label + multiple arguments
        cdbg('pricing snapshot', $order, $request->query->all());

        // PHP · label + mixed types (enum, date, scalar)
        cdbg(
            'mixed types',
            DemoPriority::High,
            new \DateTimeImmutable('2026-07-09'),
            $user,
        );

        // PHP · sequential flow (multiple calls in one request)
        cdbg('flow · step 1', ['route' => $request->attributes->get('_route')]);
        cdbg('flow · step 2', ['user' => $user, 'roles' => $this->getUser()?->getRoles() ?? []]);
        cdbg('flow · step 3', ['status' => 'ready']);

        $entity   = new DemoEntity(7, 'jane@example.com');
        $customer = new DemoCustomer(7, 'Jane');

        // PHP · entity-like object (no __toString) → { object, hash } in console
        cdbg('entity without __toString', $entity);

        // PHP · object with __toString() → string in console
        cdbg('entity with __toString', $customer);

        $phpEntryCount = \count($registry->all());

        return $this->render('demo/debug.html.twig', [
            'user'          => $user,
            'order'         => $order,
            'demoMeta'      => $demoMeta,
            'priority'      => DemoPriority::High,
            'demoEntity'    => $entity,
            'demoCustomer'  => $customer,
            'phpEntryCount' => $phpEntryCount,
        ]);
    }

    #[Route(path: '/debug/data.json', name: 'demo_debug_json', methods: ['GET'])]
    public function debugJson(): JsonResponse
    {
        // Collected when gate is open, but not injected on non-HTML responses.
        cdbg('json endpoint', ['injected' => false, 'format' => 'application/json']);

        return $this->json([
            'ok'      => true,
            'message' => 'Open this URL while logged in as debugger — cdbg runs server-side but no script is injected.',
        ]);
    }
}
