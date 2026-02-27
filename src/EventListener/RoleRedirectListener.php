<?php
namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RoleRedirectListener
{
    private Security $security;
    private LoggerInterface $logger;

    public function __construct(Security $security, LoggerInterface $logger)
    {
        $this->security = $security;
        $this->logger = $logger;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = rtrim($request->getPathInfo(), '/'); // normalisation du slash

        // ❌ Routes à exclure pour éviter boucle ou erreurs
        $excluded = [
            '/login',
            '/logout',
            '/register',
            '/api',
            '/_profiler',
            '/_wdt',
            '/favicon.ico',
            '/assets'
        ];
        foreach ($excluded as $ex) {
            if (str_starts_with($path, $ex)) {
                return;
            }
        }

        $user = $this->security->getUser();

        // 🔹 Si pas connecté, rediriger vers login
        if (!$user) {
            $this->logger->info(sprintf(
                'User not logged in, redirecting from %s to /login',
                $path
            ));
            $event->setResponse(new RedirectResponse('/login'));
            return;
        }

        // 🔹 Déterminer le dashboard selon le rôle
        $roles = $user->getRoles();
        $role = $roles[0] ?? null;

        $dashboard = match ($role) {
            'ROLE_ADMIN' => '/dashboard/admin',
            'ROLE_MEDECIN' => '/dashboard/medecin',
            'ROLE_PATIENT' => '/dashboard/patient',
            default => null
        };

        if (!$dashboard) {
            return;
        }

        // Normalisation pour comparaison
        $dashboardNormalized = rtrim($dashboard, '/');
        $pathNormalized = rtrim($path, '/');

        // 🔹 Redirection si l’utilisateur n’est pas déjà sur son dashboard
        if ($pathNormalized !== $dashboardNormalized) {
            $this->logger->info(sprintf(
                'Redirecting user %s with role %s from %s to %s',
                $user->getUserIdentifier(),
                $role,
                $pathNormalized,
                $dashboardNormalized
            ));

            $event->setResponse(new RedirectResponse($dashboardNormalized));
        }
    }
}