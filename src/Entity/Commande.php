<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
#[ORM\HasLifecycleCallbacks] 
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $reference = null;

    #[ORM\Column(name: "prix_total")]
    private ?float $prix_total = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(name: "date_creation", type: "datetime_immutable")]
    private ?\DateTimeImmutable $dateCreation = null;

    /**
     * @var Collection<int, DetailsCommande>
     */
    #[ORM\OneToMany(targetEntity: DetailsCommande::class, mappedBy: 'commande', orphanRemoval: true)]
    private Collection $details_commande;

    #[ORM\ManyToOne(inversedBy: 'commandes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $utilisateur = null;

    public function __construct()
    {
        $this->details_commande = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getPrixTotal(): ?float
    {
        return $this->prix_total;
    }

    public function setPrixTotal(float $prix_total): static
    {
        $this->prix_total = $prix_total;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    #[ORM\PrePersist] // Code exécuter automatiquement avant un INSERT en SQL
    public function setDateCreation(): void
    {
        $this->dateCreation = new \DateTimeImmutable();
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->dateCreation;
    }

    /**
     * @return Collection<int, DetailsCommande>
     */
    public function getDetailsCommande(): Collection
    {
        return $this->details_commande;
    }

    public function addDetailsCommande(DetailsCommande $detailsCommande): static
    {
        if (!$this->details_commande->contains($detailsCommande)) {
            $this->details_commande->add($detailsCommande);
            $detailsCommande->setCommande($this);
        }

        return $this;
    }

    public function removeDetailsCommande(DetailsCommande $detailsCommande): static
    {
        if ($this->details_commande->removeElement($detailsCommande)) {
            // set the owning side to null (unless already changed)
            if ($detailsCommande->getCommande() === $this) {
                $detailsCommande->setCommande(null);
            }
        }

        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }
}