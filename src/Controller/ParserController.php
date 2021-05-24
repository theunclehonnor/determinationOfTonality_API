<?php

namespace App\Controller;

use App\Parser\Parser;
use App\Parser\Review;
use App\Repository\UserRepository;
use OpenApi\Annotations as OA;
use PhpQuery\PhpQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/api/v1/parser")
 */
class ParserController extends AbstractController
{
    /**
     * @Route("/mvideo", name="api_parser_mvideo", methods={"POST"})
     * @OA\Post (
     *     path="/api/v1/parser/mvideo",
     *     tags={"Parser"},
     *     summary="Парсер отзывов с М.видео",
     *     description="Парсер отзывов с М.видео",
     *     security={
     *         { "Bearer":{} },
     *     },
     *     @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="url",
     *                  type="string",
     *                  example="https://www.mvideo.ru/products/smartfon-vivo-y31-chernyi-asfalt-v2036-30054937"
     *              ),
     *          )
     *     ),
     *     @OA\Response(
     *          response="201",
     *          description="Отзывы успешно получены",
     *          @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                     example="201"
     *                 ),
     *                 @OA\Property(
     *                     property="success",
     *                     type="bool",
     *                     example="true"
     *                 ),
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Некорректная ссылка",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="code",
     *                  type="string",
     *                  example="400"
     *              ),
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="Некорректная ссылка"
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *          response="401",
     *          description="Invalid JWT token",
     *          @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                     example="401",
     *                 ),
     *                 @OA\Property(
     *                     property="message",
     *                     type="string",
     *                     example="Invalid JWT Token",
     *                 ),
     *             ),
     *          )
     *     ),
     *     @OA\Response(
     *          response="404",
     *          description="Товар или отзыв не найден",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="code",
     *                  type="string",
     *                  example="404"
     *              ),
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="Товар не найден"
     *              )
     *          )
     *     )
     *  )
     */
    public function mvideo(Request $request, SerializerInterface $serializer, UserRepository $userRepository): Response
    {
        try {
            // ссылка на продукт
            $url = json_decode($request->getContent(), true)['url'];
            // Вызываем метод парсинга страницы
            $html = $this->generateHtml(
                $url,
                'Mozilla/5.0 (X10; Ubuntu; Linux x86_64; rv:88.0)',
                150,
                150,
                null,
                [
                    'file' => 'cookie.txt', // string Файл для хранения cookie
                    'session' => false, // bool Для указания текущему сеансу начать новую "сессию" cookies
                ]
            );

            //_______________________Работаем с документом для получения заголовков, кол-во отзывов и т.д._____________
            // Создаем документ
            $mainDocument = PhpQuery::newDocument('<meta charset="utf-8">'.$html);
            // Находим заголовок h1 с названием продукта
            $entry = $mainDocument->find('h1');
            $data['h1'] = PhpQuery::pq($entry)->text();
            // Выгрузим количество отзывов
            $countReviews = $mainDocument->find('span.c-star-rating_reviews-qty:first')->text();
            // Если отзывов не найдено
            if (!$countReviews) {
                // Код ответа 404
                throw new \Exception('Отзывы не найдены', Response::HTTP_NOT_FOUND);
            }
            // Получим ID товара из ссылки
            $idProduct = explode('-', $url);
            $idProduct = $idProduct[count($idProduct) - 1];
            // Ссылка на страницу с отзывами
            $urlReviews = 'https://www.mvideo.ru/sitebuilder/blocks/browse/product-detail/tabs/product-reviews.jsp?'.
                'productId='.$idProduct.'&howMany='.$countReviews.'&sortBy=dateDesc&page=1';

            //_______________________Выгружаем отзывы________________________________
            phpQuery::unloadDocuments();
            // Парсим страницу c отзывами
            $reviewsHtml = $this->generateHtml(
                $urlReviews,
                'Mozilla/5.0 (X10; Ubuntu; Linux x86_64; rv:88.0)',
                150,
                150,
                null,
                [
                    'file' => 'cookie.txt', // string Файл для хранения cookie
                    'session' => false, // bool Для указания текущему сеансу начать новую "сессию" cookies
                ]
            );
            // Создаем документ
            $reviewsDocument = PhpQuery::newDocument('<meta charset="utf-8">'.$reviewsHtml);
            // все отзывы со страницы
            $allReviews = $reviewsDocument->find('.review-ext-wrapper');
            // класс содержащий все отзывы
            /** @var Review[] $reviews */
            $reviews = [];
            // цикл по отзывам
            foreach ($allReviews as $reviewSelectors) {
                $review = new Review();
                // автор
                $author = PhpQuery::pq($reviewSelectors)->find('span.review-ext-item-author-name');
                $review->setAuthor($author ? $author->text() : null);
                // дата
                $date = PhpQuery::pq($reviewSelectors)->find('span.review-ext-item-date');
                $review->setDate($date ? $date->attr('content') : null);
                // оценка
                $rating = PhpQuery::pq($reviewSelectors)->find('span[itemprop=ratingValue]');
                $review->setRating($rating ? (float) $rating->text() : null);

                // плюсы, минусы и отзыв
                $plusMinusDescription = PhpQuery::pq($reviewSelectors)->find('.review-ext-item-description-item:first');
                $plusMinusDescription_p = PhpQuery::pq($plusMinusDescription)->find('p');
                $review->setPluses($plusMinusDescription_p->text() ?: null);
                // плюсы
                $plusMinusDescription = PhpQuery::pq($plusMinusDescription)->next();
                $plusMinusDescription_p = PhpQuery::pq($plusMinusDescription)->find('p');
                $review->setMinuses($plusMinusDescription_p->text() ?: null);
                // минусы
                $plusMinusDescription = PhpQuery::pq($plusMinusDescription)->next();
                $plusMinusDescription_p = PhpQuery::pq($plusMinusDescription)->find('p');
                $review->setDescription($plusMinusDescription_p->text() ?: null);
                // отзыв
                $reviews[] = $review;
            }
            // сериализуем в json
            $data = $serializer->serialize($reviews, 'json');

            // найдем id юзера
            $user = $this->getUser();
            $user = $userRepository->findOneBy(['email' => $user->getUsername()]);
            // сохраняем в файл
            $this->saveJson($idProduct, $user->getId(), $data);

            // Код ответа 201
            $dataResponse = [
                'code' => Response::HTTP_CREATED,
                'success' => true,
            ];
        } catch (\Exception $e) {
            // ошибка
            $dataResponse = [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }
        $response = new Response();
        $response->setStatusCode($dataResponse['code']);
        $response->setContent($serializer->serialize($dataResponse, 'json'));
        $response->headers->add(['Content-Type' => 'application/json']);

        return $response;
    }

    public function saveJson($idProduct, $idUser, $dataJson): void
    {
        $path = './data_users/'.$idUser.'/json/'.$idProduct.'_'.date('Y-m-d_H-i-s').'.json';
        $fp = fopen($path, 'w');
        fwrite($fp, $dataJson);
        fclose($fp);
    }

    public function generateHtml(
        string $url,
        string $useragent = null,
        int $timeout = null,
        int $connectTimeout = null,
        bool $head = null,
        array $cookie = null,
        array $proxy = null,
        array $headers = null,
        string $post = null
    ) {
        // Парсим страницу
        $arHtml = Parser::getPage([
            'url' => $url, // string Ссылка на страницу
            'useragent' => $useragent, // string Содержимое заголовка "User-Agent: ", посылаемого в HTTP-запросе
            'timeout' => $timeout, // int Максимально позволенное количество секунд для выполнения CURL-функций
            'connecttimeout' => $connectTimeout, // int Количество секунд ожидания при попытке соединения
            'head' => $head, // bool Для вывода заголовков без тела документа
            'cookie' => $cookie, // array('file' - string для хранения; 'session' - bool Для указания текущему сеансу
            // начать новую "сессию" cookies'
            'proxy' => $proxy, // array('ip' - string IP адрес прокси сервера, 'port' - int Порт прокси сервера, \
            // 'type' - string Тип прокси сервера)
            'headers' => $headers, // array Массив устанавливаемых HTTP-заголовков
            'post' => $post, // string Все данные, передаваемые в HTTP POST-запросе
        ]);
        //Проверяем на ошибки
        if (false !== $arHtml['error']) {
            throw new \Exception($arHtml['error']['message'], Response::HTTP_NOT_FOUND);
        }
        // html страницу
        return $arHtml['data']['content'];
    }
}
