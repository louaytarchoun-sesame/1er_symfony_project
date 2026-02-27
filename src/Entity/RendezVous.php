<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: "rendez_vous", indexes: [new ORM\Index(name: "fk_patient", columns: ["patient_id"]), new ORM\Index(name: "fk_medecin", columns: ["medecin_id"])])]
#[ORM\Entity]
class RendezVous
{
    #[ORM\Id]
    #[ORM\Column(name: "id", type: "integer", nullable: false)]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    private $id;

    #[ORM\Column(name: "date", type: "datetime", nullable: false)]
    private $date;

    #[ORM\Column(name: "duree", type: "integer", nullable: true, options: ["default" => 30])]
    private $duree = 30;

    #[ORM\Column(name: "motif", type: "string", length: 255, nullable: true)]
    private $motif;

    #[ORM\Column(name: "etat", type: "string", length: 20, nullable: true)]
    private $etat;

    #[ORM\Column(name: "created_at", type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    private $createdAt = 'CURRENT_TIMESTAMP';

    #[ORM\Column(name: "updated_at", type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    private $updatedAt = 'CURRENT_TIMESTAMP';

    #[ORM\ManyToOne(targetEntity: Patient::class)]
    #[ORM\JoinColumn(name: "patient_id", referencedColumnName: "id")]
    private $patient;

    #[ORM\ManyToOne(targetEntity: Medecin::class)]
    #[ORM\JoinColumn(name: "medecin_id", referencedColumnName: "id")]
    private $medecin;

    public function getId() { return $this->id; }

    public function getDate(): ?\DateTimeInterface { return $this->date; }
    public function setDate(\DateTimeInterface $date): self { $this->date = $date; return $this; }

    public function getDuree(): ?int { return $this->duree; }
    public function setDuree(?int $duree): self { $this->duree = $duree; return $this; }

    public function getMotif(): ?string { return $this->motif; }
    public function setMotif(?string $motif): self { $this->motif = $motif; return $this; }

    public function getEtat(): ?string { return $this->etat; }
    public function setEtat(?string $etat): self { $this->etat = $etat; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt instanceof \DateTimeInterface ? $this->createdAt : null; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt instanceof \DateTimeInterface ? $this->updatedAt : null; }
    public function setUpdatedAt(\DateTimeInterface $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }

    public function getPatient(): ?Patient { return $this->patient; }
    public function setPatient(?Patient $patient): self { $this->patient = $patient; return $this; }

    public function getMedecin(): ?Medecin { return $this->medecin; }
    public function setMedecin(?Medecin $medecin): self { $this->medecin = $medecin; return $this; }
}