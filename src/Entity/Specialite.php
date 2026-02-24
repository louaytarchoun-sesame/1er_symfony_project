<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: "specialite")]
#[ORM\Entity]
class Specialite
{
    #[ORM\Id]
    #[ORM\Column(name: "id", type: "integer", nullable: false)]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    private $id;

    #[ORM\Column(name: "labelle", type: "string", length: 30, nullable: false)]
    private $labelle;




    public function getId()
    {
        return $this->id;
    }

    public function getLabelle(): ?string
    {
        return $this->labelle;
    }

    public function setLabelle(string $labelle): self
    {
        $this->labelle = $labelle;

        return $this;
    }

}

