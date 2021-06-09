<?php


namespace App\Controller;

use App\Model\ModelDTO;
use App\Repository\ModelRepository;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

/**
 * @Route("/api/v1/model")
 */
class ModelController
{
    /**
     * @OA\Get (
     *     path="/api/v1/model/distinct",
     *     tags={"Model"},
     *     summary="Получить названия имеющихся моделей из системы",
     *     description="Получить названия имеющихся моделей из системы",
     *     @OA\Response(
     *          response="200",
     *          description="Успешная операция",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(
     *                  @OA\Property(
     *                      property="name",
     *                      type="string",
     *                  ),
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *          response="401",
     *          description="JWT Token не найден",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="code",
     *                  type="string",
     *                  example="401"
     *              ),
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="JWT Token не найден"
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *          response="404",
     *          description="Модели не найдены",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="code",
     *                  type="string",
     *                  example="404"
     *              ),
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="Модели не найдены"
     *              )
     *          )
     *     )
     * )
     * @Route("/distinct", name="model_index", methods={"GET"})
     */
    public function getAllModel(SerializerInterface $serializer, ModelRepository $modelRepository)
    {
        try {
            $models = $modelRepository->findDistinctModel();
            if (!$models) {
                throw new \Exception('Модели не найдены', Response::HTTP_NOT_FOUND);
            }
            $data = [];
            foreach ($models as $model) {
                $modelDto = new ModelDTO();
                $modelDto->setName($model['name']);
                $data[]= $modelDto;
            }
        } catch (\Exception $e) {
            $data = [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
            $response = new Response();
            $response->setStatusCode($e->getCode());
            $response->setContent($serializer->serialize($data, 'json'));
            $response->headers->add(['Content-Type' => 'application/json']);
            return $response;
        }
        $response = new Response();
        $response->setStatusCode(Response::HTTP_OK);
        $response->setContent($serializer->serialize($data, 'json'));
        $response->headers->add(['Content-Type' => 'application/json']);
        return $response;
    }
}
