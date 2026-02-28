<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Profil;
use Firebase\JWT\JWT;

class LoginController extends AbstractController
{
    private string $jwtSecret;

    public function __construct(string $jwtSecret)
    {
        $this->jwtSecret = $jwtSecret;
    }

    #[Route('/login', name: 'app_login', methods: ['GET','POST'])]
    public function login(Request $request, EntityManagerInterface $em): Response
    {
        $profil = $this->getUser(); // récupère Profil connecté via JwtAuthenticator

        if ($profil) {
            // redirection automatique si déjà connecté
            $role = 'ROLE_' . strtoupper($profil->getRole());
            $redirectUrl = match($role) {
                'ROLE_PATIENT' => '/dashboard/patient',
                'ROLE_MEDECIN' => '/dashboard/medecin',
                'ROLE_ADMIN' => '/dashboard/admin',
                default => '/login'
            };
            return $this->redirect(rtrim($redirectUrl, '/'));
        }

        if ($request->isMethod('POST')) {
            $cin = (string)$request->request->get('cin');
            $password = (string)$request->request->get('password');

            if (!$cin || !$password) {
                $this->addFlash('warning', 'CIN et mot de passe requis.');
                return $this->redirectToRoute('app_login');
            }

            $profil = $em->getRepository(Profil::class)->findOneBy(['cin' => $cin]);

            if (!$profil || !password_verify($password, $profil->getPassword())) {
                $this->addFlash('warning', 'CIN ou mot de passe incorrect.');
                return $this->redirectToRoute('app_login');
            }

            $role = 'ROLE_' . strtoupper($profil->getRole());
            $payload = [
                'sub' => $profil->getId(),
                'role' => $role,
                'name' => $profil->getName(),
                'lastName' => $profil->getLastName(),
                'email' => $profil->getEmail(),
                'image' => $profil->getImage(),
                'exp' => time() + 3600
            ];

            $jwt = JWT::encode($payload, $this->jwtSecret, 'HS256');

            $redirectUrl = match($role) {
                'ROLE_PATIENT' => '/dashboard/patient',
                'ROLE_MEDECIN' => '/dashboard/medecin',
                'ROLE_ADMIN' => '/dashboard/admin',
                default => '/login'
            };

            $cookie = Cookie::create('jwt')
                ->withValue($jwt)
                ->withExpires(time() + 3600)
                ->withPath('/')
                ->withHttpOnly(true)
                ->withSecure(false)
                ->withSameSite(Cookie::SAMESITE_LAX);

            $response = $this->redirect($redirectUrl);
            $response->headers->setCookie($cookie);

            return $response;
        }

        return $this->render('login/login.html.twig');
    }

#[Route('/logout', name: 'app_logout', methods: ['GET','POST'])]
public function logout(Request $request): Response
{
    // Vider la session Symfony
    $request->getSession()->invalidate();

    // Créer le cookie pour supprimer le JWT côté client
    $cookie = Cookie::create('jwt')
        ->withValue('')
        ->withExpires(new \DateTime('-1 day')) // date passée
        ->withPath('/')
        ->withHttpOnly(true)
        ->withSecure(false)
        ->withSameSite(Cookie::SAMESITE_LAX);

    // Créer la réponse de redirection et ajouter le cookie
    $response = $this->redirectToRoute('app_login');
    $response->headers->setCookie($cookie);

    return $response;
}
}