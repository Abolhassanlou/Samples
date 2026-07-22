<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Qualification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Qualification>
 */
class QualificationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->jobTitle(),
            'type' => Qualification::TYPE_SKILL,
            'code' => strtoupper(
                fake()->unique()->bothify('QUA-###')
            ),
            'description' => fake()->sentence(),
            'requires_verification' => false,
            'requires_expiry_date' => false,
            'is_active' => true,
        ];
    }

    public function subject(): static
    {
        return $this->state(fn (): array => [
            'type' => Qualification::TYPE_SUBJECT,
        ]);
    }

    public function language(): static
    {
        return $this->state(fn (): array => [
            'type' => Qualification::TYPE_LANGUAGE,
        ]);
    }

    public function certificate(): static
    {
        return $this->state(fn (): array => [
            'type' => Qualification::TYPE_CERTIFICATE,
            'requires_verification' => true,
            'requires_expiry_date' => true,
        ]);
    }

    public function training(): static
    {
        return $this->state(fn (): array => [
            'type' => Qualification::TYPE_TRAINING,
            'requires_verification' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
