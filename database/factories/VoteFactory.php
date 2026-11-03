<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FeatureRequest;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vote>
 */
class VoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'feature_request_id' => FeatureRequest::factory(),
        ];
    }
}
