<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: "patient")]
#[ORM\Entity]
class Patient
{
    #[ORM\Id]
    #[ORM\Column(name: "id", type: "integer", nullable: false)]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    private $id;

    #[ORM\Column(name: "date_inscription", type: "date", nullable: true)]
    private $dateInscription;

    #[ORM\ManyToOne(targetEntity: Profil::class, inversedBy: null)]
    #[ORM\JoinColumn(name: "profile_id", referencedColumnName: "id", nullable: true, onDelete: "SET NULL")]
    private ?Profil $profile = null;


    public function getId()
    {
        return $this->id;
    }

    public function getDateInscription(): ?\DateTimeInterface
    {
        return $this->dateInscription;
    }

    public function setDateInscription(?\DateTimeInterface $date): self
    {
        $this->dateInscription = $date;

        return $this;
    }

    public function getProfile(): ?Profil
    {
        return $this->profile;
    }

    public function setProfile(?Profil $profile): self
    {
        $this->profile = $profile;

        return $this;
    }

}