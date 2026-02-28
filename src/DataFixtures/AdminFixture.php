<?php

namespace App\DataFixtures;

use App\Entity\Profil;
use App\Entity\Specialite;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AdminFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // ── Admin user ──
        $repo = $manager->getRepository(Profil::class);
        $existing = $repo->findOneBy(['cin' => '45645645']);

        if (!$existing) {
            $admin = new Profil();
            $admin->setCin('45645645');
            $admin->setName('Admin');
            $admin->setLastName('User');
            $admin->setRole('admin');
            $admin->setEmail('admin@medgestion.tn');
            $admin->setTel('00000000');
            $admin->setSexe('M');
            // hash password using bcrypt to match controller behavior
            $admin->setPassword(password_hash('admin', PASSWORD_BCRYPT));

            $manager->persist($admin);
            $manager->flush();
        }

        // ── Default specialités ──
        $specRepo = $manager->getRepository(Specialite::class);
        $specialites = [
            'Ophtalmologue',
            'Dermatologue',
            'Cardiologue',
            'Pédiatre',
            'Neurologue',
            'Gynécologue',
            'Orthopédiste',
            'Psychiatre',
            'Radiologue',
            'Endocrinologue',
        ];

        foreach ($specialites as $label) {
            $exists = $specRepo->findOneBy(['labelle' => $label]);
            if (!$exists) {
                $spec = new Specialite();
                $spec->setLabelle($label);
                $manager->persist($spec);
            }
        }

        $manager->flush();
    }
}