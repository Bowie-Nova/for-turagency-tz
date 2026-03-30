<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Models\Tour;
use App\Services\PuppeteerParserService;
use App\Services\TourAggregatorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ParseToursJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries = 1;

    public function __construct(protected Lead $lead) {}

    public function handle(
        PuppeteerParserService $parser,
        TourAggregatorService $aggregator
    ): void {
        try {
            $this->lead->update(['status' => 'processing']);

            Log::info("Начинаю парсинг для lead #{$this->lead->id}");

            $rawResults = $parser->parseAllOperators(
                $this->lead->getSearchCriteria()
            );

            if (isset($rawResults['error'])) {
                throw new \Exception($rawResults['error']);
            }

            $aggregatedTours = $aggregator->aggregate(
                $rawResults,
                $this->lead->getSearchCriteria()
            );

            foreach ($aggregatedTours as $tour) {
                Tour::create([
                    'lead_id' => $this->lead->id,
                    ...$tour
                ]);
            }

            cache()->put(
                "lead_tours_{$this->lead->id}",
                $aggregatedTours,
                now()->addHours(24)
            );

            $this->lead->update(['status' => 'completed']);

            Log::info(
                "✓ Парсинг завершен для lead #{$this->lead->id}, "
                . "найдено " . count($aggregatedTours) . " туров"
            );

        } catch (\Exception $e) {
            Log::error(
                "✗ Ошибка парсинга lead #{$this->lead->id}: " . $e->getMessage()
            );
            $this->lead->update(['status' => 'failed']);
            throw $e;
        }
    }
}
