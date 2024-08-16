<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Exceptions\WeatherException;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    protected $apiKey = "d70250a48a297c0bc8d48b6789c5b709";
    protected $baseUrl = 'https://api.openweathermap.org/data/2.5/';
    protected $geoUrl = 'https://api.openweathermap.org/geo/1.0/';

    
    public function getWeatherData(string $city, string $units = 'metric'): array
    {
        try {
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
        } catch (WeatherException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new WeatherException('An unexpected error occurred in getWeatherData', 500, $e, [
                'city' => $city,
                'units' => $units,
                'exception' => get_class($e),
            ]);
        }
    }

    protected function getCoordinates(string $city): array
    {
        try {
            $response = Http::get("{$this->geoUrl}direct", [
                'q' => $city,
                'limit' => 1,
                'appid' => $this->apiKey,
            ]);

            if ($response->failed()) {
                throw new WeatherException('Geocoding API request failed', $response->status(), null, [
                    'response' => $response->json(),
                    'city' => $city,
                ]);
            }

            $data = $response->json();
            if (empty($data)) {
                throw new WeatherException('No coordinates found for the given city', 404, null, ['city' => $city]);
            }

            return [
                'name' => $data[0]['name'],
                'lat' => $data[0]['lat'],
                'lon' => $data[0]['lon'],
                'country' => $data[0]['country'],
                'state' => $data[0]['state'] ?? null,
            ];
        } catch (WeatherException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new WeatherException('An unexpected error occurred in getCoordinates', 500, $e, ['city' => $city]);
        }
    }

    protected function getForecast(float $lat, float $lon, string $units): array
    {
        try {
            $response = Http::get("{$this->baseUrl}forecast", [
                'lat' => $lat,
                'lon' => $lon,
                'units' => $units,
                'appid' => $this->apiKey,
            ]);

            if ($response->failed()) {
                throw new WeatherException('Forecast API request failed', $response->status(), null, [
                    'response' => $response->json(),
                    'lat' => $lat,
                    'lon' => $lon,
                    'units' => $units,
                ]);
            }

            return $response->json();
        } catch (WeatherException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new WeatherException('An unexpected error occurred in getForecast', 500, $e, [
                'lat' => $lat,
                'lon' => $lon,
                'units' => $units,
            ]);
        }
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