<?php
namespace App\Controller;

use App\Entity\Patient;
use App\Form\PatientType;
use App\Repository\PatientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PatientController extends AbstractController
{
    #[Route('/patient', name: 'patient_index', methods: ['GET'])]
    public function index(PatientRepository $patientRepository): Response
    {
        return $this->render('patient/index.html.twig', [
            'patients' => $patientRepository->findAll(),
        ]);
    }

    #[Route('/patient/new', name: 'patient_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $patient = new Patient();
        $form = $this->createForm(PatientType::class, $patient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Création automatique du profil
            $profil = new \App\Entity\Profil();
            $profil->setName($request->get('name'));
            $profil->setLastName($request->get('last_name'));
            $profil->setCin($request->get('cin'));
            $profil->setRole('patient');
            $profil->setImage($request->get('image'));
            $profil->setTel($request->get('tel'));
            $profil->setSexe($request->get('sexe'));
            $entityManager->persist($profil);
            $patient->setProfile($profil);
            $entityManager->persist($patient);
            $entityManager->flush();
            return $this->redirectToRoute('patient_index');
        }

        return $this->render('patient/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/patient/{id}/profil', name: 'patient_profil', methods: ['GET'])]
    public function profil(Patient $patient): Response
    {
        return $this->render('profil/show.html.twig', [
            'profil' => $patient->getProfile(),
        ]);
    }

    #[Route('/patient/{id}', name: 'patient_show', methods: ['GET'])]
    public function show(Patient $patient): Response
    {
        return $this->render('patient/show.html.twig', [
            'patient' => $patient,
        ]);
    }

    #[Route('/patient/{id}/edit', name: 'patient_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Patient $patient, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PatientType::class, $patient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('patient_index');
        }

        return $this->render('patient/edit.html.twig', [
            'form' => $form->createView(),
            'patient' => $patient,
        ]);
    }

    #[Route('/patient/{id}', name: 'patient_delete', methods: ['POST'])]
    public function delete(Request $request, Patient $patient, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$patient->getId(), $request->request->get('_token'))) {
            $entityManager->remove($patient);
            $entityManager->flush();
        }
        return $this->redirectToRoute('patient_index');
    }
}
