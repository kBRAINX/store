<?php

namespace App\Controller;

use App\Entity\ImageProduct;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImageProductController extends AbstractController
{
    #[Route('/api/products/{id}/image', methods: ['POST'])]
    public function upload(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $product = $em->getRepository(Product::class)->find($request->attributes->get('id'));
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }
        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'File not found'], Response::HTTP_BAD_REQUEST);
        }
        $image = new ImageProduct();
        $image->setThumbnailFile($file);
        $image->setProduct($product);
        $em->persist($image);
        $em->flush();
        return $this->json($image, Response::HTTP_CREATED, [], [
            'groups' => ['image_product.create']
        ]);
    }

    #[Route('/api/products/{id}/image', methods: ['PATCH'])]
    public function update(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $product = $em->getRepository(Product::class)->find($request->attributes->get('id'));
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }
        $image = $product->getImageProducts()->first();
        if (!$image) {
            return $this->json(['error' => 'Image not found'], Response::HTTP_NOT_FOUND);
        }
        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'File not found'], Response::HTTP_BAD_REQUEST);
        }
        $image->setThumbnailFile($file);
        $em->flush();
        return $this->json($image, Response::HTTP_OK, [], [
            'groups' => ['image_product.create']
        ]);
    }

    #[Route('/api/products/{id}/image', methods: ['DELETE'])]
    public function delete(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $product = $em->getRepository(Product::class)->find($request->attributes->get('id'));
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }
        $image = $product->getImageProducts()->first();
        if (!$image) {
            return $this->json(['error' => 'Image not found'], Response::HTTP_NOT_FOUND);
        }
        $em->remove($image);
        $em->flush();
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
