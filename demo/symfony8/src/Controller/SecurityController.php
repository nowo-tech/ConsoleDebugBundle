<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\LoginFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils, TranslatorInterface $translator): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        if (null !== $error) {
            $this->addFlash('error', $translator->trans($error->getMessageKey(), $error->getMessageData(), 'security'));
        }

        $loginForm = $this->createForm(LoginFormType::class, [
            '_username' => $authenticationUtils->getLastUsername(),
        ]);

        return $this->render('security/login.html.twig', [
            'loginForm'     => $loginForm,
            'last_username' => $authenticationUtils->getLastUsername(),
        ]);
    }

    #[Route(path: '/logout', name: 'logout', methods: ['GET'])]
    public function logout(): void
    {
        throw new \LogicException('Logout is handled by the firewall.');
    }
}
