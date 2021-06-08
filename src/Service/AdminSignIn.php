<?php


namespace App\Service;

use App\Model\UserDTO;
use JMS\Serializer\SerializerInterface;

class AdminSignIn
{
    private $startUri;
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
        $this->startUri = 'api.determination-of-tonality.local';
    }

    public function auth(string $request): array
    {
        // Запрос в сервис биллинг
        $query = curl_init($this->startUri . '/api/v1/auth');
        curl_setopt($query, CURLOPT_POST, 1);
        curl_setopt($query, CURLOPT_POSTFIELDS, $request);
        curl_setopt($query, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($query, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($request)
        ]);
        $response = curl_exec($query);
        // Ошибка с биллинга
        if ($response === false) {
            throw new \Exception('Возникли технические неполадки. Попробуйте позднее');
        }
        curl_close($query);

        // Ответа от сервиса
        $result = json_decode($response, true);
        if (isset($result['code'])) {
            if ($result['code'] === 401) {
                throw new \Exception('Проверьте правильность введёного логина и пароля');
            }
        }

        return $result;
    }
}
