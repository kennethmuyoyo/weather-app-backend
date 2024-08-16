<?php

namespace App\Exceptions;

use Exception;

class WeatherException extends Exception
{
    public function render($request)
    {
        return response()->json([
            'error' => $this->getMessage()
        ], $this->getCode() ?: 500);
    }
}