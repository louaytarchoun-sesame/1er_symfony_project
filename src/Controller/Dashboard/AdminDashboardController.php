<?php

namespace App\Controller\Dashboard;

use App\Entity\Medecin;
use App\Entity\Patient;
use App\Entity\Profil;
use App\Entity\RendezVous;
use App\Entity\Specialite;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dashboard/admin')]
class AdminDashboardController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function index(EntityManagerInterface $em): Response
    {
        $profilRepo = $em->getRepository(Profil::class);
        $rdvRepo    = $em->getRepository(RendezVous::class);

        $patientsCount  = $profilRepo->count(['role' => 'PATIENT']);
        $medecinsCount  = $profilRepo->count(['role' => 'MEDECIN']);
        $adminsCount    = $profilRepo->count(['role' => 'ADMIN']);
        $totalUsers     = $profilRepo->count([]);
        $rdvCount       = $rdvRepo->count([]);
        $specialitesCount = $em->getRepository(Specialite::class)->count([]);

        // Rendez-vous by status
        $rdvByStatus = $em->createQueryBuilder()
            ->select('r.etat, COUNT(r.id) as total')
            ->from(RendezVous::class, 'r')
            ->groupBy('r.etat')
            ->getQuery()
            ->getResult();

        // Recent users (last 5)
        $recentUsers = $profilRepo->findBy([], ['id' => 'DESC'], 5);

        return $this->render('dashboard/admin/dashboard.admin.html.twig', [
            'patientsCount'    => $patientsCount,
            'medecinsCount'    => $medecinsCount,
            'adminsCount'      => $adminsCount,
            'totalUsers'       => $totalUsers,
            'rdvCount'         => $rdvCount,
            'specialitesCount' => $specialitesCount,
            'rdvByStatus'      => $rdvByStatus,
            'recentUsers'      => $recentUsers,
        ]);
    }

    #[Route('/users', name: 'admin_users')]
    public function users(EntityManagerInterface $em): Response
    {
        $users = $em->getRepository(Profil::class)->createQueryBuilder('p')
            ->where('p.role != :admin')
            ->setParameter('admin', 'ADMIN')
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('dashboard/admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/users/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function deleteUser(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $profil = $em->getRepository(Profil::class)->find($id);

        if (!$profil) {
            $this->addFlash('danger', 'Utilisateur introuvable.');
            return $this->redirectToRoute('admin_users');
        }

        if ($profil->getRole() === 'ADMIN') {
            $this->addFlash('danger', 'Impossible de supprimer un administrateur.');
            return $this->redirectToRoute('admin_users');
        }

        // Validate CSRF token
        if (!$this->isCsrfTokenValid('delete_user_' . $id, $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_users');
        }

        $role = $profil->getRole();

        if ($role === 'PATIENT') {
            $patient = $em->getRepository(Patient::class)->findOneBy(['profile' => $profil]);
            if ($patient) {
                // Delete all rendez-vous linked to this patient
                $rdvs = $em->getRepository(RendezVous::class)->findBy(['patient' => $patient]);
                foreach ($rdvs as $rdv) {
                    $em->remove($rdv);
                }
                $em->remove($patient);
            }
        } elseif ($role === 'MEDECIN') {
            $medecin = $em->getRepository(Medecin::class)->findOneBy(['profile' => $profil]);
            if ($medecin) {
                // Delete all rendez-vous linked to this medecin
                $rdvs = $em->getRepository(RendezVous::class)->findBy(['medecin' => $medecin]);
                foreach ($rdvs as $rdv) {
                    $em->remove($rdv);
                }
                $em->remove($medecin);
            }
        }

        $em->remove($profil);
        $em->flush();

        $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        return $this->redirectToRoute('admin_users');
    }

    // ─── API: Get user details (JSON) ───
    #[Route('/users/{id}/detail', name: 'admin_user_detail', methods: ['GET'])]
    public function userDetail(int $id, EntityManagerInterface $em): JsonResponse
    {
        $profil = $em->getRepository(Profil::class)->find($id);
        if (!$profil) {
            return new JsonResponse(['error' => 'Utilisateur introuvable'], 404);
        }

        return new JsonResponse([
            'id'            => $profil->getId(),
            'cin'           => $profil->getCin(),
            'name'          => $profil->getName(),
            'lastName'      => $profil->getLastName(),
            'role'          => $profil->getRole(),
            'tel'           => $profil->getTel(),
            'sexe'          => $profil->getSexe(),
            'image'         => $profil->getImage(),
            'dateNaissance' => $profil->getDateNaissance() ? $profil->getDateNaissance()->format('Y-m-d') : null,
        ]);
    }

    // ─── API: Update user (JSON) ───
    #[Route('/users/{id}/update', name: 'admin_user_update', methods: ['POST'])]
    public function updateUser(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $profil = $em->getRepository(Profil::class)->find($id);
        if (!$profil) {
            return new JsonResponse(['error' => 'Utilisateur introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse(['error' => 'Données invalides'], 400);
        }

        try {
            if (!empty($data['name'])) $profil->setName($data['name']);
            if (!empty($data['lastName'])) $profil->setLastName($data['lastName']);
            if (!empty($data['tel'])) $profil->setTel($data['tel']);
            if (!empty($data['sexe'])) $profil->setSexe($data['sexe']);
            if (!empty($data['role'])) $profil->setRole($data['role']);
            if (!empty($data['dateNaissance'])) {
                $profil->setDateNaissance(new \DateTime($data['dateNaissance']));
            }

            $em->flush();
            return new JsonResponse(['success' => true, 'message' => 'Utilisateur mis à jour avec succès.']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    // ─── API: Reset user password (JSON) ───
    #[Route('/users/{id}/reset-password', name: 'admin_user_reset_password', methods: ['POST'])]
    public function resetPassword(int $id, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): JsonResponse
    {
        $profil = $em->getRepository(Profil::class)->find($id);
        if (!$profil) {
            return new JsonResponse(['error' => 'Utilisateur introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $password = $data['password'] ?? null;

        if (!$password || strlen($password) < 6) {
            return new JsonResponse(['error' => 'Le mot de passe doit contenir au moins 6 caractères.'], 400);
        }

        $profil->setPassword($hasher->hashPassword($profil, $password));
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Mot de passe réinitialisé avec succès.']);
    }
}