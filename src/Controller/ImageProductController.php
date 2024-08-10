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
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;


class ImageProductController extends AbstractController
{
    #[Route('/api/products/{id}/image', methods: ['POST'])]
    #[OA\Post(
        path: '/api/products/{id}/image',
        description: 'Upload an image for the product with the given ID.',
        summary: 'Upload an image for a product',
        requestBody: new OA\RequestBody(
            description: 'Image data',
            content: new OA\JsonContent(ref: new Model(type: ImageProduct::class, groups: ['image_product.create']))
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Image successfully uploaded',
                content: new OA\JsonContent(ref: new Model(type: ImageProduct::class, groups: ['image_product.create']))
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request'
            )
        ]
    )]
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
    #[OA\Patch(
        path: '/api/products/{id}/image',
        description: 'Update the image with the given ID with the provided data.',
        summary: 'Update an existing image',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                ref: new Model(type: ImageProduct::class, groups: ['image_product.create'])
            )
        ),
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID of the image to update',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: new Model(type: ImageProduct::class, groups: ['image_product.create']))
            )
        ]
    )]
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
    #[OA\Delete(
        path: '/api/products/{id}/image',
        description: 'Delete the image with the given ID.',
        summary: 'Delete an image',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID of the image to delete',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Image deleted successfully'
            ),
            new OA\Response(
                response: 404,
                description: 'Image not found'
            )
        ]
    )]
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
