<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Medecin
 *
 * @ORM\Table(name="medecin", indexes={@ORM\Index(name="fk_specialite", columns={"specialite_id"})})
 * @ORM\Entity
 */
class Medecin
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date_embauche", type="date", nullable=true)
     */
    private $dateEmbauche;

    /**
     * @var int|null
     *
     * @ORM\Column(name="profile_id", type="integer", nullable=true)
     */
    private $profileId;

    /**
     * @var \Specialite
     *
     * @ORM\ManyToOne(targetEntity="Specialite")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="specialite_id", referencedColumnName="id")
     * })
     */
    private $specialite;


}
