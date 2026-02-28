<?php

namespace App\Controller;

use App\Entity\Profil;
use App\Entity\ResetPasswordToken;
use App\Repository\ResetPasswordTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ForgotPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        ResetPasswordTokenRepository $tokenRepo
    ): Response {
        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email'));

            if (!$email) {
                $this->addFlash('warning', 'Veuillez entrer votre adresse e-mail.');
                return $this->redirectToRoute('app_forgot_password');
            }

            $profil = $em->getRepository(Profil::class)->findOneBy(['email' => $email]);

            if ($profil) {
                // Remove any existing tokens for this user
                $tokenRepo->removeTokensForProfil($profil->getId());

                // Generate a secure token
                $token = bin2hex(random_bytes(32));

                $resetToken = new ResetPasswordToken();
                $resetToken->setProfil($profil);
                $resetToken->setToken($token);
                $resetToken->setExpiresAt(new \DateTime('+1 hour'));

                $em->persist($resetToken);
                $em->flush();

                // Build the reset link
                $resetUrl = $this->generateUrl('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

                // Send the email
                $emailMessage = (new Email())
                    ->from('noreply@medgestion.com')
                    ->to($profil->getEmail())
                    ->subject('Réinitialisation de votre mot de passe - MedGestion')
                    ->html(
                        '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px;">' .
                        '<div style="text-align:center;padding:20px;background:linear-gradient(135deg,#667eea,#764ba2);border-radius:12px 12px 0 0;">' .
                        '<h1 style="color:#fff;margin:0;">MedGestion</h1>' .
                        '</div>' .
                        '<div style="padding:30px;background:#fff;border:1px solid #e0e0e0;border-top:none;border-radius:0 0 12px 12px;">' .
                        '<h2 style="color:#333;">Réinitialisation du mot de passe</h2>' .
                        '<p style="color:#555;">Bonjour <strong>' . htmlspecialchars($profil->getName()) . '</strong>,</p>' .
                        '<p style="color:#555;">Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le bouton ci-dessous pour définir un nouveau mot de passe :</p>' .
                        '<div style="text-align:center;margin:30px 0;">' .
                        '<a href="' . $resetUrl . '" style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;padding:14px 32px;text-decoration:none;border-radius:8px;font-weight:bold;font-size:16px;">Réinitialiser mon mot de passe</a>' .
                        '</div>' .
                        '<p style="color:#888;font-size:13px;">Ce lien expirera dans <strong>1 heure</strong>.</p>' .
                        '<p style="color:#888;font-size:13px;">Si vous n\'avez pas demandé cette réinitialisation, vous pouvez ignorer cet e-mail en toute sécurité.</p>' .
                        '<hr style="border:none;border-top:1px solid #eee;margin:20px 0;">' .
                        '<p style="color:#aaa;font-size:11px;text-align:center;">© MedGestion ' . date('Y') . '</p>' .
                        '</div>' .
                        '</div>'
                    );

                $mailer->send($emailMessage);
            }

            // Always show the same message to prevent email enumeration
            $this->addFlash('success', 'Si un compte existe avec cette adresse e-mail, un lien de réinitialisation a été envoyé.');
            return $this->redirectToRoute('app_forgot_password');
        }

        return $this->render('login/forgot_password.html.twig');
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(
        string $token,
        Request $request,
        EntityManagerInterface $em,
        ResetPasswordTokenRepository $tokenRepo
    ): Response {
        // Cleanup expired tokens
        $tokenRepo->removeExpiredTokens();

        $resetToken = $em->getRepository(ResetPasswordToken::class)->findOneBy(['token' => $token]);

        if (!$resetToken || $resetToken->isExpired()) {
            $this->addFlash('danger', 'Ce lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $password = (string) $request->request->get('password');
            $passwordConfirm = (string) $request->request->get('password_confirm');

            if (strlen($password) < 6) {
                $this->addFlash('warning', 'Le mot de passe doit contenir au moins 6 caractères.');
                return $this->redirectToRoute('app_reset_password', ['token' => $token]);
            }

            if ($password !== $passwordConfirm) {
                $this->addFlash('warning', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_reset_password', ['token' => $token]);
            }

            $profil = $resetToken->getProfil();
            $profil->setPassword(password_hash($password, PASSWORD_BCRYPT));

            // Remove all tokens for this user
            $tokenRepo->removeTokensForProfil($profil->getId());

            $em->flush();

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('login/reset_password.html.twig', [
            'token' => $token,
        ]);
    }
}
