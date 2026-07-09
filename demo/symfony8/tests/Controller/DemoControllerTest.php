<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DemoControllerTest extends WebTestCase
{
    public function testHomepageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Console Debug Bundle');
        self::assertSelectorTextContains('table', 'Full Twig context');
    }

    public function testDebugPageRequiresLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/debug');

        self::assertResponseRedirects('/login');
    }

    public function testDebuggerSeesInjectedScript(): void
    {
        $client = $this->createAuthenticatedClient('debugger', 'debug');

        $client->request('GET', '/debug');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('<script data-nowo-console-debug>', (string) $client->getResponse()->getContent());
    }

    public function testViewerDoesNotSeeInjectedScript(): void
    {
        $client = $this->createAuthenticatedClient('viewer', 'viewer');

        $client->request('GET', '/debug');
        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('<script data-nowo-console-debug>', (string) $client->getResponse()->getContent());
    }

    public function testJsonEndpointDoesNotInjectScript(): void
    {
        $client = $this->createAuthenticatedClient('debugger', 'debug');

        $client->request('GET', '/debug/data.json');
        self::assertResponseIsSuccessful();
        self::assertSame('application/json', $client->getResponse()->headers->get('Content-Type'));
        self::assertStringNotContainsString('<script data-nowo-console-debug>', (string) $client->getResponse()->getContent());
    }

    private function createAuthenticatedClient(string $username, string $password): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        $client = static::createClient();
        $client->request('GET', '/login');
        $client->submitForm('Sign in', [
            '_username' => $username,
            '_password' => $password,
        ]);

        return $client;
    }
}
