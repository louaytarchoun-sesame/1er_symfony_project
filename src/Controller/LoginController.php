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
            if (!$profil || !password_verify($password, (string)$profil->getPassword())) {
                $this->addFlash('warning', 'Wrong CIN or password.');
                return $this->redirectToRoute('app_login');
            }

            // Successful login: store profile id in session and redirect by role
            $request->getSession()->set('profil_id', $profil->getId());
            $this->addFlash('success', 'Logged in successfully.');
            $role = strtolower((string)$profil->getRole());
            if ($role === 'patient') {
                return $this->redirectToRoute('dashboard_patient');
            }
            if ($role === 'medecin' || $role === 'médecin') {
                return $this->redirectToRoute('dashboard_medecin');
            }
            return $this->redirectToRoute('dashboard_admin');
        }

        return $this->render('login/login.html.twig');
    }
}
