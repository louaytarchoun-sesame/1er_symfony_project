<?php

namespace App\Controller\Dashboard;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dashboard/patient')]
class PatientDashboardController extends AbstractController
{
    #[Route('/', name: 'patient_dashboard')]
    public function index()
    {
        return $this->render('dashboard/patient/dashboard.patient.html.twig');
    }
}