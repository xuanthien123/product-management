<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ProductDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_login_and_dashboard_pages_load_as_api_clients(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('data-login-form', false)
            ->assertSee('Sign in to your workspace');

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('data-dashboard', false)
            ->assertSee('data-admin-only hidden', false)
            ->assertSee('id="product-list"', false)
            ->assertSee('id="filter-category"', false)
            ->assertSee('id="export-products-button"', false);
    }

    public function test_profile_and_change_password_have_separate_pages(): void
    {
        $this->get('/profile')
            ->assertOk()
            ->assertSee('/change-password', false)
            ->assertSee(route('profile.edit'), false)
            ->assertDontSee('id="profile-update-form"', false)
            ->assertDontSee('id="change-password-form"', false);

        $this->get('/profile/edit')
            ->assertOk()
            ->assertSee('data-profile-edit-page', false)
            ->assertSee('id="profile-update-form"', false);

        $this->get('/change-password')
            ->assertOk()
            ->assertSee('data-change-password-page', false)
            ->assertSee('id="change-password-form"', false);
    }

    public function test_password_recovery_pages_are_linked_to_the_email_reset_route(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee(route('password.request'), false);

        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('data-forgot-password', false)
            ->assertSee('id="recovery-email"', false);

        $this->get(route('password.reset', ['token' => 'example-reset-token']))
            ->assertOk()
            ->assertSee('data-reset-password', false)
            ->assertSee('value="example-reset-token"', false)
            ->assertDontSee('name="email"', false)
            ->assertDontSee('id="reset-email"', false);
    }

    public function test_import_page_has_sections_for_failed_and_successful_products(): void
    {
        $this->get('/products/import')
            ->assertOk()
            ->assertSee('products-import-template.xlsm', false)
            ->assertSee('Download Excel template (.xlsm)')
            ->assertSee('id="import-failures-section"', false)
            ->assertSee('id="import-failures-list"', false)
            ->assertSee('id="import-success-section"', false)
            ->assertSee('id="import-success-list"', false)
            ->assertSee('select multiple categories from the dropdown');
    }

    public function test_product_forms_include_inline_category_selection_and_management(): void
    {
        $this->get('/products/create')
            ->assertOk()
            ->assertSee('data-category-options', false)
            ->assertSee('data-category-manager-open', false)
            ->assertSee('data-category-manager-dialog', false);

        $this->get('/products/1/edit')
            ->assertOk()
            ->assertSee('data-category-options', false)
            ->assertSee('data-category-manager-open', false)
            ->assertSee('data-category-manager-dialog', false);
    }

    public function test_downloaded_import_template_has_live_categories_and_a_macro(): void
    {
        Category::query()->create(['name' => 'Audio']);
        Category::query()->create(['name' => 'Keyboard']);

        $response = $this->get(route('products.import-template'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.ms-excel.sheet.macroEnabled.12');

        $path = tempnam(sys_get_temp_dir(), 'product-import-template-');
        file_put_contents($path, $response->streamedContent());

        try {
            $spreadsheet = IOFactory::load($path);
            $this->assertNotEmpty($spreadsheet->getMacrosCode());
            $this->assertSame(
                'Audio',
                $spreadsheet->getSheetByName('Categories')->getCell('A2')->getValue()
            );
            $this->assertSame(
                'Keyboard',
                $spreadsheet->getSheetByName('Categories')->getCell('A3')->getValue()
            );
            $this->assertNull(
                $spreadsheet->getSheetByName('Categories')->getCell('B2')->getValue()
            );
            $this->assertSame(
                "'Categories'!\$A\$2:\$A\$3",
                $spreadsheet->getDefinedName('CategoryList')->getValue()
            );
            $this->assertSame(
                'CategoryList',
                $spreadsheet->getSheetByName('Products')->getDataValidation('E2')->getFormula1()
            );
        } finally {
            unlink($path);
        }
    }
}
