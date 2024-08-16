<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class WeatherException extends Exception
{
    protected $details;

    public function __construct($message = "", $code = 0, Exception $previous = null, $details = null)
    {
        parent::__construct($message, $code, $previous);
        $this->details = $details;
    }

    public function render($request): JsonResponse
    {
        return response()->json([
            'error' => $this->getMessage(),
            'details' => $this->details,
            'code' => $this->getCode() ?: 500,
            'trace' => $this->getTraceAsString() 
        ], $this->getCode() ?: 500);
    }

    public function setDetails($details): self
    {
        $this->details = $details;
        return $this;
    }

    public function getDetails()
    {
        return $this->details;
    }
}