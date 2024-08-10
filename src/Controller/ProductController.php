<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;


#[OA\Tag(name:"Products", description: "Routes about Products")]
class ProductController extends AbstractController
{
    #[Route('/api/products', methods: ['GET'])]
    #[OA\Get(
        path: '/api/products',
        description: 'Retrieve a list of all products.',
        summary: 'List all products',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: Product::class, groups: ['product.show']))
                )
            )
        ]
    )]
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

    #[Route('/api/products/{id}', methods: ['GET'])]
    #[OA\Get(
        path: '/api/products/{id}',
        description: 'Retrieve a product by its ID.',
        summary: 'Show a product by its ID',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID of the product to retrieve',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    ref: new Model(type: Product::class, groups: ['product.show'])
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Product not found'
            )
        ]
    )]
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
    #[Route('/api/products', methods: ['POST'])]
    #[OA\Post(
        path: '/api/products',
        description: 'Create a new product ',
        summary: 'Create a new product',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                ref: new Model(type: Product::class, groups: ['product.create'])
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Product created successfully',
                content: new OA\JsonContent(
                    ref: new Model(type: Product::class, groups: ['product.create'])
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid input data'
            )
        ]
    )]
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
    #[OA\Patch(
        path: '/api/products/{id}',
        description: 'Update the product with the given ID with the provided data.',
        summary: 'Update an existing product',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                ref: new Model(type: Product::class, groups: ['product.create'])
            )
        ),
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID of the product to update',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product updated successfully',
                content: new OA\JsonContent(
                    ref: new Model(type: Product::class, groups: ['product.create'])
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Product not found'
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid input data'
            )
        ]
    )]
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
            $updatedProduct->setUpdatedAt(new \DateTimeImmutable());
            $em->flush();
            return $this->json($updatedProduct, Response::HTTP_OK, [], [
                'groups' => ['product.create']
            ]);
        } catch (JsonException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[IsGranted('ROLE_GRANT_EDIT', message: 'Access denied.', statusCode: Response::HTTP_FORBIDDEN)]
    #[Route('/api/products/{id}/category', methods: ['PATCH'])]
    #[OA\Patch(
        path: '/api/products/{id}/category',
        description: 'Update the product with category.',
        summary: 'Update an existing product with category',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                ref: new Model(type: Product::class, groups: ['product.create'])
            )
        ),
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID of the product to update',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product updated successfully',
                content: new OA\JsonContent(
                    ref: new Model(type: Product::class, groups: ['product.create'])
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Product or category not found'
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid input data'
            )
        ]
    )]
    public function updateWithCategory(Request $request, EntityManagerInterface $em, SerializerInterface $serializer, Product $product): JsonResponse
    {
        $product->setUpdatedAt(new \DateTimeImmutable());
        $data = $request->getContent();

        // Deserializer of the data with the category
        $product = $serializer->deserialize($data, Product::class, 'json', [
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
        $product->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();
        return $this->json($product, Response::HTTP_OK, [], [
            'groups' => ['product.create']
        ]);

    }

    #[IsGranted('ROLE_SUPER_ADMIN', message: 'Access denied.', statusCode: Response::HTTP_FORBIDDEN)]
    #[Route('/api/products/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/products/{id}',
        description: 'Delete the product with the given ID.',
        summary: 'Delete a product by its ID',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID of the product to delete',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Product deleted successfully'
            ),
            new OA\Response(
                response: 404,
                description: 'Product not found'
            )
        ]
    )]
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
