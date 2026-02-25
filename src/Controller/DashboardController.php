<?php
namespace App\Controller;

use App\Repository\PatientRepository;
use App\Repository\MedecinRepository;
use App\Repository\RendezVousRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ProfilRepository;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function index(PatientRepository $patientRepo, MedecinRepository $medecinRepo, RendezVousRepository $rendezVousRepo): Response
    {
        $patientsCount = $patientRepo->count([]);
        $medecinsCount = $medecinRepo->count([]);
        $rendezVousCount = $rendezVousRepo->count([]);
        $rendezVousByMedecin = $rendezVousRepo->findRendezVousByMedecin();

        return $this->render('dashboard/index.html.twig', [
            'patientsCount' => $patientsCount,
            'medecinsCount' => $medecinsCount,
            'rendezVousCount' => $rendezVousCount,
            'rendezVousByMedecin' => $rendezVousByMedecin,
        ]);
    }

    #[Route('/dashboard/patient', name: 'dashboard_patient')]
    public function patientDashboard(Request $request, ProfilRepository $profilRepo, PatientRepository $patientRepo, RendezVousRepository $rendezVousRepo): Response
    {
        $profilId = $request->getSession()->get('profil_id');
        if (!$profilId) {
            return $this->redirectToRoute('app_login');
        }
        $profil = $profilRepo->find($profilId);
        $patient = $patientRepo->findOneBy(['profile' => $profil]);
        if (!$patient) {
            $this->addFlash('warning', 'Patient profile not found.');
            return $this->redirectToRoute('app_login');
        }
        $rendezvous = $rendezVousRepo->findBy(['patient' => $patient]);
        return $this->render('dashboard/patient.html.twig', [
            'patient' => $patient,
            'rendezvous' => $rendezvous,
        ]);
    }

    #[Route('/dashboard/medecin', name: 'dashboard_medecin')]
    public function medecinDashboard(Request $request, ProfilRepository $profilRepo, MedecinRepository $medecinRepo, RendezVousRepository $rendezVousRepo): Response
    {
        $profilId = $request->getSession()->get('profil_id');
        if (!$profilId) {
            return $this->redirectToRoute('app_login');
        }
        $profil = $profilRepo->find($profilId);
        $medecin = $medecinRepo->findOneBy(['profile' => $profil]);
        if (!$medecin) {
            $this->addFlash('warning', 'Medecin profile not found.');
            return $this->redirectToRoute('app_login');
        }
        $rendezvous = $rendezVousRepo->findBy(['medecin' => $medecin]);
        return $this->render('dashboard/medecin.html.twig', [
            'medecin' => $medecin,
            'rendezvous' => $rendezvous,
        ]);
    }

    #[Route('/dashboard/admin', name: 'dashboard_admin')]
    public function adminDashboard(PatientRepository $patientRepo, MedecinRepository $medecinRepo, RendezVousRepository $rendezVousRepo): Response
    {
        // reuse previous admin metrics
        $patientsCount = $patientRepo->count([]);
        $medecinsCount = $medecinRepo->count([]);
        $rendezVousCount = $rendezVousRepo->count([]);
        $rendezVousByMedecin = $rendezVousRepo->findRendezVousByMedecin();

        return $this->render('dashboard/admin.html.twig', [
            'patientsCount' => $patientsCount,
            'medecinsCount' => $medecinsCount,
            'rendezVousCount' => $rendezVousCount,
            'rendezVousByMedecin' => $rendezVousByMedecin,
        ]);
    }
}
