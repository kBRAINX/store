<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;


class CategoryController extends AbstractController
{
    #[Route('/api/categories', methods: ['GET'])]
    public function index(CategoryRepository  $repository): JsonResponse
    {
        $categories = $repository->findAll();
        return $this->json($categories);
    }

    #[Route('/api/categories/{id}', methods: ['GET'])]
    public function findOne(Category $category): JsonResponse
    {
        return $this->json($category);
    }

    #[Route('/api/category', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $object = new Category();
        $object->setCreatedAt(new \DateTimeImmutable());
        $object->setUpdatedAt(new \DateTimeImmutable());
        $data = $request->getContent();
        $category = $serializer->deserialize($data, Category::class, 'json', [
            AbstractNormalizer::OBJECT_TO_POPULATE => $object
        ]);
        $em->persist($category);
        $em->flush();
        return $this->json($category, 201);
    }

    #[Route('/api/category/{id}', methods: ['PATCH'])]
    public function update(Request $request, EntityManagerInterface $em, SerializerInterface $serializer, Category $category): JsonResponse
    {
        $category->setUpdatedAt(new \DateTimeImmutable());
        $data = $request->getContent();
        $serializer->deserialize($data, Category::class, 'json', [
            AbstractNormalizer::OBJECT_TO_POPULATE => $category
        ]);
        $em->flush();
        return $this->json($category);
    }

    #[Route('/api/category/{id}', methods: ['DELETE'])]
    public function delete(EntityManagerInterface $em, Category $category): JsonResponse
    {
        $em->remove($category);
        $em->flush();
        return new JsonResponse(null, 204);
    }
}
