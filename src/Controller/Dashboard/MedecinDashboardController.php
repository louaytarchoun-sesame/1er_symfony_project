<?php
namespace App\Controller\Dashboard;

use App\Entity\Medecin;
use App\Form\MedecinType;
use App\Repository\MedecinRepository;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

#[Route('/dashboard/medecin')]
class MedecinDashboardController extends AbstractController
{
    private string $jwtSecret;

    // On injecte le secret depuis .env
    public function __construct(string $jwtSecret)
    {
        $this->jwtSecret = $jwtSecret;
    }

    private function authenticateJWT(Request $request): ?array
    {
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            throw $this->createAccessDeniedException('Token JWT manquant');
        }

        $token = substr($authHeader, 7);

        try {
            $payload = (array) JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
        } catch (\Exception $e) {
            throw $this->createAccessDeniedException('Token JWT invalide ou expiré');
        }

        // Vérifier que le rôle est bien "medecin"
        if (strtolower($payload['role']) !== 'medecin' && strtolower($payload['role']) !== 'médecin') {
            throw $this->createAccessDeniedException('Accès refusé pour ce rôle');
        }

        return $payload;
    }

    #[Route('', name: 'medecin_dashboard', methods: ['GET'])]
    public function index(Security $security, MedecinRepository $medecinRepository): Response
{
    $user = $security->getUser(); // l'utilisateur authentifié via JwtAuthenticator


    $profilId = $user->getId();
    $medecin = $medecinRepository->findOneBy(['profile' => $profilId]);

    if (!$medecin) {
        throw $this->createNotFoundException('Médecin introuvable pour ce profil.');
    }

    return $this->render('dashboard/medecin/home/medecin.home.html.twig', [
        'medecin' => $medecin,
        'user_payload' => $user, // ou converti en array si nécessaire
    ]);
}

    #[Route('/new', name: 'medecin_dashboard_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $medecin = new Medecin();
        $form = $this->createForm(MedecinType::class, $medecin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($medecin->getProfile());
            $entityManager->persist($medecin);
            $entityManager->flush();

            $this->addFlash('success', 'Médecin et profil créés avec succès !');
            return $this->redirectToRoute('medecin_dashboard');
        }

        return $this->render('dashboard/medecin/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/profil', name: 'medecin_dashboard_profil', methods: ['GET'])]
    public function profil(Medecin $medecin): Response
    {
        return $this->render('dashboard/medecin/profil/show.html.twig', [
            'profil' => $medecin->getProfile(),
        ]);
    }

    #[Route('/{id}', name: 'medecin_dashboard_show', methods: ['GET'])]
    public function show(Medecin $medecin): Response
    {
        return $this->render('dashboard/medecin/show.html.twig', [
            'medecin' => $medecin,
        ]);
    }

    #[Route('/{id}/edit', name: 'medecin_dashboard_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Medecin $medecin, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MedecinType::class, $medecin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('medecin_dashboard');
        }

        return $this->render('dashboard/medecin/profil/edit.html.twig', [
            'form' => $form->createView(),
            'medecin' => $medecin,
        ]);
    }

    #[Route('/{id}/delete', name: 'medecin_dashboard_delete', methods: ['POST'])]
    public function delete(Request $request, Medecin $medecin, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$medecin->getId(), $request->request->get('_token'))) {
            $entityManager->remove($medecin);
            $entityManager->flush();
        }
        return $this->redirectToRoute('medecin_dashboard');
    }
}