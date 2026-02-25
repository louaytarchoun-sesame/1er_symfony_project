<?php
namespace App\Controller;

use App\Entity\RendezVous;
use App\Form\RendezVousType;
use App\Repository\RendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RendezVousController extends AbstractController
{
    #[Route('/rendezvous', name: 'rendezvous_index', methods: ['GET'])]
    public function index(RendezVousRepository $rendezVousRepository): Response
    {
        return $this->render('rendezvous/index.html.twig', [
            'rendezvous' => $rendezVousRepository->findAll(),
        ]);
    }

    #[Route('/rendezvous/new', name: 'rendezvous_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $rendezVous = new RendezVous();
        $form = $this->createForm(RendezVousType::class, $rendezVous);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($rendezVous);
            $entityManager->flush();
            return $this->redirectToRoute('rendezvous_index');
        }

        return $this->render('rendezvous/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/rendezvous/{id}', name: 'rendezvous_show', methods: ['GET'])]
    public function show(RendezVous $rendezVous): Response
    {
        return $this->render('rendezvous/show.html.twig', [
            'rendezvous' => $rendezVous,
        ]);
    }

    #[Route('/rendezvous/{id}/edit', name: 'rendezvous_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, RendezVous $rendezVous, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RendezVousType::class, $rendezVous);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('rendezvous_index');
        }

        return $this->render('rendezvous/edit.html.twig', [
            'form' => $form->createView(),
            'rendezvous' => $rendezVous,
        ]);
    }

    #[Route('/rendezvous/{id}', name: 'rendezvous_delete', methods: ['POST'])]
    public function delete(Request $request, RendezVous $rendezVous, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$rendezVous->getId(), $request->request->get('_token'))) {
            $entityManager->remove($rendezVous);
            $entityManager->flush();
        }
        return $this->redirectToRoute('rendezvous_index');
    }
}
