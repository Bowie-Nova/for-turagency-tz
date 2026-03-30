<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TourParserService
{
    protected array $operators = [
        'selfie' => 'https://b2b.selfietravel.kz/search_tour',
        'abk' => 'https://b2b.abktourism.kz/search_tour',
        'travelluxe' => 'https://online.travelluxe.kz/search_tour',
        'kazunion' => 'https://online.kazunion.com/search_tour',
        'fstravel' => 'https://b2b.fstravel.asia/search_tour',
        'crystalbay' => 'https://booking-kz.crystalbay.com/search_tour',
    ];

    /**
     * Проверяет доступность оператора
     */
    public function checkOperatorAvailability(string $operatorKey): array
    {
        if (!isset($this->operators[$operatorKey])) {
            return ['status' => 'error', 'message' => 'Оператор не найден'];
        }

        $url = $this->operators[$operatorKey];

        try {
            $response = Http::timeout(10)->get($url);

            if ($response->successful()) {
                return [
                    'status' => 'available',
                    'operator' => $operatorKey,
                    'url' => $url
                ];
            }
        } catch (\Exception $e) {
            Log::warning("Оператор {$operatorKey} недоступен: " . $e->getMessage());
        }

        return ['status' => 'unavailable', 'message' => 'Оператор недоступен'];
    }

    /**
     * Получить список всех операторов
     */
    public function getAllOperators(): array
    {
        return array_keys($this->operators);
    }
}
