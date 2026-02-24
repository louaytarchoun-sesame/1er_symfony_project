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


}
