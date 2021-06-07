<?php


namespace App\Controller;

use App\Model\UserDTO;
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
}
