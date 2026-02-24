<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Profil;
use App\Entity\Patient;
use App\Entity\Medecin;
use App\Entity\Specialite;

class RegisterController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request;

            // Check CIN uniqueness
            $cinValue = (string)$data->get('cin');
            if ($cinValue !== '') {
                $existing = $em->getRepository(Profil::class)->findOneBy(['cin' => $cinValue]);
                if ($existing) {
                    $this->addFlash('warning', 'CIN already exists. Please use a different CIN.');
                    return $this->redirectToRoute('app_register');
                }
            }

            $profil = new Profil();
            if ($data->get('cin')) $profil->setCin($data->get('cin'));
            if ($data->get('name')) $profil->setName($data->get('name'));
            if ($data->get('last_name')) $profil->setLastName($data->get('last_name'));
            if ($data->get('role')) $profil->setRole($data->get('role'));
            if ($data->get('image')) $profil->setImage($data->get('image'));
            if ($data->get('tel')) $profil->setTel($data->get('tel'));
            if ($data->get('sexe')) $profil->setSexe($data->get('sexe'));
            // Password: hash before storing
            if ($data->get('password')) {
                $hashed = password_hash($data->get('password'), PASSWORD_BCRYPT);
                $profil->setPassword($hashed);
            }

            // Image upload handling
            $imageFile = $request->files->get('image');
            if ($imageFile) {
                $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
                if (!is_dir($uploadsDir)) {
                    @mkdir($uploadsDir, 0755, true);
                }
                $original = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safe = preg_replace('/[^a-zA-Z0-9_-]/', '', $original);
                $newFilename = $safe . '_' . uniqid() . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move($uploadsDir, $newFilename);
                    $profil->setImage('uploads/' . $newFilename);
                } catch (FileException $e) {
                    // ignore or log error; leave image null
                }
            }

            $em->persist($profil);

            $userType = $data->get('user_type');
            if ($userType === 'patient') {
                $patient = new Patient();
                $date = $data->get('date_inscription');
                if ($date) {
                    $patient->setDateInscription(new \DateTime($date));
                } else {
                    $patient->setDateInscription(new \DateTime());
                }
                $patient->setProfile($profil);
                $em->persist($patient);
            } else {
                $medecin = new Medecin();
                $date = $data->get('date_embauche');
                if ($date) {
                    $medecin->setDateEmbauche(new \DateTime($date));
                }
                // specialite: pick from dropdown (specialite_id)
                $specId = $data->get('specialite_id');
                if ($specId) {
                    $spec = $em->getRepository(Specialite::class)->find($specId);
                    if ($spec) {
                        $medecin->setSpecialite($spec);
                    }
                }
                $medecin->setProfile($profil);
                $em->persist($medecin);
            }

            $em->flush();

            $this->addFlash('success', 'Account created successfully.');

            return $this->redirectToRoute('app_register');
        }

        // Load specialities for the dropdown
        $specialites = $em->getRepository(Specialite::class)->findAll();

        return $this->render('register.html.twig', ['specialites' => $specialites]);
    }
}
