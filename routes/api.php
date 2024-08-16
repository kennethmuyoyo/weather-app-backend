<?php

use App\Http\Controllers\WeatherController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return 'Welcome to weather api';
});Route::get('/weather', [WeatherController::class, 'getWeatherData']);
Route::get('/weather/current', [WeatherController::class, 'getCurrentWeather']);
Route::get('/weather/forecast', [WeatherController::class, 'getForecast']);