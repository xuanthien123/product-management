<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_can_be_created_listed_retrieved_updated_and_deleted(): void
    {
        $this->authenticate();

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.total', 0);

        $product = $this->postJson('/api/products', [
            'name' => 'Keyboard',
            'description' => 'Mechanical keyboard',
            'price' => 59.90,
            'quantity' => 12,
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Keyboard')
            ->assertJsonPath('data.description', 'Mechanical keyboard')
            ->assertJsonPath('data.price', '59.90')
            ->assertJsonPath('data.quantity', 12);

        $id = $product->json('data.id');

        $this->getJson("/api/products/{$id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $id);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1);

        $this->patchJson("/api/products/{$id}", [
            'quantity' => 8,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.quantity', 8)
            ->assertJsonPath('data.name', 'Keyboard');

        $this->deleteJson("/api/products/{$id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', null);

        $this->assertDatabaseMissing('products', ['id' => $id]);
    }

    public function test_product_creation_validates_required_and_nonnegative_values(): void
    {
        $this->authenticate();

        $this->postJson('/api/products', [
            'name' => '',
            'price' => -1,
            'quantity' => -2,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['name', 'price', 'quantity']);
    }

    public function test_product_list_can_filter_and_paginate_results_with_matching_summary(): void
    {
        $this->authenticate();

        foreach ([
            ['name' => 'Keyboard A', 'price' => 30, 'quantity' => 3],
            ['name' => 'Keyboard B', 'price' => 50, 'quantity' => 4],
            ['name' => 'Mouse', 'price' => 10, 'quantity' => 20],
        ] as $product) {
            $this->postJson('/api/products', $product + [
                'description' => null,
            ])->assertCreated();
        }

        $this->getJson('/api/products?search=Keyboard&min_price=20&max_price=60&sort=desc&per_page=1&page=2')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total_units', 7)
            ->assertJsonPath('meta.inventory_value', 290)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Keyboard A');
    }

    public function test_product_routes_require_authentication(): void
    {
        $this->getJson('/api/products')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
        $this->postJson('/api/products', [])
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    private function authenticate(): void
    {
        $user = User::factory()->create();
        $this->withToken($user->createToken('test')->plainTextToken);
    }
}
