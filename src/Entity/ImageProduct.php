<?php

namespace App\Entity;

use App\Repository\ImageProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: ImageProductRepository::class)]
#[Vich\Uploadable]
#[UniqueEntity('filename', message: 'This filename of image already exists')]
#[Groups(['image_product.create'])]
class ImageProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['product.show'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['product.show'])]
    private ?string $filename = null;


    #[Vich\UploadableField(mapping: 'products', fileNameProperty: 'filename')]
    #[Assert\Image]
    #[Groups(['product.show'])]
    private ?File $thumbnailFile = null;

    #[ORM\ManyToOne(inversedBy: 'imageProducts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getThumbnailFile(): ?File
    {
        return $this->thumbnailFile;
    }

    public function setThumbnailFile(?File $thumbnailFile): static
    {
        $this->thumbnailFile = $thumbnailFile;
        return $this;
    }
}
