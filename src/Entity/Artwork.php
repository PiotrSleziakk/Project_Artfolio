<?php

namespace App\Entity;

use App\Repository\ArtworkRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArtworkRepository::class)]
class Artwork
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $artworkName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $artworkDescription = null;

    #[ORM\Column(length: 255)]
    private ?string $artworkImage = null;
    
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'artworks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user;
    

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArtworkName(): ?string
    {
        return $this->artworkName;
    }

    public function setArtworkName(string $artworkName): static
    {
        $this->artworkName = $artworkName;

        return $this;
    }

    public function getArtworkDescription(): ?string
    {
        return $this->artworkDescription;
    }

    public function setArtworkDescription(?string $artworkDescription): static
    {
        $this->artworkDescription = $artworkDescription;

        return $this;
    }

    public function getArtworkImage(): ?string
    {
        return $this->artworkImage;
    }

    public function setArtworkImage(string $artworkImage): static
    {
        $this->artworkImage = $artworkImage;

        return $this;
    }
    

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
