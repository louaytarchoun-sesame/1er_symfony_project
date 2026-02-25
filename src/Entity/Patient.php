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

    #[ORM\Column(name: "name", type: "string", length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(name: "last_name", type: "string", length: 255, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(name: "cin", type: "string", length: 20, nullable: true)]
    private ?string $cin = null;

    #[ORM\Column(name: "image", type: "string", length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(name: "tel", type: "string", length: 20, nullable: true)]
    private ?string $tel = null;

    #[ORM\Column(name: "sexe", type: "string", length: 10, nullable: true)]
    private ?string $sexe = null;

    #[ORM\Column(name: "date_inscription", type: "date", nullable: true)]
    private $dateInscription;

    #[ORM\ManyToOne(targetEntity: Profil::class, inversedBy: null)]
    #[ORM\JoinColumn(name: "profile_id", referencedColumnName: "id", nullable: true, onDelete: "SET NULL")]
    private ?Profil $profile = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateInscription(): ?\DateTimeInterface
    {
        return $this->dateInscription;
    }

    public function setDateInscription(?\DateTimeInterface $dateInscription): self
    {
        $this->dateInscription = $dateInscription;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getCin(): ?string
    {
        return $this->cin;
    }

    public function setCin(?string $cin): self
    {
        $this->cin = $cin;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function getTel(): ?string
    {
        return $this->tel;
    }

    public function setTel(?string $tel): self
    {
        $this->tel = $tel;
        return $this;
    }

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(?string $sexe): self
    {
        $this->sexe = $sexe;
        return $this;
    }
}
