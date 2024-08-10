<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;

#[OA\Tag(name:"Users", description: "Routes about Users")]
class UserController extends AbstractController
{
    public function __construct(private readonly UserPasswordHasherInterface $hasher){
    }

    #[IsGranted('ROLE_SUPER_ADMIN', message: 'Access denied.', statusCode: Response::HTTP_FORBIDDEN)]
    #[Route('/api/users', methods: ['GET'])]
    #[OA\Get(
        path: '/api/users',
        description: 'Retrieve a list of all users.',
        summary: 'List all users',
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of users',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: User::class))
                )
            ),
            new OA\Response(
                response: 404,
                description: 'No users found'
            )
        ]
    )]
    public function index(EntityManagerInterface $em): JsonResponse
    {
        $users = $em->getRepository(User::class)->findAll();
        return $this->json($users, Response::HTTP_OK);
    }

    #[Route('/api/users/{id}', methods: ['GET'])]
    #[OA\Get(
        path: '/api/users/{id}',
        description: 'Retrieve a specific user.',
        summary: 'Retrieve a user',
        responses: [
            new OA\Response(
                response: 200,
                description: 'User found',
                content: new OA\JsonContent(ref: new Model(type: User::class))
            ),
            new OA\Response(
                response: 404,
                description: 'User not found'
            )
        ]
    )]
    public function show(User $user): JsonResponse
    {
        return $this->json($user, Response::HTTP_OK);
    }

    #[Route('/api/register', methods: ['POST'])]
    #[OA\Post(
        path: '/api/register',
        description: 'Register a new user.',
        summary: 'Register a user',
        requestBody: new OA\RequestBody(
            description: 'User registration data',
            content: new OA\JsonContent(ref: new Model(type: User::class))
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User successfully registered',
                content: new OA\JsonContent(ref: new Model(type: User::class))
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request'
            )
        ]
    )]
    public function register(Request $request, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $user = new User();
        $data = $request->getContent();
        $user = $serializer->deserialize($data, User::class, 'json', [
            AbstractNormalizer::OBJECT_TO_POPULATE => $user
        ]);
        $user->setPassword($this->hasher->hashPassword($user, $user->getPassword()));
        $em->persist($user);
        $em->flush();
        return $this->json($user, Response::HTTP_CREATED);
    }

    #[Route('/api/login', methods: ['POST'])]
    public function login(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = $request->getContent();
        $user = $em->getRepository(User::class)->findByUsernameOrByEmail(json_decode($data)->username || json_decode($data)->email);
        if ($user && $this->hasher->isPasswordValid($user, json_decode($data)->password, $user->getPassword())) {
            return $this->json($user, Response::HTTP_OK);
        }
        return $this->json(['message' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
    }

    #[Route('/api/user/{id}/update', methods: ['PATCH'])]
    #[IsGranted('ROLE_USER', message: 'Access denied.', statusCode: Response::HTTP_FORBIDDEN)]
    #[OA\Patch(
        path: '/api/user/{id}/update',
        description: 'Update an existing user.',
        summary: 'Update a user',
        requestBody: new OA\RequestBody(
            description: 'Updated user data',
            content: new OA\JsonContent(ref: new Model(type: User::class))
        ),
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Id of the user to update',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User updated successfully',
                content: new OA\JsonContent(ref: new Model(type: User::class, groups: ['user.update']))
            ),
            new OA\Response(
                response: 404,
                description: 'User not found'
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden for admin to edit another admin'
            )
        ]
    )]
    public function update(Request $request, EntityManagerInterface $em, SerializerInterface $serializer, User $user): JsonResponse
    {
        try {
            $data = $request->getContent();
            $serializer->deserialize($data, User::class, 'json', [
                AbstractNormalizer::OBJECT_TO_POPULATE => $user,
                AbstractNormalizer::IGNORED_ATTRIBUTES => ['id','password']
            ]);
            $em->flush();
            return $this->json($user, Response::HTTP_OK);
        }catch (JsonException $e){
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[IsGranted('ROLE_SUPER_ADMIN', message: 'Access denied.', statusCode: Response::HTTP_FORBIDDEN)]
    #[Route('/api/user/{id}', methods: ['PATCH'])]
    #[OA\Patch(
        path: '/api/user/{id}',
        description: 'Update a user role.',
        summary: 'Update a user role',
        requestBody: new OA\RequestBody(
            description: 'Updated a user role',
            content: new OA\JsonContent(ref: new Model(type: User::class))
        ),
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Id of the user to update role',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User role updated successfully',
                content: new OA\JsonContent(ref: new Model(type: User::class))
            ),
            new OA\Response(
                response: 404,
                description: 'User not found'
            )
        ]
    )]
    public function changeRole(Request $request, EntityManagerInterface $em, User $user): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (isset($data['role'])) {
                $newRole = $data['role'];
                $allowedRoles = ['ROLE_EDIT', 'ROLE_GRANT_EDIT'];

                if (in_array($newRole, $allowedRoles)) {
                    $userRoles = $user->getRoles();

                    // Filter out any roles that match the allowed roles
                    $userRoles = array_filter($userRoles, function($role) use ($allowedRoles) {
                        return !in_array($role, $allowedRoles);
                    });

                    $userRoles[] = $newRole;
                    $user->setRoles(array_unique($userRoles));
                } else {
                    return $this->json(['error' => 'Invalid role'], Response::HTTP_BAD_REQUEST);
                }

                $em->flush();
                return $this->json($user, Response::HTTP_OK);
            } else {
                return $this->json(['error' => 'Role is required'], Response::HTTP_BAD_REQUEST);
            }

        } catch (JsonException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/user/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_SUPER_ADMIN', message: 'Access denied.', statusCode: Response::HTTP_FORBIDDEN)]
    #[OA\Delete(
        path: '/api/user/{id}',
        description: 'Delete a user by its ID.',
        summary: 'Delete a user',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID of the user to delete',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'User deleted'
            ),
            new OA\Response(
                response: 404,
                description: 'User not found'
            )
        ]
    )]
    public function delete(EntityManagerInterface $em, User $user): JsonResponse
    {
        $em->remove($user);
        $em->flush();
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
