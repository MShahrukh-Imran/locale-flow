<?php

namespace App\Http\Requests\Translation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $translation = $this->route('translation');
        $locale = $this->input('locale', $translation->locale);

        return [
            'locale' => ['sometimes', 'string', 'min:2', 'max:8', 'regex:/^[a-zA-Z_\-]+$/'],
            'key' => [
                'sometimes', 'string', 'max:191',
                Rule::unique('translations', 'key')
                    ->ignore($translation->id)
                    ->where(fn ($q) => $q->where('locale', $locale)),
            ],
            'content' => ['sometimes', 'string'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:64'],
        ];
    }
}
