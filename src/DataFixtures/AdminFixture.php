<?php

namespace App\DataFixtures;

use App\Entity\Profil;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AdminFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Récupération des variables depuis le .env, null si non définies
        $cin       = $_ENV['ADMIN_CIN'];
        $name      = $_ENV['ADMIN_NAME'] ;
        $lastName  = $_ENV['ADMIN_LASTNAME'];
        $role      = $_ENV['ADMIN_ROLE'] ;
        $email     = $_ENV['ADMIN_EMAIL'] ;
        $tel       = $_ENV['ADMIN_TEL'] ;
        $sexe      = $_ENV['ADMIN_SEXE'];
        $dateNaissance = !empty($_ENV['ADMIN_DATE_NAISSANCE']) ? new \DateTime($_ENV['ADMIN_DATE_NAISSANCE']) : null;
        $password  = $_ENV['ADMIN_PASSWORD'];

        // Vérifier si l'admin existe déjà
        $repo = $manager->getRepository(Profil::class);
        $existing = $repo->findOneBy(['cin' => $cin]);

        if (!$existing) {
            $admin = new Profil();
            $admin->setCin($cin);
            $admin->setName($name);
            $admin->setLastName($lastName);
            $admin->setRole($role);
            $admin->setEmail($email);
            $admin->setTel($tel);
            $admin->setSexe($sexe);
            $admin->setDateNaissance($dateNaissance);
            $admin->setPassword(password_hash($password, PASSWORD_BCRYPT));

            $manager->persist($admin);
            $manager->flush();
        }
    }
}