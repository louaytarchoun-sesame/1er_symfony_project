<?php

namespace App\Controller\Dashboard;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dashboard/admin')]
class AdminDashboardController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function index()
    {
        return $this->render('dashboard/admin/dashboard.admin.html.twig');
    }

    #[Route('/users', name: 'admin_users')]
    public function users()
    {
        return $this->render('dashboard/admin/users.html.twig');
    }
}