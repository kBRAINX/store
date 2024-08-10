<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
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


#[OA\Tag(name:"Categories", description: "Routes about Categories")]
class CategoryController extends AbstractController
{
    #[Route('/api/categories', methods: ['GET'])]
    #[OA\Get(
        path: '/api/categories',
        description: 'Retrieve a list of all categories in the system.',
        summary: 'List all categories',
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of categories',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: Category::class, groups: ['category.index']))
                )
            ),
            new OA\Response(
                response: 404,
                description: 'No categories found'
            )
        ]
    )]
    public function getAll(CategoryRepository  $repository): JsonResponse
    {
        try {
            $categories = $repository->getAllAndCountProducts();
            return $this->json($categories, Response::HTTP_OK, [], [
                'groups' => ['category.index']
            ]);
        }catch (JsonException $e){
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/categories/{id}', methods: ['GET'])]
    #[OA\Get(
        path: '/api/category/{id}',
        description: 'Retrieve a category by its ID.',
        summary: 'Get a category by ID',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID of the category to retrieve',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Category details',
                content: new OA\JsonContent(ref: new Model(type: Category::class, groups: ['category.show']))
            ),
            new OA\Response(
                response: 404,
                description: 'Category not found'
            )
        ]
    )]
    public function findOne(Category $category): JsonResponse
    {
        try {
            return $this->json($category, Response::HTTP_OK, [], [
                'groups' => ['category.show']
            ]);
        }catch(JsonException $e){
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[IsGranted('ROLE_SUPER_ADMIN', message: 'Access denied.', statusCode: Response::HTTP_FORBIDDEN)]
    #[Route('/api/categories', methods: ['POST'])]
    #[OA\Post(
        path: '/api/categories',
        description: 'Create a new category.',
        summary: 'Create a new category',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: new Model(type: Category::class, groups: ['category.create']))
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Category created',
                content: new OA\JsonContent(ref: new Model(type: Category::class, groups: ['category.create']))
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
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[IsGranted('ROLE_SUPER_ADMIN', message: 'Access denied.', statusCode: Response::HTTP_FORBIDDEN)]
    #[Route('/api/categories/{id}', methods: ['PATCH'])]
    #[OA\Patch(
        path: '/api/categories/{id}',
        description: 'Update an existing category.',
        summary: 'Update a category',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: new Model(type: Category::class, groups: ['category.create']))
        ),
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID of the category to update',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Category updated',
                content: new OA\JsonContent(ref: new Model(type: Category::class, groups: ['category.create']))
            ),
            new OA\Response(
                response: 404,
                description: 'Category not found'
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid input data'
            )
        ]
    )]
    public function update(Request $request, EntityManagerInterface $em, SerializerInterface $serializer, Category $category): JsonResponse
    {
        try {
            $category->setUpdatedAt(new \DateTimeImmutable());
            $data = $request->getContent();
            $serializer->deserialize($data, Category::class, 'json', [
                AbstractNormalizer::OBJECT_TO_POPULATE => $category,
                AbstractNormalizer::IGNORED_ATTRIBUTES => ['products']
            ]);
            $em->flush();
            return $this->json($category, Response::HTTP_OK, [], [
                'groups' => ['category.create']
            ]);
        }catch(JsonException $e){
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[IsGranted('ROLE_SUPER_ADMIN', message: 'Access denied.', statusCode: Response::HTTP_FORBIDDEN)]
    #[Route('/api/categories/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/categories/{id}',
        description: 'Delete a category by its ID.',
        summary: 'Delete a category',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID of the category to delete',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Category deleted'
            ),
            new OA\Response(
                response: 404,
                description: 'Category not found'
            )
        ]
    )]
    public function delete(EntityManagerInterface $em, Category $category): JsonResponse
    {
        try {
            $em->remove($category);
            $em->flush();
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }catch (JsonException $e){
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
