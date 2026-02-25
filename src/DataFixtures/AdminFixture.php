<?php

namespace App\DataFixtures;

use App\Entity\Profil;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AdminFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $repo = $manager->getRepository(Profil::class);
        $existing = $repo->findOneBy(['cin' => '45645645']);

        if (!$existing) {
            $admin = new Profil();
            $admin->setCin('45645645');
            $admin->setName('Admin');
            $admin->setLastName('User');
            $admin->setRole('admin');
            $admin->setTel('00000000');
            $admin->setSexe('M');
            // hash password using bcrypt to match controller behavior
            $admin->setPassword(password_hash('admin', PASSWORD_BCRYPT));

            $manager->persist($admin);
            $manager->flush();
        }
    }
}