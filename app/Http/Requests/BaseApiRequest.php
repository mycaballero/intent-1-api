<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

abstract class BaseApiRequest extends FormRequest
{
    /**
     * Validated data with keys converted to snake_case for Spatie Data / DTOs.
     * Request body is expected in camelCase; Data classes use snake_case properties.
     *
     * @return array<string, mixed>
     */
    public function validatedSnake(): array
    {
        return $this->keysToSnake($this->validated());
    }

    /**
     * Recursively convert array keys to snake_case.
     *
     * @param  array<string, mixed>  $array
     * @return array<string, mixed>
     */
    protected function keysToSnake(array $array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $snakeKey = Str::snake($key);
            $result[$snakeKey] = is_array($value) && ! array_is_list($value)
                ? $this->keysToSnake($value)
                : $value;
        }

        return $result;
    }
}
