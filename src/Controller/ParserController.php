<?php

namespace App\Controller;

use App\Entity\Model;
use App\Entity\ObjectInQuestion;
use App\Entity\Report;
use App\Entity\Resource;
use App\Entity\User;
use App\Exception\PythonServiceMLUnavaibleException;
use App\Model\ItemDTO;
use App\Model\ModelDTO;
use App\Model\ReviewDTO;
use App\Parser\Parser;
use App\Repository\ModelRepository;
use App\Repository\ReportRepository;
use App\Repository\UserRepository;
use App\Service\PythonServiceML;
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
    private $pythonServiceMl;

    public function __construct(PythonServiceML $pythonServiceML)
    {
        $this->pythonServiceMl = $pythonServiceML;
    }

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
     *              @OA\Property(
     *                  property="model",
     *                  type="object",
     *                  @OA\Property(
     *                      property="name",
     *                      type="string",
     *                      example="BagOfWords"
     *                  ),
     *                  @OA\Property(
     *                      property="dataSet",
     *                      type="string",
     *                      example="womenShop"
     *                  ),
     *                  @OA\Property(
     *                      property="classificator",
     *                      type="string",
     *                      example="MultinomialNB"
     *                  ),
     *              ),
     *          )
     *     ),
     *     @OA\Response(
     *          response="201",
     *          description="Операция успешно проведена",
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
     *                     type="boolean",
     *                     example="true"
     *                 ),
     *                 @OA\Property(
     *                     property="id_report",
     *                     type="integer",
     *                     example=1
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
     *          description="Товар / отзыв не найден",
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
    public function mvideo(
        Request $request,
        SerializerInterface $serializer,
        UserRepository $userRepository,
        ModelRepository $modelRepository,
        ReportRepository $reportRepository
    ): Response {
        try {
            // ссылка на продукт
            $url = json_decode($request->getContent(), true)['url'];
            $modelDto = json_decode($request->getContent(), true)['model'];
            if (!$url) {
                throw new \Exception('Некорректная ссылка', Response::HTTP_BAD_REQUEST);
            }
            if (!$modelDto) {
                throw new \Exception('Некорректная модель', Response::HTTP_BAD_REQUEST);
            }
            // Проверяем модель на всякий случай и получаем её из БД
            $model = $this->checkModel($modelDto, $modelRepository);
            // Проверяем что ссылка действительно на м-видео
            $checkUrl = explode('/', $url);
            if ('www.mvideo.ru' !== $checkUrl[2] || 'products' !== $checkUrl[3]) {
                throw new \Exception('Некорректная ссылка', Response::HTTP_BAD_REQUEST);
            }
            // Вызываем метод парсинга страницы
            $html = $this->generateHtml(
                $url,
                null,
                15,
                15,
                null,
                [
                    'file' => 'cookie.txt', // string Файл для хранения cookie
                    'session' => true, // bool Для указания текущему сеансу начать новую "сессию" cookies
                ]
            );
            //_______________________Работаем с документом для получения заголовков, кол-во отзывов и т.д._____________
            // Создаем документ
            $mainDocument = PhpQuery::newDocument('<meta charset="utf-8">'.$html);
            // Находим заголовок h1 с названием продукта
            $entry = $mainDocument->find('h1');
            $mvideoData['title'] = PhpQuery::pq($entry)->text();
            $mvideoData['title'] = preg_replace('/\s+/', ' ', $mvideoData['title']);
            if ('Cтраница не найдена.' === $mvideoData['title']) {
                throw new \Exception('Страница с данным продуктом не найдена', Response::HTTP_BAD_REQUEST);
            }
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
            // Выгрузим url картинки
            $mvideoData['image'] = 'https://img.mvideo.ru/Big/'.$idProduct.'bb.jpg';
            // Ссылка на страницу с отзывами
            $urlReviews = 'https://www.mvideo.ru/sitebuilder/blocks/browse/product-detail/tabs/product-reviews.jsp?'.
                'productId='.$idProduct.'&howMany='.$countReviews.'&sortBy=dateDesc&page=1';

            //_______________________Выгружаем отзывы________________________________
            phpQuery::unloadDocuments();
            // Парсим страницу c отзывами
            $reviewsHtml = $this->generateHtml(
                $urlReviews,
                'Mozilla/5.0 (X10; Ubuntu; Linux x86_64; rv:88.0)',
                20,
                20,
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
            /** @var ReviewDTO[] $reviews */
            $reviews = [];
            // цикл по отзывам
            foreach ($allReviews as $reviewSelectors) {
                $review = new ReviewDTO();
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
                $text = trim($plusMinusDescription_p->text(), "\ \t\n\r\0\x0B");
                $review->setPluses($text ?: null);
                // плюсы
                $plusMinusDescription = PhpQuery::pq($plusMinusDescription)->next();
                $plusMinusDescription_p = PhpQuery::pq($plusMinusDescription)->find('p');
                $text = trim($plusMinusDescription_p->text(), "\ \t\n\r\0\x0B");
                $review->setMinuses($text ?: null);
                // минусы
                $plusMinusDescription = PhpQuery::pq($plusMinusDescription)->next();
                $plusMinusDescription_p = PhpQuery::pq($plusMinusDescription)->find('p');
                $text = trim($plusMinusDescription_p->text(), "\ \t\n\r\0\x0B");
                $review->setDescription($text ?: null);
                // отзыв
                $reviews[] = $review;
            }
            // сериализуем в json
            $data = $serializer->serialize($reviews, 'json');
            //_____________________Запрос в серивис с определением тональности; на выходе json_______________________
            $data = $this->pythonServiceMl->predictTonality(
                $data,
                $model->getPath(),
                $model->getDataSet(),
                $model->getClassificator()
            );
            // заполняем всей имеющейся информацией Итем с продуктом
            $item = new ItemDTO();
            $item->setTitle($mvideoData['title']);
            $item->setCountReviews($countReviews);
            $item->setReviews($data);
            $item->setImage($mvideoData['image']);
            $item->setAccuracy($this->accuracyOfTonalityToRating($item, 'М.видео'));
            $item->setItervalRating('0 до 5');

            $modelDto = new ModelDTO();
            $modelDto->setName($model->getName());
            $modelDto->setDataSet($model->getDataSet());
            $modelDto->setClassificator($model->getClassificator());
            $modelDto->setDescription($model->getDescription());
            $item->setModel($modelDto);
            $item->setTonality($this->setTonalityItem($item));

            $itemJson = $serializer->serialize($item, 'json');

            // найдем id юзера
            $user = $this->getUser();
            $user = $userRepository->findOneBy(['email' => $user->getUsername()]);
            // сохраняем в файл
            $pathFileReviews = $this->saveJson('mvideo_'.$idProduct, $user->getId(), $itemJson);

            //____________________________Создание рассматриваемого объекта, отчета_____________________
            $report = $this->createOtherEntity(
                'М.видео',
                $url,
                $model,
                $pathFileReviews,
                $mvideoData['title'],
                $mvideoData['image']
            );
            $report = $reportRepository->find($report);
            // Код ответа 201
            $dataResponse = [
                'code' => Response::HTTP_CREATED,
                'success' => true,
                'id_report' => $report->getId(),
            ];
        } catch (PythonServiceMLUnavaibleException $e) {
            $dataResponse = [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
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

    /**
     * @Route("/prodoctorov", name="api_parser_prodoctorov", methods={"POST"})
     * @OA\Post (
     *     path="/api/v1/parser/prodoctorov",
     *     tags={"Parser"},
     *     summary="Парсер отзывов с Продокторов (отзывы о врачах)",
     *     description="Парсер отзывов с Продокторов (отзывы о врачах)",
     *     security={
     *         { "Bearer":{} },
     *     },
     *     @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="url",
     *                  type="string",
     *                  example="https://prodoctorov.ru/lipeck/vrach/330713-benammar/#otzivi"
     *              ),
     *              @OA\Property(
     *                  property="model",
     *                  type="object",
     *                  @OA\Property(
     *                      property="name",
     *                      type="string",
     *                      example="Word2Vec"
     *                  ),
     *                  @OA\Property(
     *                      property="dataSet",
     *                      type="string",
     *                      example="twitter"
     *                  ),
     *                  @OA\Property(
     *                      property="classificator",
     *                      type="string",
     *                      example="RandomForest"
     *                  ),
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
     *                     type="boolean",
     *                     example="true"
     *                 ),
     *                 @OA\Property(
     *                     property="id_report",
     *                     type="integer",
     *                     example=1
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
     *          description="Врач или отзыв по данной ссылке не найден",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="code",
     *                  type="string",
     *                  example="404"
     *              ),
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="Врач по данной ссылке не найден"
     *              )
     *          )
     *     )
     *  )
     */
    public function prodoctorov(
        Request $request,
        SerializerInterface $serializer,
        UserRepository $userRepository,
        ModelRepository $modelRepository,
        ReportRepository $reportRepository
    ): Response {
        try {
            // ссылка на врача с продокторов
            $url = json_decode($request->getContent(), true)['url'];
            $modelDto = json_decode($request->getContent(), true)['model'];
            if (!$url) {
                throw new \Exception('Некорректная ссылка', Response::HTTP_BAD_REQUEST);
            }
            if (!$modelDto) {
                throw new \Exception('Некорректная модель', Response::HTTP_BAD_REQUEST);
            }
            // Проверяем модель на всякий случай и получаем её из БД
            $model = $this->checkModel($modelDto, $modelRepository);

            // Проверяем что ссылка действительно на отзыве о враче с продокторов
            $checkUrl = explode('/', $url);
            if ('prodoctorov.ru' !== $checkUrl[2] || 'vrach' !== $checkUrl[4] || '#otzivi' !== $checkUrl[6]) {
                throw new \Exception('Некорректная ссылка', Response::HTTP_BAD_REQUEST);
            }
            // Вызываем метод парсинга страницы
            $html = $this->generateHtml(
                $url,
                null,
                15,
                15,
                null,
                [
                    'file' => 'cookie.txt', // string Файл для хранения cookie
                    'session' => true, // bool Для указания текущему сеансу начать новую "сессию" cookies
                ]
            );
            //_______________________Работаем с документом для получения заголовков, кол-во отзывов и т.д._____________
            // Создаем документ
            $mainDocument = PhpQuery::newDocument('<meta charset="utf-8">'.$html);
            // Находим заголовок h1 с названием врача
            $h1 = $mainDocument->find('h1');
            $entry = $h1->find('span[itemprop=name]');
            if ('' === $entry->text()) {
                $entry = $h1;
            }
            $prodoctorovData['title'] = PhpQuery::pq($entry)->text();
            if ('На этой странице ничего нет' === $prodoctorovData['title']) {
                throw new \Exception('Страница с введеными данным не найдена', Response::HTTP_BAD_REQUEST);
            }
            // Описание врача
            $prodoctorovData['description'] = $mainDocument->find('p.b-doctor-intro__summary')->text();
            // Картинка врача
            $prodoctorovData['image'] = $mainDocument->find('img[itemprop=image]')->attr('src');
            //_______________________Выгружаем отзывы________________________________
            // все отзывы со страницы
            $allReviews = $mainDocument->find('div[itemprop=review]');
            if (0 === count($allReviews)) {
                throw new \Exception('На данной странице нет отзывов', Response::HTTP_NOT_FOUND);
            }
            // класс содержащий все отзывы
            /** @var ReviewDTO[] $reviews */
            $reviews = [];
            // цикл по отзывам
            foreach ($allReviews as $reviewSelectors) {
                $review = new ReviewDTO();
                // дата
                $date = PhpQuery::pq($reviewSelectors)->find('div[itemprop=datePublished]');
                $review->setDate($date ? $date->attr('content') : null);

                // оценка
                $rating = PhpQuery::pq($reviewSelectors)->find('span.b-review-card__rate-num')->text();
                //избавляемся от символов - и +
                $rating = trim($rating, '+');
                $rating = trim($rating, '-');
                $review->setRating($rating ? (float) $rating : null);

                // плюсы, минусы и отзыв
                $first = PhpQuery::pq($reviewSelectors)->find('.b-review-card__comment-wrapper:first');
                if (null === $first) {
                    $review->setPluses(null);
                    $review->setMinuses(null);
                    $review->setDescription(null);
                } elseif ('Понравилось' === $first->find('.b-review-card__comment-title')->text()) {
                    $text = trim(
                        $first->find('.b-review-card__comment')->text(),
                        "\ \t\n\r\0\x0B"
                    );
                    $review->setPluses($text);
                } elseif ('Не понравилось' === $first->find('.b-review-card__comment-title')->text()) {
                    $review->setPluses(null);
                    $text = trim(
                        $first->find('.b-review-card__comment')->text(),
                        "\ \t\n\r\0\x0B"
                    );
                    $review->setMinuses($text);
                } elseif ('Комментарий' === $first->find('.b-review-card__comment-title')->text()) {
                    $review->setPluses(null);
                    $review->setMinuses(null);
                    $text = trim(
                        $first->find('.b-review-card__comment')->text(),
                        "\ \t\n\r\0\x0B"
                    );
                    $review->setDescription($text);
                }

                $second = $first->next();
                if (null === $second) {
                    $review->setMinuses(null);
                    $review->setDescription(null);
                } elseif ('Не понравилось' === $second->find('.b-review-card__comment-title')->text()) {
                    $text = trim(
                        $second->find('.b-review-card__comment')->text(),
                        "\ \t\n\r\0\x0B"
                    );
                    $review->setMinuses($text);
                } elseif ('Комментарий' === $second->find('.b-review-card__comment-title')->text()) {
                    $review->setMinuses(null);
                    $text = trim(
                        $second->find('.b-review-card__comment')->text(),
                        "\ \t\n\r\0\x0B"
                    );
                    $review->setDescription($text);
                }

                $third = $second->next();
                if (null === $third) {
                    $review->setDescription(null);
                } elseif ('Комментарий' === $third->find('.b-review-card__comment-title')->text()) {
                    $text = trim(
                        $third->find('.b-review-card__comment')->text(),
                        "\ \t\n\r\0\x0B"
                    );
                    $review->setDescription($text);
                }
                // отзыв
                $reviews[] = $review;
            }
            // сериализуем в json
            $data = $serializer->serialize($reviews, 'json');

            //_____________________Запрос в серивис с определением тональности; на выходе json_______________________
            $data = $this->pythonServiceMl->predictTonality(
                $data,
                $model->getPath(),
                $model->getDataSet(),
                $model->getClassificator()
            );
            // заполняем всей имеющейся информацией Итем с продуктом
            $item = new ItemDTO();
            $item->setTitle($prodoctorovData['title']);
            $item->setCountReviews(count($reviews));
            $item->setReviews($data);
            $item->setDescription($prodoctorovData['description']);
            $item->setImage('https://prodoctorov.ru'.$prodoctorovData['image']);
            $item->setAccuracy($this->accuracyOfTonalityToRating($item, 'Продокторов | врачи'));
            $item->setItervalRating('-2 до 2');

            $modelDto = new ModelDTO();
            $modelDto->setName($model->getName());
            $modelDto->setDataSet($model->getDataSet());
            $modelDto->setClassificator($model->getClassificator());
            $modelDto->setDescription($model->getDescription());
            $item->setModel($modelDto);
            $item->setTonality($this->setTonalityItem($item));

            $itemJson = $serializer->serialize($item, 'json');

            // id доктора
            $idDoctor = $checkUrl[5];
            // найдем id юзера
            $user = $this->getUser();
            $user = $userRepository->findOneBy(['email' => $user->getUsername()]);
            // сохраняем в файл
            $pathFileReviews = $this->saveJson('prodoctorov_'.$idDoctor, $user->getId(), $itemJson);

            //____________________________Создание рассматриваемого объекта, отчета_____________________
            $report = $this->createOtherEntity(
                'Продокторов | врачи',
                $url,
                $model,
                $pathFileReviews,
                $item->getTitle(),
                $item->getImage()
            );
            $report = $reportRepository->find($report);
            // Код ответа 201
            $dataResponse = [
                'code' => Response::HTTP_CREATED,
                'success' => true,
                'id_report' => $report->getId(),
            ];
        } catch (PythonServiceMLUnavaibleException $e) {
            $dataResponse = [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                ];
        } catch (\Exception $e) {
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

    public function accuracyOfTonalityToRating(ItemDTO $item, $nameParser)
    {
        if ('М.видео' === $nameParser) {
            // считаем рейтинг и кол-во отзывов, всё что больше или равно оценке 2.5 - позитинвые, иначе негативные
            // (0 до 5) оценка
            // сравнили совпадения рейтинга и тональности
            $countTrue = 0;
            /** @var ReviewDTO $review */
            foreach ($item->getReviews() as $review) {
                if (1 === $review->getSentiment() && $review->getRating() >= 2.5) {
                    ++$countTrue;
                } elseif (0 === $review->getSentiment() && $review->getRating() < 2.5) {
                    ++$countTrue;
                } elseif (null === $review->getSentiment()) {
                    ++$countTrue;
                }
            }
        } elseif ('Продокторов | врачи' === $nameParser) {
            // считаем рейтинг и кол-во отзывов, всё что больше или равно оценке 0 - позитинвые, иначе негативные
            // (-2 до 2) оценка
            // сравнили совпадения рейтинга и тональности
            $countTrue = 0;
            /** @var ReviewDTO $review */
            foreach ($item->getReviews() as $review) {
                if (1 === $review->getSentiment() && $review->getRating() >= 0) {
                    ++$countTrue;
                } elseif (0 === $review->getSentiment() && $review->getRating() < 0) {
                    ++$countTrue;
                } elseif (null === $review->getSentiment()) {
                    ++$countTrue;
                }
            }
        }

        // считаем точность : отношение совпавших ко всем отзывом умноженное на 100 процентов
        return round($countTrue / $item->getCountReviews() * 100, 2);
    }

    public function setTonalityItem(ItemDTO $item)
    {
        $pluses = 0;
        $minuses = 0;
        foreach ($item->getReviews() as $review) {
            if (1 === $review->getSentiment()) {
                ++$pluses;
            } elseif (0 === $review->getSentiment()) {
                ++$minuses;
            }
        }

        if ($pluses > $minuses) {
            return 'Положительные';
        }

        if ($pluses < $minuses) {
            return 'Отрицательные';
        }

        return 'Нейтральные';
    }

    public function checkModel($modelDto, ModelRepository $modelRepository): Model
    {
        try {
            /** @var Model $model */
            $model = $modelRepository->findOneBy(
                [
                    'name' => $modelDto['name'],
                    'dataSet' => $modelDto['dataSet'],
                    'classificator' => $modelDto['classificator'],
                ]
            );
            if (!$model) {
                throw new \Exception('Данной модели несуществует', Response::HTTP_NOT_FOUND);
            }
        } catch (\Exception $e) {
            throw new \Exception($e);
        }

        return $model;
    }

    public function saveJson($idProduct, $idUser, $dataJson): string
    {
        $path = './data_users/'.$idUser.'/json/'.$idProduct.'_'.
            (new \DateTime())->setTimezone(new \DateTimeZone('Europe/Moscow'))->format('Y-m-d_H-i-s').
            '.json';
        $fp = fopen($path, 'w');
        fwrite($fp, $dataJson);
        fclose($fp);

        return $path;
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

    public function createOtherEntity(
        $nameResource,
        $url,
        $model,
        $pathFileReviews,
        $title,
        $imgae
    ): Report {
        try {
            $entityManager = $this->getDoctrine()->getManager();
            // Найдём в базе наш Веб-ресурс
            $resourceRepository = $entityManager->getRepository(Resource::class);
            /** @var resource $resource */
            $resource = $resourceRepository->findOneBy(['name' => $nameResource]);
            if (!$resource) {
                throw new \Exception();
            }
            // Заполнение данными Рассматривыемый объект
            $objectInQuestion = new ObjectInQuestion();
            $objectInQuestion->setLink($url);
            $objectInQuestion->setModel($model);
            $objectInQuestion->setFileReviews($pathFileReviews);
            $objectInQuestion->setResource($resource);
            $objectInQuestion->setName($title);
            $objectInQuestion->setImage($imgae);

            $entityManager->persist($objectInQuestion);

            // Заполнение данными Отчет
            $report = new Report();
            $report->setCreatedAt((new \DateTime())->setTimezone(new \DateTimeZone('Europe/Moscow')));
            $report->setObjectInQuestion($objectInQuestion);
            /** @var User $user */
            $user = $entityManager->getRepository(User::class)->findOneBy(
                ['email' => $this->getUser()->getUsername()]
            );
            if (!$user) {
                throw new \Exception();
            }
            $report->setUserApi($user);
            // Нужно ещё сформировать отчёт
            $entityManager->persist($report);

            $entityManager->flush();
        } catch (\Exception $e) {
            throw new \Exception('Непредвиденная ошибка: '.$e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $report;
    }
}
