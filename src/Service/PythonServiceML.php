<?php

namespace App\Service;

use App\Exception\PythonServiceMLUnavaibleException;
use JMS\Serializer\SerializerInterface;

class PythonServiceML
{
    private $startUri;
    protected $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->startUri = $_ENV['ML'];
        $this->serializer = $serializer;
    }

    public function predictTonality($json, $path, $nameDataSet, $nameClassificator)
    {
        // Запрос в сервис биллинг
        $query = curl_init(
            $this->startUri.
            $path.'?nameDataSet='.$nameDataSet.'&nameClassificator='.$nameClassificator
        );
        curl_setopt($query, CURLOPT_POST, 1);
        curl_setopt($query, CURLOPT_POSTFIELDS, $json);
        curl_setopt($query, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($query, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: '.strlen($json),
        ]);
        $response = curl_exec($query);
        $data = json_decode($response, true);
        // Ошибка с биллинга
        if (false === $response) {
            throw new PythonServiceMLUnavaibleException('Возникли технические неполадки. Попробуйте позднее', 500);
        }
        if (isset($data['code']) && 500 === $data['code']) {
            throw new PythonServiceMLUnavaibleException($data['message'], 500);
        }
        curl_close($query);
        return $this->serializer->deserialize($response, 'array<App\Model\ReviewDTO>', 'json');
    }
}
