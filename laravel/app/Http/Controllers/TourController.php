<?php

namespace App\Http\Controllers;

use App\Jobs\ParseToursJob;
use App\Models\Lead;
use Illuminate\Http\Request;

class TourController extends Controller
{
    public function search(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string',
            'email' => 'nullable|email',
            'departure_city' => 'required|string',
            'destination_country' => 'required|string',
            'hotel_category' => 'nullable|integer|min:3|max:5',
            'departure_from' => 'required|date|after_or_equal:today',
            'departure_to' => 'required|date|after:departure_from',
            'nights_from' => 'required|integer|min:1|max:30',
            'nights_to' => 'required|integer|min:1|max:30|gte:nights_from',
            'adults' => 'required|integer|min:1',
            'children' => 'nullable|integer|min:0',
            'preferences' => 'nullable|array',
        ]);

        $lead = Lead::create($validated);

        // Mark lead as processing immediately to avoid race where client polls before the job starts
        $lead->update(['status' => 'processing']);

        // Always dispatch job for real parsing (ABK parser will save HTML results automatically)
        ParseToursJob::dispatch($lead);

        return response()->json([
            'status' => 'processing',
            'message' => 'Поиск туров запущен. Пожалуйста, подождите...',
            'lead_id' => $lead->id,
            'check_later' => "/api/tours/{$lead->id}/results"
        ], 202);
    }

    public function getResults(Lead $lead)
    {
        // Treat null/empty status as still processing (job may not have started yet)
        if (empty($lead->status) || $lead->status === 'processing') {
            return response()->json([
                'status' => 'processing',
                'message' => 'Поиск в процессе. Пожалуйста, подождите...'
            ], 202);
        }

        if ($lead->status === 'failed') {
            return response()->json([
                'status' => 'failed',
                'message' => 'Ошибка при поиске туров. Попробуйте позже.'
            ], 400);
        }

        $tours = $lead->tours()
            ->orderByDesc('popularity_score')
            ->orderBy('price')
            ->get();

        return response()->json([
            'status' => 'completed',
            'lead' => $lead,
            'tours' => $tours,
            'count' => count($tours)
        ]);
    }
}
