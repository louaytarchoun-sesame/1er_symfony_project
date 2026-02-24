<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Profil;
use App\Entity\Patient;
use App\Entity\Medecin;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(Request $request, EntityManagerInterface $em): Response
    {
        $error = null;
        $token = null;
        $payload = null;

        if ($request->isMethod('POST')) {
            $cin = (string)$request->request->get('cin');
            $password = (string)$request->request->get('password');

            $profil = $em->getRepository(Profil::class)->findOneBy(['cin' => $cin]);
            if (!$profil) {
                $error = 'Invalid credentials.';
            } else {
                if (!password_verify($password, (string)$profil->getPassword())) {
                    $error = 'Invalid credentials.';
                } else {
                    // Build payload
                    $payload = [
                        'sub' => $profil->getId(),
                        'cin' => $profil->getCin(),
                        'name' => $profil->getName(),
                        'last_name' => $profil->getLastName(),
                        'role' => $profil->getRole(),
                        'tel' => $profil->getTel(),
                        'sexe' => $profil->getSexe(),
                        'image' => $profil->getImage(),
                    ];

                    // Include patient or medecin data if exists
                    $patient = $em->getRepository(Patient::class)->findOneBy(['profile' => $profil]);
                    if ($patient) {
                        $payload['patient'] = [
                            'id' => $patient->getId(),
                            'date_inscription' => $patient->getDateInscription() ? $patient->getDateInscription()->format('c') : null,
                        ];
                    }
                    $medecin = $em->getRepository(Medecin::class)->findOneBy(['profile' => $profil]);
                    if ($medecin) {
                        $payload['medecin'] = [
                            'id' => $medecin->getId(),
                            'date_embauche' => $medecin->getDateEmbauche() ? $medecin->getDateEmbauche()->format('c') : null,
                            'specialite' => $medecin->getSpecialite() ? $medecin->getSpecialite()->getLabelle() : null,
                        ];
                    }

                    // Add iat/exp
                    $now = time();
                    $exp = $now + 60 * 60 * 24; // 24h
                    $jwtPayload = array_merge($payload, ['iat' => $now, 'exp' => $exp]);

                    // create JWT (HS256) using kernel.secret
                    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
                    $base64UrlEncode = function ($data) {
                        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
                    };
                    $headerEncoded = $base64UrlEncode(json_encode($header));
                    $payloadEncoded = $base64UrlEncode(json_encode($jwtPayload));
                    $secret = $this->getParameter('kernel.secret');
                    $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $secret, true);
                    $signatureEncoded = $base64UrlEncode($signature);
                    $token = $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
                }
            }
        }

        return $this->render('login.html.twig', ['error' => $error, 'token' => $token, 'payload' => $payload]);
    }
}
