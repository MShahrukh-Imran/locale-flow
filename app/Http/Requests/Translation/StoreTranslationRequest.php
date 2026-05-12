<?php

namespace App\Http\Requests\Translation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'locale' => ['required', 'string', 'min:2', 'max:8', 'regex:/^[a-zA-Z_\-]+$/'],
            'key' => [
                'required', 'string', 'max:191',
                Rule::unique('translations', 'key')->where(fn ($q) => $q->where('locale', $this->input('locale'))),
            ],
            'content' => ['required', 'string'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:64'],
        ];
    }
}
