<?php

namespace App\Controller\Dashboard;

use App\Entity\Medecin;
use App\Entity\Patient;
use App\Entity\Profil;
use App\Entity\RendezVous;
use App\Entity\Specialite;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
}