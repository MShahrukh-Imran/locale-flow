<?php

namespace Database\Factories;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Translation>
 */
class TranslationFactory extends Factory
{
    protected $model = Translation::class;

    public function definition(): array
    {
        $locales = ['en', 'fr', 'es', 'de', 'it'];
        $groups = ['auth', 'dashboard', 'profile', 'billing', 'errors', 'common', 'emails'];

        return [
            'locale' => $this->faker->randomElement($locales),
            'key' => $this->faker->randomElement($groups).'.'.$this->faker->slug(3).'.'.mt_rand(1, 9999999),
            'content' => $this->faker->sentence(),
        ];
    }
}
