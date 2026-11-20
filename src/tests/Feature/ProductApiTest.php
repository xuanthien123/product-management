<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Exports\ProductsExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;
use PhpOffice\PhpSpreadsheet\IOFactory;
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

    public function test_product_list_defaults_to_newest_first(): void
    {
        $this->authenticate();

        $this->postJson('/api/products', [
            'name' => 'Keyboard',
            'price' => 30,
            'quantity' => 3,
        ])->assertCreated();

        $this->postJson('/api/products', [
            'name' => 'Mouse',
            'price' => 10,
            'quantity' => 20,
        ])->assertCreated();

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Mouse')
            ->assertJsonPath('data.1.name', 'Keyboard');
    }

    public function test_product_list_can_filter_by_category_and_reject_unknown_category_ids(): void
    {
        $this->authenticate();

        $categoryId = $this->postJson('/api/categories', ['name' => 'Audio'])
            ->assertCreated()
            ->json('data.id');
        $otherCategoryId = $this->postJson('/api/categories', ['name' => 'Accessories'])
            ->assertCreated()
            ->json('data.id');

        $this->postJson('/api/products', [
            'name' => 'Headphones',
            'price' => 80,
            'quantity' => 4,
            'category_ids' => [$categoryId, $otherCategoryId],
        ])->assertCreated();

        $this->postJson('/api/products', [
            'name' => 'Mouse',
            'price' => 20,
            'quantity' => 10,
            'category_ids' => [$otherCategoryId],
        ])->assertCreated();

        $this->getJson("/api/products?category_id={$categoryId}")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.total_units', 4)
            ->assertJsonPath('meta.inventory_value', 320)
            ->assertJsonPath('data.0.name', 'Headphones');

        $this->getJson('/api/products?category_id=999')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_admin_can_export_filtered_products_to_excel(): void
    {
        $this->authenticate();

        $electronicsId = $this->postJson('/api/categories', ['name' => 'Electronics'])
            ->assertCreated()
            ->json('data.id');
        $otherCategoryId = $this->postJson('/api/categories', ['name' => 'Accessories'])
            ->assertCreated()
            ->json('data.id');

        $this->postJson('/api/products', [
            'name' => 'Keyboard Pro',
            'description' => 'Mechanical keyboard',
            'price' => 80,
            'quantity' => 0,
            'category_ids' => [$electronicsId],
        ])->assertCreated();

        $this->postJson('/api/products', [
            'name' => 'Mouse',
            'price' => 20,
            'quantity' => 10,
            'category_ids' => [$otherCategoryId],
        ])->assertCreated();

        $filters = [
            'search' => 'Keyboard',
            'category_id' => $electronicsId,
        ];
        $export = new ProductsExport($filters);
        $path = tempnam(sys_get_temp_dir(), 'products-export-');
        file_put_contents(
            $path,
            ExcelFacade::raw($export, \Maatwebsite\Excel\Excel::XLSX)
        );

        try {
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $this->assertSame('Name', $sheet->getCell('B1')->getValue());
            $this->assertSame('Keyboard Pro', $sheet->getCell('B2')->getValue());
            $this->assertSame(0, $sheet->getCell('E2')->getValue());
            $this->assertSame(\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC, $sheet->getCell('E2')->getDataType());
            $this->assertTrue($sheet->getSheetView()->getShowZeros());
            $this->assertSame('Electronics', $sheet->getCell('F2')->getValue());
        } finally {
            unlink($path);
        }

        ExcelFacade::fake();

        $this->get("/api/products/export?search=Keyboard&category_id={$electronicsId}")
            ->assertOk();

        ExcelFacade::assertDownloaded('products.xlsx', function (ProductsExport $export): bool {
            $products = $export->query()->get();

            return $export->headings() === [
                'ID',
                'Name',
                'Description',
                'Price',
                'Quantity',
                'Categories',
                'Created at',
                'Updated at',
            ]
                && $products->pluck('name')->all() === ['Keyboard Pro']
                && $export->map($products->first())[5] === 'Electronics';
        });
    }

    public function test_admin_can_manage_categories_and_assign_multiple_categories_to_products(): void
    {
        $this->authenticate();

        $electronics = $this->postJson('/api/categories', ['name' => 'Electronics'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Electronics')
            ->json('data.id');
        $accessories = $this->postJson('/api/categories', ['name' => 'Accessories'])
            ->assertCreated()
            ->json('data.id');

        $productId = $this->postJson('/api/products', [
            'name' => 'Keyboard',
            'price' => 59.90,
            'quantity' => 12,
            'category_ids' => [$electronics, $accessories],
        ])
            ->assertCreated()
            ->assertJsonCount(2, 'data.categories')
            ->json('data.id');

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(2, 'data.0.categories');

        $this->patchJson("/api/products/{$productId}", ['quantity' => 11])
            ->assertOk()
            ->assertJsonCount(2, 'data.categories');

        $this->putJson("/api/categories/{$electronics}", ['name' => 'Computer gear'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Computer gear');

        $this->patchJson("/api/products/{$productId}", [
            'category_ids' => [$electronics],
        ])
            ->assertOk()
            ->assertJsonCount(1, 'data.categories')
            ->assertJsonPath('data.categories.0.name', 'Computer gear');

        $this->deleteJson("/api/categories/{$electronics}")
            ->assertOk();

        $this->getJson("/api/products/{$productId}")
            ->assertOk()
            ->assertJsonCount(0, 'data.categories');

        $this->assertDatabaseHas('products', ['id' => $productId]);
    }

    public function test_non_admin_users_cannot_manage_categories(): void
    {
        $user = User::factory()->create();
        $this->withToken($user->createToken('test')->plainTextToken);

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonPath('data', []);
        $this->postJson('/api/categories', ['name' => 'Forbidden'])->assertForbidden();
    }

    public function test_product_routes_require_authentication(): void
    {
        $this->getJson('/api/products')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
        $this->getJson('/api/products/export')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
        $this->postJson('/api/products', [])
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_missing_product_returns_a_model_specific_not_found_message(): void
    {
        $this->authenticate();

        $this->getJson('/api/products/99')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Product not found.')
            ->assertJsonMissing(['message' => 'No query results for model [App\\Models\\Product] 99']);
    }

    public function test_non_admin_users_cannot_create_products(): void
    {
        $user = User::factory()->create();
        $this->withToken($user->createToken('test')->plainTextToken);

        $this->postJson('/api/products', [
            'name' => 'Forbidden product',
            'price' => 10,
            'quantity' => 1,
        ])
            ->assertForbidden()
            ->assertJsonPath('success', false);
        $this->getJson('/api/products/export')
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->assertDatabaseMissing('products', ['name' => 'Forbidden product']);
    }

    public function test_admin_can_import_valid_products_and_receives_invalid_row_failures(): void
    {
        $this->authenticate();

        $file = UploadedFile::fake()->createWithContent(
            'products.csv',
            "name,price,quantity,description\nKeyboard,59.90,12,Mechanical\nInvalid,-1,-2,\n"
        );

        $this->post('/api/products/import', ['file' => $file])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.imported', 1)
            ->assertJsonPath('meta.failed', 1)
            ->assertJsonPath('meta.imported_products.0.name', 'Keyboard')
            ->assertJsonPath('meta.failures.0.product.name', 'Invalid')
            ->assertJsonCount(2, 'meta.failures.0.errors');

        $this->assertDatabaseHas('products', [
            'name' => 'Keyboard',
            'price' => '59.90',
            'quantity' => 12,
            'description' => 'Mechanical',
        ]);
        $this->assertDatabaseMissing('products', ['name' => 'Invalid']);
    }

    public function test_product_import_assigns_multiple_categories_and_reports_unknown_categories(): void
    {
        $this->authenticate();

        $audioId = $this->postJson('/api/categories', ['name' => 'Audio'])
            ->assertCreated()
            ->json('data.id');
        $accessoriesId = $this->postJson('/api/categories', ['name' => 'Accessories'])
            ->assertCreated()
            ->json('data.id');

        $file = UploadedFile::fake()->createWithContent(
            'products.csv',
            "name,price,quantity,description,categories\n"
                . "Headphones,79.90,5,Wireless,Audio | Accessories\n"
                . "Speaker,49.90,3,Portable,Unknown\n"
        );

        $response = $this->post('/api/products/import', ['file' => $file])
            ->assertOk()
            ->assertJsonPath('meta.imported', 1)
            ->assertJsonPath('meta.failed', 1)
            ->assertJsonPath('meta.imported_products.0.categories', 'Audio | Accessories')
            ->assertJsonPath('meta.failures.0.product.categories', 'Unknown');

        $productId = Product::query()->where('name', 'Headphones')->value('id');
        $this->assertDatabaseHas('category_product', [
            'product_id' => $productId,
            'category_id' => $audioId,
        ]);
        $this->assertDatabaseHas('category_product', [
            'product_id' => $productId,
            'category_id' => $accessoriesId,
        ]);
        $this->assertDatabaseMissing('products', ['name' => 'Speaker']);
    }

    public function test_product_import_accepts_macro_enabled_excel_files(): void
    {
        $this->authenticate();

        $file = UploadedFile::fake()->createWithContent(
            'products-import-template.xlsm',
            file_get_contents(resource_path('templates/products-import-template.xlsm'))
        );

        $response = $this->post('/api/products/import', ['file' => $file])->assertOk();

        $this->assertSame(2, $response->json('meta.imported'), json_encode($response->json()));
        $this->assertSame(0, $response->json('meta.failed'), json_encode($response->json()));
    }

    private function authenticate(): void
    {
        $user = User::factory()->create();
        $adminRole = Role::query()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
        $user->roles()->attach($adminRole);

        $this->withToken($user->createToken('test')->plainTextToken);
    }
}
