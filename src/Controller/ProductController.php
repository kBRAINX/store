<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class ProductController extends AbstractController
{
    #[IsGranted('ROLE_USER', message: 'Access denied.', statusCode: Response::HTTP_FORBIDDEN)]
    #[Route('/api/products', methods: ['GET'])]
    public function getAll(ProductRepository $repository): JsonResponse
    {
        try {
            $products = $repository->findAll();
            return $this->json($products, Response::HTTP_OK, [], [
                'groups' => 'product.show'
            ]);
        }catch (JsonException $e){
            return $this->json($e);
        }
    }

    #[IsGranted('ROLE_USER', message: 'Access denied.', statusCode: Response::HTTP_FORBIDDEN)]
    #[Route('/api/products/{id}', methods: ['GET'])]
    public function findOne(Product $product): JsonResponse
    {
        try {
            return $this->json($product, Response::HTTP_OK, [], [
                'groups' => 'product.show'
            ]);
        }catch (JsonException $e){
            return $this->json($e);
        }
    }

    #[IsGranted('ROLE_SUPER_ADMIN', message: 'Access denied.', statusCode: Response::HTTP_FORBIDDEN)]
    #[Route('/api/product', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        try {
            $object = new Product();
            $object->setCreatedAt(new \DateTimeImmutable());
            $object->setUpdatedAt(new \DateTimeImmutable());
            $data = $request->getContent();
            $dataArray = json_decode($data, true);

            // Deserializer of the data
            $product = $serializer->deserialize($data, Product::class, 'json', [
                AbstractNormalizer::OBJECT_TO_POPULATE => $object
            ]);

            // retrieve category ID from request data
            if (isset($dataArray['category']['id'])) {
                $category = $em->getRepository(Category::class)->find($dataArray['category']['id']);
                if (!$category) {
                    return $this->json(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
                }
                $product->setCategory($category);
            } else {
                return $this->json(['error' => 'Category ID is required'], Response::HTTP_BAD_REQUEST);
            }

            $em->persist($product);
            $em->flush();
            return $this->json($product, Response::HTTP_CREATED, [], [
                'groups' => ['product.create']
            ]);
        } catch (JsonException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[IsGranted('ROLE_EDIT', message: 'Access denied.', statusCode: Response::HTTP_FORBIDDEN)]
    #[Route('/api/products/{id}', methods: ['PATCH'])]
    public function update(Product $product, EntityManagerInterface $em, SerializerInterface $serializer, Request $request): JsonResponse
    {
        try {
            $product->setUpdatedAt(new \DateTimeImmutable());
            $data = $request->getContent();

            // Deserializer of the product without the category
            $updatedProduct = $serializer->deserialize($data, Product::class, 'json', [
                AbstractNormalizer::OBJECT_TO_POPULATE => $product,
                AbstractNormalizer::IGNORED_ATTRIBUTES => ['category']
            ]);

            $em->flush();
            return $this->json($updatedProduct, Response::HTTP_OK, [], [
                'groups' => ['product.create']
            ]);
        } catch (JsonException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[IsGranted('ROLE_GRANT_EDIT', message: 'Access denied.', statusCode: Response::HTTP_FORBIDDEN)]
    #[Route('/api/products/{id}', methods: ['PATCH'])]
    public function updateWithCategory(Request $request, EntityManagerInterface $em, SerializerInterface $serializer, Product $product): JsonResponse
    {
        $product->setUpdatedAt(new \DateTimeImmutable());
        $data = $request->getContent();

        // Deserializer of the data with the category
        $serializer->deserialize($data, Product::class, 'json', [
            AbstractNormalizer::OBJECT_TO_POPULATE => $product
        ]);

        // retrieve category ID from request data
        if (isset($data['category']['id'])) {
            $category = $em->getRepository(Category::class)->find($data['category']['id']);
            if (!$category) {
                return $this->json(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
            }
            $product->setCategory($category);
        }else{
            return $this->json(['error' => 'Category ID is required'], Response::HTTP_BAD_REQUEST);
        }

        $em->flush();
        return $this->json($product, Response::HTTP_OK, [], [
            'groups' => ['product.create']
        ]);

    }

    #[IsGranted('ROLE_SUPER_ADMIN', message: 'Access denied.', statusCode: Response::HTTP_FORBIDDEN)]
    #[Route('/api/products/{id}', methods: ['DELETE'])]
    public function delete(Product $product, EntityManagerInterface $em): JsonResponse
    {
        try {
            $em->remove($product);
            $em->flush();
            return $this->json(null, Response::HTTP_NO_CONTENT);
        }catch (JsonException $e){
            return $this->json($e);
        }
    }
}
