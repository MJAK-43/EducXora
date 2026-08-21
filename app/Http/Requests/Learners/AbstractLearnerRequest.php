<?php

declare(strict_types=1);

namespace App\Http\Requests\Learners;

use App\Domain\Learner\Enums\LearnerLanguage;
use App\Domain\Learner\Enums\LearnerLevel;
use App\Domain\Learner\Support\PhoneNormalizer;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class AbstractLearnerRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge(['phone' => PhoneNormalizer::normalize((string) $this->input('phone'))]);
        }
    }

    /** @return array<string, mixed> */
    protected function learnerRules(): array
    {
        return [
            'organization_id' => ['prohibited'],
            'center_id' => ['prohibited'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'phone' => [
                'required', 'string', 'max:32',
                static function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || ! PhoneNormalizer::isValid($value)) {
                        $fail('Le numéro doit être un numéro camerounais valide à 9 chiffres.');
                    }
                },
            ],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'language' => ['required', Rule::enum(LearnerLanguage::class)],
            'initial_level' => ['required', Rule::enum(LearnerLevel::class)],
            'photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4096,max_height=4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'organization_id.prohibited' => 'L’organisation est déterminée par votre session.',
            'center_id.prohibited' => 'Le centre est déterminé par votre session.',
            'photo.max' => 'La photo ne doit pas dépasser 2 Mo.',
            'photo.image' => 'Le fichier doit être une image valide.',
        ];
    }
}
