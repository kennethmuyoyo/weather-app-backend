<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Exceptions\WeatherException;

class WeatherService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.openweathermap.org/data/2.5/';
    protected $geoUrl = 'https://api.openweathermap.org/geo/1.0/';

    public function __construct()
    {
        $this->apiKey = config('services.openweathermap.key');
    }

    public function getWeatherData(string $city, string $units = 'metric'): array
    {
        $coordinates = $this->getCoordinates($city);
        $weatherData = $this->getForecast($coordinates['lat'], $coordinates['lon'], $units);
        
        return [
            'current' => $this->getCurrentWeather($weatherData),
            'forecast' => $this->getThreeDayForecast($weatherData),
            'location' => [
                'name' => $coordinates['name'],
                'country' => $coordinates['country'],
            ],
        ];
    }

    protected function getCoordinates(string $city): array
    {
        return Cache::remember("coordinates_{$city}", 86400, function () use ($city) {
            $response = Http::get("{$this->geoUrl}direct", [
                'q' => $city,
                'limit' => 1,
                'appid' => $this->apiKey,
            ]);

            if ($response->failed() || empty($response->json())) {
                throw new WeatherException('Failed to get coordinates for the city');
            }

            $data = $response->json()[0];
            return [
                'name' => $data['name'],
                'lat' => $data['lat'],
                'lon' => $data['lon'],
                'country' => $data['country'],
                'state' => $data['state'] ?? null,
            ];
        });
    }

    protected function getForecast(float $lat, float $lon, string $units): array
    {
        return Cache::remember("forecast_{$lat}_{$lon}_{$units}", 1800, function () use ($lat, $lon, $units) {
            $response = Http::get("{$this->baseUrl}forecast", [
                'lat' => $lat,
                'lon' => $lon,
                'units' => $units,
                'appid' => $this->apiKey,
            ]);

            if ($response->failed()) {
                throw new WeatherException('Failed to fetch forecast data');
            }

            return $response->json();
        });
    }

    protected function getCurrentWeather(array $weatherData): array
    {
        $current = $weatherData['list'][0];
        return [
            'temp' => $current['main']['temp'],
            'feels_like' => $current['main']['feels_like'],
            'pressure' => $current['main']['pressure'],
            'main' => $current['weather'][0]['main'],
            'description' => $current['weather'][0]['description'],
            'icon' => $current['weather'][0]['icon'],
            'wind' => [
                'speed' => $current['wind']['speed'],
                'deg' => $current['wind']['deg'],
            ],
            'humidity' => $current['main']['humidity'],
            'date' => $current['dt'],
        ];
    }

    protected function getThreeDayForecast(array $weatherData): array
    {
        $forecast = [];
        $uniqueDays = [];

        foreach ($weatherData['list'] as $item) {
            $date = date('Y-m-d', $item['dt']);
            if (!in_array($date, $uniqueDays) && count($uniqueDays) < 3) {
                $uniqueDays[] = $date;
                $forecast[] = [
                    'date' => $item['dt'],
                    'temp_min' => $item['main']['temp_min'],
                    'temp_max' => $item['main']['temp_max'],
                    'icon' => $item['weather'][0]['icon'],
                ];
            }
        }

        return $forecast;
    }

}