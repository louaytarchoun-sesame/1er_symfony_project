<?php
namespace App\Controller;

use App\Repository\PatientRepository;
use App\Repository\MedecinRepository;
use App\Repository\RendezVousRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
}
