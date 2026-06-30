<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WeatherController extends Controller
{
    public function fetch(Request $request)
    {
        // Get coordinates if provided, otherwise use default city
        if ($request->has('lat') && $request->has('lon')) {
            $query = trim($request->lat) . ',' . trim($request->lon);
        } else {
            $query = config('services.weather.default_city', 'Egypt');
        }

        $data = $this->getWeatherData($query);

        return response()->json($data);
    }

    private function getWeatherData(string $query): array
    {
        $url = "https://wttr.in/" . urlencode($query) . "?format=j1";

        $context = stream_context_create([
            "http" => [
                "method"  => "GET",
                "timeout" => 10,
                "header"  => "User-Agent: Laravel Weather App\r\n"
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return ['error' => 'Unable to fetch weather data. Please try again later.'];
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'Invalid response from weather service.'];
        }

        $current = $data['current_condition'][0] ?? null;

        if (!$current) {
            return ['error' => 'No weather data available for this location.'];
        }

        return [
            'name'        => $data['nearest_area'][0]['areaName'][0]['value'] ?? 'Unknown',
            'temp'        => $current['temp_C']                               ?? 'N/A',
            'description' => $current['weatherDesc'][0]['value']              ?? 'N/A',
            'humidity'    => $current['humidity']                             ?? 'N/A',
            'wind_speed'  => $current['windspeedKmph']                        ?? 'N/A',
        ];
    }
}