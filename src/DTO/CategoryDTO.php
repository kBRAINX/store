<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CategoryDTO
{
    #[Assert\NotBlank()]
    #[Assert\Length(min:2, max: 255)]
    private ?string $name = null;

    #[Assert\Length(min:2, max: 255)]
    private string $slug;
    private ?\DateTimeImmutable $createdAt = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
    private ?\DateTimeImmutable $updatedAt = null;
}
