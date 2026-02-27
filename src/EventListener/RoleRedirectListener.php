<?php
namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Bundle\SecurityBundle\Security;
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
        $path = rtrim($request->getPathInfo(), '/');

        // ❌ Routes à exclure (assets, profiler, API, etc.)
        $excluded = [
            '/_profiler',
            '/_wdt',
            '/favicon.ico',
            '/assets',
            '/dashboard/patient/api',
            '/dashboard/medecin/api',
            '/dashboard/admin/api',
        ];
        foreach ($excluded as $ex) {
            if (str_starts_with($path, $ex)) {
                return;
            }
        }

        $user = $this->security->getUser();

        // 🔹 Si connecté et sur login/register → rediriger vers dashboard
        if ($user && in_array($path, ['/login', '/register'])) {
            $dashboard = $this->getDashboardByRole($user);
            $event->setResponse(new RedirectResponse($dashboard));
            return;
        }

        // 🔹 Si pas connecté et accès à un dashboard → rediriger vers login
        $protectedDashboards = ['/dashboard/admin', '/dashboard/medecin', '/dashboard/patient'];
        foreach ($protectedDashboards as $dash) {
            if (str_starts_with($path, $dash) && !$user) {
                $event->setResponse(new RedirectResponse('/login'));
                return;
            }
        }

        // 🔹 Si connecté et accès à un dashboard qui n’est pas le sien → rediriger vers son dashboard
        if ($user) {
            $dashboardByRole = $this->getDashboardByRole($user);
            foreach ($protectedDashboards as $dash) {
                if (str_starts_with($path, $dash) && !str_starts_with($path, $dashboardByRole)) {
                    $this->logger->info(sprintf(
                        'Redirecting user %s with role %s from %s to %s',
                        $user->getUserIdentifier(),
                        $user->getRoles()[0] ?? 'N/A',
                        $path,
                        $dashboardByRole
                    ));
                    $event->setResponse(new RedirectResponse($dashboardByRole));
                    return;
                }
            }
        }
    }

    private function getDashboardByRole($user): string
    {
        $role = $user->getRoles()[0] ?? null;

        return match ($role) {
            'ROLE_ADMIN' => '/dashboard/admin',
            'ROLE_MEDECIN' => '/dashboard/medecin',
            'ROLE_PATIENT' => '/dashboard/patient',
            default => '/',
        };
    }
}