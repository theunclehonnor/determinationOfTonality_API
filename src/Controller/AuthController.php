<?php

namespace App\Controller;

use App\Entity\User;
use App\Model\UserDTO;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Service\RefreshToken;
use JMS\Serializer\SerializerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api/v1")
 */
class AuthController extends AbstractController
{
    /**
     * @OA\Post (
     *     path="/api/v1/auth",
     *     tags={"User"},
     *     summary="Автроизация пользователя",
     *     description="Автроизация пользователя",
     *     operationId="auth",
     *     @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="username",
     *                  type="string",
     *                  example="user@yandex.ru"
     *              ),
     *              @OA\Property(
     *                  property="password",
     *                  type="string",
     *                  example="user123"
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Успешная авторизация",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="token",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="refresh_token",
     *                  type="string"
     *              ),
     *          )
     *     ),
     *     @OA\Response(
     *          response="401",
     *          description="Неудалось авторизоваться",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="code",
     *                  type="string",
     *                  example="401"
     *              ),
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="Неверные учетные данные"
     *              )
     *          )
     *     )
     *  )
     *  @Route("/auth", name="api_login_check", methods={"POST"})
     */
    public function auth(): void
    {
        // get jwt token
    }

    /**
     * @OA\Post(
     *     path="/api/v1/register",
     *     tags={"User"},
     *     summary="Регистрация нового пользователя",
     *     description="Регистрация доступна только для новых пользователей",
     *     operationId="register",
     *     @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  example="user@yandex.ru"
     *              ),
     *              @OA\Property(
     *                  property="password",
     *                  type="string",
     *                  example="user123"
     *              ),
     *              @OA\Property(
     *                  property="surname",
     *                  type="string",
     *                  example="Артуров"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  example="Артур"
     *              ),
     *              @OA\Property(
     *                  property="patronymic",
     *                  type="string",
     *                  example="Артурович"
     *              ),
     *              @OA\Property(
     *                  property="nameCompany",
     *                  type="string",
     *                  example="М.видео"
     *              ),
     *          )
     *     ),
     *     @OA\Response(
     *          response="201",
     *          description="Регистрация прошла успешно",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="token",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="refresh_token",
     *                  type="string"
     *              ),
     *          )
     *     ),
     *     @OA\Response(
     *          response="500",
     *          description="Сервер не отвечает"
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Ошибка при валидации данных",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="message",
     *                  type="array",
     *                  @OA\Items(
     *                      type="string"
     *                  )
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *          response="403",
     *          description="Данный пользователь уже существует",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="message",
     *                  type="string"
     *              )
     *          )
     *     )
     * )
     * @Route("/register", name="register", methods={"POST"})
     */
    public function register(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        UserPasswordEncoderInterface $passwordEncoder,
        JWTTokenManagerInterface $JWTManager,
        RefreshTokenManagerInterface $refreshTokenManager
    ): Response {
        try {
            // Десериализация
            $userDTO = $serializer->deserialize($request->getContent(), UserDTO::class, 'json');

            // Проверяем ошибки при валидации
            $validErrors = $validator->validate($userDTO);
            if (count($validErrors)) {
                throw new \Exception($validErrors, Response::HTTP_BAD_REQUEST);
            }
            // Существует ли данный пользовательн в системе
            $entityManager = $this->getDoctrine()->getManager();
            $userRepository = $entityManager->getRepository(User::class);
            if ($userRepository->findOneBy(['email' => $userDTO->getEmail()])) {
                // Статус ответа 403, если пользователь уже существует
                throw new \Exception('Пользователь с данным email уже существует', Response::HTTP_FORBIDDEN);
            }

            // Создаем пользователя
            $user = User::fromDTO($userDTO);
            $user->setPassword($passwordEncoder->encodePassword(
                $user,
                $user->getPassword()
            ));
            $entityManager->persist($user);
            $entityManager->flush();

            // Token обновления
            $refreshToken = $refreshTokenManager->create();
            $refreshToken->setUsername($user->getEmail());
            $refreshToken->setRefreshToken();
            $refreshToken->setValid((new \DateTime())->modify('+1 month'));
            $refreshTokenManager->save($refreshToken);

            // Создадим папку пользователю, в которой он будет хранить комментарии в json
            if (!mkdir($concurrentDirectory = './data_users/'.$user->getId().'/json', 0777, true)
            && !is_dir($concurrentDirectory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
            // и отчеты
            if (!mkdir($concurrentDirectory = './data_users/'.$user->getId().'/reports', 0777, true)
            && !is_dir($concurrentDirectory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }

            $data = [
                // JWT token
                'username' => $user->getEmail(),
                'token' => $JWTManager->create($user),
                'refresh_token' => $refreshToken->getRefreshToken(),
            ];
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
        $response->setStatusCode(Response::HTTP_CREATED);
        $response->setContent($serializer->serialize($data, 'json'));
        $response->headers->add(['Content-Type' => 'application/json']);

        return $response;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/token/refresh",
     *     tags={"User"},
     *     summary="Refresh token",
     *     operationId="token.refresh",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *              @OA\Property(
     *                  property="refresh_token",
     *                  type="string"
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Обновление токена успешно завершено",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="token",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="refresh_token",
     *                  type="string"
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *          response="401",
     *          description="Не удалось обновить токен",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="code",
     *                  type="integer",
     *                  example="401"
     *              ),
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="Произошла ошибка при обновлении токена"
     *              )
     *          )
     *     )
     * )
     *
     * @Route("/token/refresh", name="refresh", methods={"POST"})
     *
     * @return mixed
     */
    public function refresh(Request $request, RefreshToken $refreshService)
    {
        return $refreshService->refresh($request);
    }
}
