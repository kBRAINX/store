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

class UserController extends AbstractController
{
    public function __construct(private readonly UserPasswordHasherInterface $hasher){
    }

    #[Route('/api/users', methods: ['GET'])]
    public function index(EntityManagerInterface $em): JsonResponse
    {
        $users = $em->getRepository(User::class)->findAll();
        return $this->json($users, Response::HTTP_OK);
    }

    #[Route('/api/user', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
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

    public function update(Request $request, EntityManagerInterface $em, SerializerInterface $serializer, User $user): JsonResponse
    {
        try {
            $data = $request->getContent();
            $serializer->deserialize($data, User::class, 'json', [
                AbstractNormalizer::OBJECT_TO_POPULATE => $user,
                AbstractNormalizer::IGNORED_ATTRIBUTES => ['password']
            ]);
            $em->flush();
            return $this->json($user, Response::HTTP_OK);
        }catch (JsonException $e){
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route('/api/user/{id}', methods: ['PATCH'])]
    public function changeRole(Request $request, EntityManagerInterface $em, SerializerInterface $serializer, User $user): JsonResponse
    {
        try {
            $data = $request->getContent();
            $serializer->deserialize($data, User::class, 'json', [
                AbstractNormalizer::OBJECT_TO_POPULATE => $user,
                AbstractNormalizer::IGNORED_ATTRIBUTES => ['password', 'username', 'email']
            ]);
            if (isset(json_decode($data)->role)){ {
                $role = json_decode($data)->role;
                if (is_array($role)){
                    $user->setRoles([$role]);
                }else{
                    return $this->json(['error' => 'Role should be an array'], Response::HTTP_BAD_REQUEST);
                }
            }
            }else{
                return $this->json(['error' => 'Role is required'], Response::HTTP_BAD_REQUEST);
            }

            $em->flush();
            return $this->json($user, Response::HTTP_OK);

        }catch (JsonException $e){
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
