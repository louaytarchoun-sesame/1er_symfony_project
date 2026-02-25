<?php
namespace App\Entity;



use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: "medecin", indexes: [new ORM\Index(name: "fk_specialite", columns: ["specialite_id"])])]
#[ORM\Entity]
class Medecin
{
    #[ORM\Id]
    #[ORM\Column(name: "id", type: "integer", nullable: false)]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    private $id;

    #[ORM\Column(name: "date_embauche", type: "date", nullable: true)]
    private $dateEmbauche;

    #[ORM\ManyToOne(targetEntity: Profil::class, inversedBy: null)]
    #[ORM\JoinColumn(name: "profile_id", referencedColumnName: "id", nullable: true, onDelete: "SET NULL")]
    private ?Profil $profile = null;

    #[ORM\ManyToOne(targetEntity: Specialite::class)]
    #[ORM\JoinColumn(name: "specialite_id", referencedColumnName: "id")]
    private $specialite;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateEmbauche(): ?\DateTimeInterface
    {
        return $this->dateEmbauche;
    }

    public function setDateEmbauche(?\DateTimeInterface $dateEmbauche): self
    {
        $this->dateEmbauche = $dateEmbauche;
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
    return $this->profile ? $this->profile->getNom() : null;
}

public function setName(string $name): self
{
    if ($this->profile) {
        $this->profile->setNom($name);
    }
    return $this;
}

public function getLastName(): ?string
{
    return $this->profile ? $this->profile->getPrenom() : null;
}

public function setLastName(string $lastName): self
{
    if ($this->profile) {
        $this->profile->setPrenom($lastName);
    }
    return $this;
}

    public function getSpecialite()
    {
        return $this->specialite;
    }

    public function setSpecialite($specialite): self
    {
        $this->specialite = $specialite;
        return $this;
    }
}

    public function getId()
    {
        return $this->id;
    }

    public function getDateEmbauche(): ?\DateTimeInterface
    {
        return $this->dateEmbauche;
    }

    public function setDateEmbauche(?\DateTimeInterface $date): self
    {
        $this->dateEmbauche = $date;

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

    public function getSpecialite(): ?Specialite
    {
        return $this->specialite;
    }

    public function setSpecialite(?Specialite $specialite): self
    {
        $this->specialite = $specialite;

        return $this;
    }

}

