<?php
namespace App\Controller\Dashboard;

use App\Entity\Medecin;
use App\Form\MedecinType;
use App\Repository\MedecinRepository;
use App\Repository\RendezVousRepository;
use App\Repository\PatientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;

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
 #[Route('', name: 'medecin_redirect', methods: ['GET'])]
    public function redirectHome(): Response
    {
        return $this->redirectToRoute('medecin_dashboard');
    }
#[Route('/home', name: 'medecin_dashboard', methods: ['GET'])]
    public function home(
        Security $security,
        MedecinRepository $medecinRepository,
        RendezVousRepository $rdvRepository
    ): Response {

        $user = $security->getUser();
        $medecin = $medecinRepository->findOneBy(['profile' => $user->getId()]);

        if (!$medecin) {
            throw $this->createNotFoundException();
        }

        // Total RDV
        $rdvCount = $rdvRepository->count(['medecin' => $medecin]);

        // RDV par statut
        $rdvByStatus = $rdvRepository->createQueryBuilder('r')
            ->select('r.etat as etat, COUNT(r.id) as total')
            ->where('r.medecin = :med')
            ->setParameter('med', $medecin)
            ->groupBy('r.etat')
            ->getQuery()
            ->getResult();

        // Patients distincts
        $patientsCount = $rdvRepository->createQueryBuilder('r')
            ->select('COUNT(DISTINCT p.id)')
            ->join('r.patient', 'p')
            ->where('r.medecin = :med')
            ->setParameter('med', $medecin)
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('dashboard/medecin/home/medecin.home.html.twig', [
            'medecin' => $medecin,
            'rdvCount' => $rdvCount,
            'patientsCount' => $patientsCount,
            'rdvByStatus' => $rdvByStatus
        ]);
    }

    #[Route('/patients', name: 'medecin_patients')]
    public function patients(
        Request $request,
        Security $security,
        MedecinRepository $medecinRepository,
        RendezVousRepository $rdvRepository
    ): Response {

        $user = $security->getUser();
        $medecin = $medecinRepository->findOneBy(['profile' => $user->getId()]);

        $cin   = $request->query->get('cin');
        $nom   = $request->query->get('nom');
        $email = $request->query->get('email');

        $qb = $rdvRepository->createQueryBuilder('r')
            ->join('r.patient', 'p')
            ->join('p.profile', 'pr')
            ->where('r.medecin = :med')
            ->setParameter('med', $medecin)
            ->groupBy('p.id');

        if ($cin) {
            $qb->andWhere('pr.cin LIKE :cin')
               ->setParameter('cin', "%$cin%");
        }

        if ($nom) {
            $qb->andWhere('pr.name LIKE :nom OR pr.last_name LIKE :nom')
               ->setParameter('nom', "%$nom%");
        }

        if ($email) {
            $qb->andWhere('pr.email LIKE :email')
               ->setParameter('email', "%$email%");
        }

        $patients = $qb->select('p.id, pr.cin, pr.name, pr.last_name, pr.email, COUNT(r.id) as nbRdv')
                       ->getQuery()
                       ->getResult();

        return $this->render('dashboard/medecin/patients.html.twig', [
            'patients' => $patients
        ]);
    }

    #[Route('/rdvs', name: 'medecin_rdvs')]
    public function rdvs(
        Request $request,
        Security $security,
        MedecinRepository $medecinRepository,
        RendezVousRepository $rdvRepository
    ): Response {

        $user = $security->getUser();
        $medecin = $medecinRepository->findOneBy(['profile' => $user->getId()]);

        $date = $request->query->get('date');
        $etat = $request->query->get('etat');

        $qb = $rdvRepository->createQueryBuilder('r')
            ->join('r.patient', 'p')
            ->addSelect('p')
            ->where('r.medecin = :med')
            ->setParameter('med', $medecin);

        if ($date) {
            $start = new \DateTime($date.' 00:00:00');
            $end   = new \DateTime($date.' 23:59:59');
            $qb->andWhere('r.date BETWEEN :start AND :end')
               ->setParameter('start', $start)
               ->setParameter('end', $end);
        }

        if ($etat) {
            $qb->andWhere('r.etat = :etat')
               ->setParameter('etat', $etat);
        }

        $rdvs = $qb->orderBy('r.date', 'DESC')->getQuery()->getResult();

        return $this->render('dashboard/medecin/rdvs.html.twig', [
            'rdvs' => $rdvs,
            'dateFilter' => $date,
            'etatFilter' => $etat
        ]);
    }

    #[Route('/rdv/{id}/status/{value}', name: 'medecin_change_status', methods: ['POST'])]
    public function changeStatus(
        RendezVous $rdv,
        int $value,
        EntityManagerInterface $em
    ): Response {
        $rdv->setEtat($value == 1 ? 'validé' : 'refusé');
        $rdv->setUpdatedAt(new \DateTime());
        $em->flush();
        return $this->redirectToRoute('medecin_rdvs');
    }


    }
