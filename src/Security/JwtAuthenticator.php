<?php
namespace App\Security;

use App\Entity\Profil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtAuthenticator extends AbstractAuthenticator
{
    private string $jwtSecret;
    private EntityManagerInterface $em;

    public function __construct(string $jwtSecret, EntityManagerInterface $em)
    {
        $this->jwtSecret = $jwtSecret;
        $this->em = $em;
    }

    public function supports(Request $request): ?bool
    {
        $jwt = $request->cookies->get('jwt');
        return $jwt && $jwt !== '';
    }

    public function authenticate(Request $request): Passport
    {
        $jwt = $request->cookies->get('jwt');

        if (!$jwt) {
            throw new AuthenticationException('JWT missing');
        }

        try {
            $payload = JWT::decode($jwt, new Key($this->jwtSecret, 'HS256'));
        } catch (\Exception $e) {
            throw new AuthenticationException('Invalid JWT');
        }

        if ($payload->exp < time()) {
            throw new AuthenticationException('JWT expired');
        }

        return new SelfValidatingPassport(
            new UserBadge((string)$payload->sub, function ($userIdentifier) {
                $profil = $this->em->getRepository(Profil::class)->find($userIdentifier);
                if (!$profil) {
                    throw new AuthenticationException('User not found');
                }
                return $profil;
            })
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        return null; // continue normalement
    }

    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): ?Response {
        // Redirection si pas connecté ou JWT invalide
        return new RedirectResponse('/login');
    }
}