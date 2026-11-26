<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_manage_categories_and_they_are_isolated(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->postJson('/api/v1/categories', [
            'name' => 'Makan', 'type' => 'expense',
        ])->assertCreated()->assertJsonPath('data.name', 'Makan');

        $this->postJson('/api/v1/categories', [
            'name' => 'Makan', 'type' => 'expense',
        ])->assertUnprocessable()->assertJsonValidationErrors('name');

        $foreign = Category::factory()->create();
        $this->getJson('/api/v1/categories/'.$foreign->id)->assertNotFound();
        $this->getJson('/api/v1/categories')->assertOk()->assertJsonCount(1, 'data');
    }
}
