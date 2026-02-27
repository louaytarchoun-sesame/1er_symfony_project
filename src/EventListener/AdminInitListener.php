<?php

namespace App\EventListener;

use App\Entity\Profil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class AdminInitListener
{
    private static bool $checked = false;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || self::$checked) {
            return;
        }

        self::$checked = true;

        $repo = $this->em->getRepository(Profil::class);
        $existing = $repo->findOneBy(['role' => 'ADMIN']);

        if (!$existing) {
            $admin = new Profil();
            $admin->setCin('45645645');
            $admin->setName('Admin');
            $admin->setLastName('User');
            $admin->setRole('ADMIN');
            $admin->setTel('00000000');
            $admin->setSexe('M');
            $admin->setPassword(password_hash('admin', PASSWORD_BCRYPT));

            $this->em->persist($admin);
            $this->em->flush();
        }
    }
}
