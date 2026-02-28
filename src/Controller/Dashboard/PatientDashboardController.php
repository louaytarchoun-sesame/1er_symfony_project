<?php

namespace App\Controller\Dashboard;

use App\Entity\Medecin;
use App\Entity\Patient;
use App\Entity\Profil;
use App\Entity\RendezVous;
use App\Entity\Specialite;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/dashboard/patient')]
class PatientDashboardController extends AbstractController
{
    // ─── Accueil (RDV creation flow) ───
    #[Route('/', name: 'patient_dashboard')]
    public function index(EntityManagerInterface $em): Response
    {
        $specialites = $em->getRepository(Specialite::class)->findAll();

        return $this->render('dashboard/patient/dashboard.patient.html.twig', [
            'specialites' => $specialites,
        ]);
    }

    // ─── Mon Profil ───
    #[Route('/profil', name: 'patient_profil')]
    public function profil(Request $request, Security $security, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        /** @var Profil $user */
        $user = $security->getUser();

        if ($request->isMethod('POST')) {
            $user->setName($request->request->get('name', $user->getName()));
            $user->setLastName($request->request->get('last_name', $user->getLastName()));
            $user->setEmail($request->request->get('email', $user->getEmail()));
            $user->setTel($request->request->get('tel', $user->getTel()));
            $user->setSexe($request->request->get('sexe', $user->getSexe()));

            $dateStr = $request->request->get('dateNaissance');
            if ($dateStr) {
                $user->setDateNaissance(new \DateTime($dateStr));
            }

            // Handle profile image upload
            $imageFile = $request->files->get('profileImage');
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '_' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads',
                        $newFilename
                    );

                    // Delete old image if it exists
                    $oldImage = $user->getImage();
                    if ($oldImage) {
                        $oldPath = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $oldImage;
                        if (file_exists($oldPath)) {
                            unlink($oldPath);
                        }
                    }

                    $user->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur lors du téléchargement de l\'image.');
                }
            }

            $em->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès.');
            return $this->redirectToRoute('patient_profil');
        }

        return $this->render('dashboard/patient/profil.html.twig', [
            'user' => $user,
        ]);
    }

    // ─── Changer le mot de passe ───
    #[Route('/change-password', name: 'patient_change_password', methods: ['POST'])]
    public function changePassword(Request $request, Security $security, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        /** @var Profil $user */
        $user = $security->getUser();

        $oldPassword = $request->request->get('old_password');
        $newPassword = $request->request->get('new_password');
        $confirmPassword = $request->request->get('confirm_password');

        // Verify old password
        if (!$hasher->isPasswordValid($user, $oldPassword)) {
            $this->addFlash('danger', 'L\'ancien mot de passe est incorrect.');
            return $this->redirectToRoute('patient_profil');
        }

        // Check new passwords match
        if ($newPassword !== $confirmPassword) {
            $this->addFlash('danger', 'Les nouveaux mots de passe ne correspondent pas.');
            return $this->redirectToRoute('patient_profil');
        }

        // Check minimum length
        if (strlen($newPassword) < 6) {
            $this->addFlash('danger', 'Le nouveau mot de passe doit contenir au moins 6 caractères.');
            return $this->redirectToRoute('patient_profil');
        }

        // Hash and set new password
        $user->setPassword($hasher->hashPassword($user, $newPassword));
        $em->flush();

        $this->addFlash('success', 'Mot de passe changé avec succès.');
        return $this->redirectToRoute('patient_profil');
    }

    // ─── Mes Rendez-vous ───
    #[Route('/rendezvous', name: 'patient_rendezvous')]
    public function rendezvous(Security $security, EntityManagerInterface $em): Response
    {
        /** @var Profil $user */
        $user = $security->getUser();
        $patient = $em->getRepository(Patient::class)->findOneBy(['profile' => $user]);

        $rdvs = [];
        if ($patient) {
            $rdvs = $em->getRepository(RendezVous::class)->findBy(
                ['patient' => $patient],
                ['date' => 'DESC']
            );
        }

        return $this->render('dashboard/patient/rendezvous.html.twig', [
            'rendezvous' => $rdvs,
        ]);
    }

    // ─── API: Get medecins by spécialité ───
    #[Route('/api/medecins/{specialiteId}', name: 'patient_api_medecins', methods: ['GET'])]
    public function getMedecinsBySpecialite(int $specialiteId, EntityManagerInterface $em): JsonResponse
    {
        $medecins = $em->getRepository(Medecin::class)->findBy(['specialite' => $specialiteId]);

        $data = [];
        foreach ($medecins as $med) {
            $profile = $med->getProfile();
            $data[] = [
                'id'            => $med->getId(),
                'name'          => $profile ? $profile->getName() : 'N/A',
                'lastName'      => $profile ? $profile->getLastName() : '',
                'image'         => $profile ? $profile->getImage() : null,
                'sexe'          => $profile ? $profile->getSexe() : '',
                'localisation'  => $med->getLocalisation(),
                'specialite'    => $med->getSpecialite() ? $med->getSpecialite()->getLabelle() : '',
            ];
        }

        return new JsonResponse($data);
    }

    // ─── API: Get available slots for a medecin on a date ───
    #[Route('/api/slots/{medecinId}/{date}', name: 'patient_api_slots', methods: ['GET'])]
    public function getSlots(int $medecinId, string $date, EntityManagerInterface $em): JsonResponse
    {
        $medecin = $em->getRepository(Medecin::class)->find($medecinId);
        if (!$medecin) {
            return new JsonResponse(['error' => 'Médecin introuvable'], 404);
        }

        // Validate date is today or in the future
        $today = new \DateTime('today');
        $selectedDay = new \DateTime($date);
        if ($selectedDay < $today) {
            return new JsonResponse(['error' => 'La date doit être aujourd\'hui ou dans le futur'], 400);
        }

        // Generate all 30-min slots from 08:00 to 17:00
        $allSlots = [];
        $start = new \DateTime($date . ' 08:00');
        $end = new \DateTime($date . ' 17:00');
        $now = new \DateTime();
        $isToday = ($selectedDay->format('Y-m-d') === $now->format('Y-m-d'));

        while ($start < $end) {
            // Skip past slots if the date is today
            if (!$isToday || $start > $now) {
                $allSlots[] = $start->format('H:i');
            }
            $start->modify('+30 minutes');
        }

        // Find existing RDVs for that medecin on that date
        $dayStart = new \DateTime($date . ' 00:00');
        $dayEnd = new \DateTime($date . ' 23:59:59');

        $existingRdvs = $em->createQueryBuilder()
            ->select('r')
            ->from(RendezVous::class, 'r')
            ->where('r.medecin = :medecin')
            ->andWhere('r.date BETWEEN :start AND :end')
            ->setParameter('medecin', $medecin)
            ->setParameter('start', $dayStart)
            ->setParameter('end', $dayEnd)
            ->getQuery()
            ->getResult();

        $bookedSlots = [];
        foreach ($existingRdvs as $rdv) {
            $bookedSlots[] = $rdv->getDate()->format('H:i');
        }

        $slots = [];
        foreach ($allSlots as $slot) {
            $slots[] = [
                'time'    => $slot,
                'booked'  => in_array($slot, $bookedSlots),
            ];
        }

        return new JsonResponse($slots);
    }

    // ─── API: Book a rendez-vous ───
    #[Route('/api/book', name: 'patient_api_book', methods: ['POST'])]
    public function bookRendezVous(Request $request, Security $security, EntityManagerInterface $em): JsonResponse
    {
        try {
            /** @var Profil $user */
            $user = $security->getUser();
            if (!$user) {
                return new JsonResponse(['error' => 'Non authentifié'], 401);
            }

            $patient = $em->getRepository(Patient::class)->findOneBy(['profile' => $user]);

            if (!$patient) {
                // Auto-create patient record if missing
                $patient = new Patient();
                $patient->setProfile($user);
                $patient->setDateInscription(new \DateTime());
                $em->persist($patient);
                $em->flush();
            }

            $content = $request->getContent();
            $data = json_decode($content, true);

            if (!$data) {
                return new JsonResponse(['error' => 'Corps de requête invalide'], 400);
            }

            $medecinId = $data['medecinId'] ?? null;
            $dateStr   = $data['date'] ?? null;
            $time      = $data['time'] ?? null;
            $motif     = $data['motif'] ?? '';

            if (!$medecinId || !$dateStr || !$time) {
                return new JsonResponse(['error' => 'Paramètres manquants'], 400);
            }

            // Validate date is today or in the future
            $today = new \DateTime('today');
            $selectedDay = new \DateTime($dateStr);
            if ($selectedDay < $today) {
                return new JsonResponse(['error' => 'La date doit être aujourd\'hui ou dans le futur'], 400);
            }

            $medecin = $em->getRepository(Medecin::class)->find($medecinId);
            if (!$medecin) {
                return new JsonResponse(['error' => 'Médecin introuvable'], 404);
            }

            // Check slot is not already booked
            $dateTime = new \DateTime($dateStr . ' ' . $time);
            $existing = $em->getRepository(RendezVous::class)->findOneBy([
                'medecin' => $medecin,
                'date'    => $dateTime,
            ]);

            if ($existing) {
                return new JsonResponse(['error' => 'Ce créneau est déjà réservé'], 409);
            }

            $rdv = new RendezVous();
            $rdv->setDate($dateTime);
            $rdv->setDuree(30);
            $rdv->setMotif($motif);
            $rdv->setEtat('en_attente');
            $rdv->setPatient($patient);
            $rdv->setMedecin($medecin);
            $rdv->setCreatedAt(new \DateTime());
            $rdv->setUpdatedAt(new \DateTime());

            $em->persist($rdv);
            $em->flush();

            return new JsonResponse(['success' => true, 'message' => 'Rendez-vous créé avec succès !']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur serveur: ' . $e->getMessage()], 500);
        }
    }
}