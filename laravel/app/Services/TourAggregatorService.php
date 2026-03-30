<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class TourAggregatorService
{
    public function aggregate(array $rawResults, array $criteria): array
    {
        $aggregated = [];

        foreach ($rawResults as $operator => $tours) {
            if (isset($tours['error']) || !is_array($tours)) {
                Log::warning("Оператор {$operator} вернул ошибку или пустой результат");
                continue;
            }

            foreach ($tours as $tour) {
                if (!$this->meetsCriteria($tour, $criteria)) {
                    continue;
                }

                $enrichedTour = array_merge($tour, [
                    'operator' => $operator,
                    'popularity_score' => $this->calculateScore($tour, $criteria),
                    // frontend relies on `days` property rather than `nights`
                    'days' => $tour['nights'] ?? 0,
                ]);

                $aggregated[] = $enrichedTour;
            }
        }

        // Сортировка: сначала по цене, затем по рейтингу
        usort($aggregated, function ($a, $b) {
            if ($a['price'] != $b['price']) {
                return $a['price'] <=> $b['price'];
            }
            return ($b['popularity_score'] ?? 0) <=> ($a['popularity_score'] ?? 0);
        });

        // Возвращаем топ 100
        return array_slice($aggregated, 0, 100);
    }

    protected function meetsCriteria(array $tour, array $criteria): bool
    {
        // Проверяем наличие основных полей тура
        if (empty($tour['title']) || empty($tour['hotel_name']) || empty($tour['price'])) {
            return false;
        }

        // Проверяем город отправления
        if (isset($criteria['departure_city']) && !empty($tour['departure_city'])) {
            if (stripos($tour['departure_city'], $criteria['departure_city']) === false) {
                return false;
            }
        }

        // Проверяем категорию отеля если указана
        if (isset($criteria['hotel_category']) && $criteria['hotel_category'] > 0) {
            if (isset($tour['hotel_category']) && $tour['hotel_category'] < $criteria['hotel_category']) {
                return false;
            }
        }

        // Проверяем количество ночей (входит в диапазон)
        if (isset($criteria['nights_from'], $criteria['nights_to']) && isset($tour['nights'])) {
            if ($tour['nights'] < $criteria['nights_from'] || $tour['nights'] > $criteria['nights_to']) {
                return false;
            }
        }

        // Note: we skip date filtering since parsed tours might be historical data
        // and we want to show all relevant results regardless of exact dates

        return true;
    }

    protected function calculateScore(array $tour, array $criteria): float
    {
        $score = 0;

        // Цена (чем ниже, тем выше балл)
        $priceScore = max(0, 10000 - ($tour['price'] / 100));
        $score += $priceScore * 0.4;

        // Наличие мест
        if (isset($tour['available_seats']) && $tour['available_seats'] > 0) {
            $score += 20;
        }

        // Рейтинг отеля
        if (isset($tour['hotel_rating'])) {
            $score += $tour['hotel_rating'] * 10;
        }

        // Соответствие количеству ночей
        if (isset($tour['nights']) && isset($criteria['nights_from'], $criteria['nights_to'])) {
            $nightsAvg = ($criteria['nights_from'] + $criteria['nights_to']) / 2;
            $nightsDiff = abs($tour['nights'] - $nightsAvg);
            $score += max(0, 10 - $nightsDiff);
        }

        // Упоминание пожеланий пользователя
        if (isset($criteria['preferences']) && is_array($criteria['preferences'])) {
            foreach ($criteria['preferences'] as $preference) {
                if (!empty($preference)) {
                    if (
                        stripos($tour['hotel_name'], $preference) !== false ||
                        (isset($tour['title']) && stripos($tour['title'], $preference) !== false)
                    ) {
                        $score += 50;
                    }
                }
            }
        }

        return round($score, 2);
    }
}
