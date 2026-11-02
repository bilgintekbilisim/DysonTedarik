<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'is_active' => true,
            'show_in_menu' => true,
        ];
    }

    public function childOf(Category $parent): static
    {
        return $this->state(fn () => ['parent_id' => $parent->id]);
    }
}
