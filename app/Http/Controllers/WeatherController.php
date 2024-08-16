<?php

namespace App\Http\Controllers;

use App\Services\WeatherService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Exceptions\WeatherException;

class WeatherController extends Controller
{
    protected $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    public function getWeatherData(Request $request): JsonResponse
    {
        $request->validate([
            'city' => 'required|string|max:255',
            'units' => 'string|in:metric,imperial',
        ]);

        try {
            $city = $request->input('city');
            $units = $request->input('units', 'metric');
            $data = $this->weatherService->getWeatherData($city, $units);
            return response()->json($data);
        } catch (WeatherException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An unexpected error occurred'], 500);
        }
    }

    public function getCurrentWeather(Request $request): JsonResponse
    {
        $request->validate([
            'city' => 'required|string|max:255',
            'units' => 'string|in:metric,imperial',
        ]);

        try {
            $city = $request->input('city');
            $units = $request->input('units', 'metric');
            $data = $this->weatherService->getWeatherData($city, $units);
            return response()->json($data['current']);
        } catch (WeatherException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An unexpected error occurred'], 500);
        }
    }

    public function getForecast(Request $request): JsonResponse
    {
        $request->validate([
            'city' => 'required|string|max:255',
            'units' => 'string|in:metric,imperial',
        ]);

        try {
            $city = $request->input('city');
            $units = $request->input('units', 'metric');
            $data = $this->weatherService->getWeatherData($city, $units);
            return response()->json($data['forecast']);
        } catch (WeatherException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An unexpected error occurred'], 500);
        }
    }
}