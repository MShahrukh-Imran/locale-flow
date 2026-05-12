<?php

namespace App\Http\Requests\Translation;

use Illuminate\Foundation\Http\FormRequest;

class IndexTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'locale' => ['sometimes', 'string', 'min:2', 'max:8'],
            'key' => ['sometimes', 'string', 'max:191'],
            'content' => ['sometimes', 'string', 'max:191'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:64'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        return $this->only(['locale', 'key', 'content', 'tags']);
    }
}
