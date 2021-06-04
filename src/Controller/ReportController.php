<?php

namespace App\Controller;

use App\Entity\Report;
use App\Model\ItemDTO;
use App\Repository\ObjectInQuestionRepository;
use App\Repository\ReportRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/api/v1/report")
 */
class ReportController extends AbstractController
{
    /**
     * @OA\Post (
     *     path="/api/v1/report/create",
     *     tags={"Report"},
     *     summary="Получить отчёт о тональности текстовых отзывов в pdf формате",
     *     description="Получить отчёт о тональности текстовых отзывов в pdf формате",
     *     security={
     *         { "Bearer":{} },
     *     },
     *      @OA\Parameter(
     *         name="id_report",
     *         in="query",
     *         description="ID отчета в БД",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *          response="201",
     *          description="Успешно",
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
     *          description="Отчет в системе не найден",
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
     * @Route("/create", name="report_create", methods={"POST"})
     */
    public function createPdf(
        Request $request,
        SerializerInterface $serializer,
        ReportRepository $reportRepository,
        ObjectInQuestionRepository $objectInQuestionRepository
    ) {
        try {
            $pdfOptions = new Options();
            $pdfOptions->set('defaultFont', 'DejaVu Sans');
            $pdfOptions->set('isRemoteEnabled', true);

            // Instantiate Dompdf with our options
            $dompdf = new Dompdf($pdfOptions);
            // Получим отчет
            $idReport = $request->query->get('id_report');
            $report = $reportRepository->findOneBy(['id' => $idReport]);
            if (!$report) {
                throw new \Exception('Ошибка. Нет данного отчета в системе.', Response::HTTP_NOT_FOUND);
            }
            // Получим Рассматриваемый объект
            $objectInQuestion = $objectInQuestionRepository->findOneBy(['id' => $report->getObjectInQuestion()]);
            if (!$objectInQuestion) {
                throw new \Exception('Ошибка. Нет рассматриваемого объекта в системе.', Response::HTTP_NOT_FOUND);
            }
            // Получим итем, с отзывами и дополнительной информацией.
            $jsonItem = file_get_contents($objectInQuestion->getFileReviews());
            /** @var ItemDTO $item */
            $item = $serializer->deserialize($jsonItem, ItemDTO::class, 'json');
            // Retrieve the HTML generated in our twig file
            $html = $this->renderView('report/index.html.twig', [
                'date' => $report->getCreatedAt(),
                'item' => $item,
            ]);
            $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

            // Load HTML to Dompdf
            $dompdf->loadHtml($html);

            // (Optional) Setup the paper size and orientation 'portrait' or 'portrait'
            $dompdf->setPaper('A4', 'portrait');

            // Render the HTML as PDF
            $dompdf->render();

            // Store PDF Binary Data
            $output = $dompdf->output();

            // путь к файлу формируем
            $temp = explode('json', $objectInQuestion->getFileReviews());
            $pdfFilepath = $temp[0].'reports'.$temp[1].'pdf';

            //добавим путь в БД
            $report->setFile($pdfFilepath);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($report);
            $entityManager->flush();

            // Write file to the desired path
            file_put_contents($pdfFilepath, $output);

            // ошибка
            $dataResponse = [
                'code' => Response::HTTP_OK,
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
}
