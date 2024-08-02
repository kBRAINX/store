<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;


class CategoryController extends AbstractController
{
    #[Route('/api/categories', methods: ['GET'])]
    public function getAll(CategoryRepository  $repository): JsonResponse
    {
        try {
            $categories = $repository->findAll();
            return $this->json($categories, Response::HTTP_OK, [], [
                'groups' => ['category.index']
            ]);
        }catch (JsonException $e){
            return $this->json($e);
        }
    }

    #[Route('/api/categories/{id}', methods: ['GET'])]
    public function findOne(Category $category): JsonResponse
    {
        try {
            return $this->json($category, Response::HTTP_OK, [], [
                'groups' => ['category.show']
            ]);
        }catch(JsonException $e){
            return $this->json($e);
        }
    }

    #[Route('/api/category', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        try {
            $object = new Category();
            $object->setCreatedAt(new \DateTimeImmutable());
            $object->setUpdatedAt(new \DateTimeImmutable());
            $data = $request->getContent();
            $category = $serializer->deserialize($data, Category::class, 'json', [
                AbstractNormalizer::OBJECT_TO_POPULATE => $object
            ]);
            $em->persist($category);
            $em->flush();
            return $this->json($category, Response::HTTP_CREATED, [], [
                'groups' => ['category.create']
            ]);
        }catch (JsonException $e){
            return $this->json($e);
        }
    }

    #[Route('/api/categories/{id}', methods: ['PATCH'])]
    public function update(Request $request, EntityManagerInterface $em, SerializerInterface $serializer, Category $category): JsonResponse
    {
        try {
            $category->setUpdatedAt(new \DateTimeImmutable());
            $data = $request->getContent();
            $serializer->deserialize($data, Category::class, 'json', [
                AbstractNormalizer::OBJECT_TO_POPULATE => $category
            ]);
            $em->flush();
            return $this->json($category, Response::HTTP_OK, [], [
                'groups' => ['category.create']
            ]);
        }catch(JsonException $e){
            return $this->json($e);
        }
    }

    #[Route('/api/category/{id}', methods: ['DELETE'])]
    public function delete(EntityManagerInterface $em, Category $category): JsonResponse
    {
        try {
            $em->remove($category);
            $em->flush();
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }catch (JsonException $e){
            return $this->json($e);
        }
    }
}
