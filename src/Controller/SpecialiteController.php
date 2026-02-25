<?php
namespace App\Controller;

use App\Entity\Specialite;
use App\Form\SpecialiteType;
use App\Repository\SpecialiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SpecialiteController extends AbstractController
{
    #[Route('/specialite', name: 'specialite_index', methods: ['GET'])]
    public function index(SpecialiteRepository $specialiteRepository): Response
    {
        return $this->render('specialite/index.html.twig', [
            'specialites' => $specialiteRepository->findAll(),
        ]);
    }

    #[Route('/specialite/new', name: 'specialite_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $specialite = new Specialite();
        $form = $this->createForm(SpecialiteType::class, $specialite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($specialite);
            $entityManager->flush();
            return $this->redirectToRoute('specialite_index');
        }

        return $this->render('specialite/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/specialite/{id}', name: 'specialite_show', methods: ['GET'])]
    public function show(Specialite $specialite): Response
    {
        return $this->render('specialite/show.html.twig', [
            'specialite' => $specialite,
        ]);
    }

    #[Route('/specialite/{id}/edit', name: 'specialite_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Specialite $specialite, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SpecialiteType::class, $specialite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('specialite_index');
        }

        return $this->render('specialite/edit.html.twig', [
            'form' => $form->createView(),
            'specialite' => $specialite,
        ]);
    }

    #[Route('/specialite/{id}', name: 'specialite_delete', methods: ['POST'])]
    public function delete(Request $request, Specialite $specialite, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$specialite->getId(), $request->request->get('_token'))) {
            $entityManager->remove($specialite);
            $entityManager->flush();
        }
        return $this->redirectToRoute('specialite_index');
    }
}
