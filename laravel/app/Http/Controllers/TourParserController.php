<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TourParserController extends Controller
{
    public function pep(Request $request)
    {
        $data = $request->all();

        // Пока просто возвращаем то, что получили
        return response()->json([
            'status' => 'success',
            'received_data' => $data['personal']['fullName'] ?? "Пиздааа", // Личные данные
        ]);
    }
}
