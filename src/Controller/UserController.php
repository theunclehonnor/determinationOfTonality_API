<?php


namespace App\Controller;

use App\Entity\Report;
use App\Entity\Resource;
use App\Model\ModelDTO;
use App\Model\ObjectInQuestionDTO;
use App\Model\ReportDTO;
use App\Model\ResourceDTO;
use App\Model\UserDTO;
use App\Repository\ModelRepository;
use App\Repository\ObjectInQuestionRepository;
use App\Repository\ReportRepository;
use App\Repository\ResourceRepository;
use App\Repository\UserRepository;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

/**
 * @Route("/api/v1/users")
 */
class UserController extends AbstractController
{
    /**
     * @OA\Get (
     *     path="/api/v1/users/profile",
     *     tags={"User"},
     *     summary="Информация о пользователе",
     *     description="Информация о пользователе",
     *     operationId="profile",
     *     @OA\Response(
     *          response="200",
     *          description="Успешная операция",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="username",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="roles",
     *                  type="array",
     *                  @OA\Items(
     *                      type="string"
     *                  )
     *              ),
     *              @OA\Property(
     *                  property="surname",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="patronymic",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="name_company",
     *                  type="string"
     *              ),
     *          )
     *     ),
     *     @OA\Response(
     *          response="401",
     *          description="Неудалось получить данные",
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
     *     )
     * )
     * @Route("/profile", name="user_profile", methods={"GET"})
     */
    public function profile(SerializerInterface $serializer, UserRepository $userRepository)
    {
        try {
            // Получаем пользователя
            $user = $this->getUser();
            if (!$user) {
                throw new \Exception('Данного пользователя не существует', Response::HTTP_NOT_FOUND);
            }
            $user = $userRepository->findOneBy(['email' => $user->getUsername()]);
            $userDto = new UserDTO();
            $userDto->setUsername($user->getUsername());
            $userDto->setName($user->getName());
            $userDto->setSurname($user->getSurname());
            $userDto->setPatronymic($user->getPatronymic());
            $userDto->setNameCompany($user->getNameCompany());
            $userDto->setRoles($user->getRoles());
            $data = $userDto;
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

    /**
     * @OA\Get (
     *     path="/api/v1/users/history",
     *     tags={"User"},
     *     summary="История пользователя",
     *     description="История пользователя",
     *     operationId="history",
     *     @OA\Response(
     *          response="200",
     *          description="Успешная операция",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(
     *                  @OA\Property(
     *                      property="report",
     *                      type="object",
     *                      @OA\Property(
     *                          property="created_at",
     *                          type="string",
     *                      ),
     *                      @OA\Property(
     *                          property="file",
     *                          type="string",
     *                      ),
     *                      @OA\Property(
     *                          property="object_in_question",
     *                          type="object",
     *                          @OA\Property(
     *                              property="link",
     *                              type="string",
     *                          ),
     *                          @OA\Property(
     *                              property="file_reviews",
     *                              type="string",
     *                          ),
     *                          @OA\Property(
     *                              property="model",
     *                              type="object",
     *                              @OA\Property(
     *                                  property="name",
     *                                  type="string",
     *                              ),
     *                              @OA\Property(
     *                                  property="data_set",
     *                                  type="string",
     *                              ),
     *                              @OA\Property(
     *                                  property="classificator",
     *                                  type="string",
     *                              )
     *                          ),
     *                          @OA\Property(
     *                              property="resource",
     *                              type="object",
     *                              @OA\Property(
     *                                  property="name",
     *                                  type="string",
     *                              ),
     *                              @OA\Property(
     *                                  property="link",
     *                                  type="string",
     *                              )
     *                          ),
     *                      )
     *                  )
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
     *          description="Отчет не найден",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="code",
     *                  type="string",
     *                  example="404"
     *              ),
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="Отчет не найден"
     *              )
     *          )
     *     )
     * )
     * @Route("/history", name="user_history", methods={"GET"})
     */
    public function history(
        SerializerInterface $serializer,
        UserRepository $userRepository,
        ReportRepository $reportRepository
    ) {
        try {
            $user = $userRepository->findOneBy(['email' => $this->getUser()->getUsername()]);
            /** @var Report[] $reports */
            $reports = $reportRepository->findBy(['userApi' => $user]);
            if (!$reports) {
                throw new \Exception('Отчет не найден', Response::HTTP_NOT_FOUND);
            }
            $reportsDto = [];
            foreach ($reports as $report) {
                $objectInQuestion = $report->getObjectInQuestion();
                if (!$objectInQuestion) {
                    throw new \Exception('Рассматриваемый объект не найден', Response::HTTP_NOT_FOUND);
                }
                $model = $objectInQuestion->getModel();
                if (!$model) {
                    throw new \Exception('Модель не найдена', Response::HTTP_NOT_FOUND);
                }
                /** @var Resource $resource */
                $resource = $objectInQuestion->getResource();
                if (!$resource) {
                    throw new \Exception('Веб-ресурс не найден', Response::HTTP_NOT_FOUND);
                }
                // modelDto
                $modelDto = new ModelDTO();
                $modelDto->setName($model->getName());
                $modelDto->setClassificator($model->getClassificator());
                $modelDto->setDataSet($model->getDataSet());
                // resourceDto
                $resourceDto = new ResourceDTO();
                $resourceDto->setName($resource->getName());
                $resourceDto->setLink($resource->getLink());
                // objectInQuestion
                $objectInQuestionDto = new ObjectInQuestionDTO();
                $objectInQuestionDto->setName($objectInQuestion->getName());
                $objectInQuestionDto->setImage($objectInQuestion->getImage());
                $objectInQuestionDto->setLink($objectInQuestion->getLink());
                $objectInQuestionDto->setModel($modelDto);
                $objectInQuestionDto->setResource($resourceDto);
                $objectInQuestionDto->setFileReviews($objectInQuestion->getFileReviews());
                // reportDto
                $reportDto = new ReportDTO();
                $reportDto->setFile($report->getFile());
                $reportDto->setCreatedAt($report->getCreatedAt()->format('Y-m-d H:i:s'));
                $reportDto->setObjectInQuestion($objectInQuestionDto);

                $reportsDto[] = $reportDto;
            }
            $data = $reportsDto;
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
