<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Obj;
use App\Models\User;
use App\Models\Product;
use App\Models\ObjectCashAccount;
use App\Models\ObjectCashBalance;
use App\Models\WarehouseStock;
use App\Models\ObjectEmployee;
use App\Enums\UserRole;
use App\Enums\ObjectType;
use App\Enums\ProductUnit;
use App\Enums\CashAccountType;
use App\Enums\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ObjectCRUDTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Obj $object;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::SuperAdmin,
            'is_active' => true,
        ]);

        $this->object = Obj::create([
            'name' => 'Test Factory',
            'type' => ObjectType::Factory,
            'address' => 'Zangiota',
            'note' => 'Main plant',
            'is_active' => true,
        ]);
    }

    /**
     * Admin can view objects index.
     */
    public function test_admin_can_view_objects_index(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/objects');
        $response->assertOk();
        $response->assertSee('Test Factory');
    }

    /**
     * Admin can view object details.
     */
    public function test_admin_can_view_object_details(): void
    {
        $response = $this->actingAs($this->admin)->get("/admin/objects/{$this->object->id}");
        $response->assertOk();
        $response->assertSee('Test Factory');
        $response->assertSee('Kassalar');
    }

    /**
     * Admin can update object.
     */
    public function test_admin_can_update_object(): void
    {
        $response = $this->actingAs($this->admin)->put("/admin/objects/{$this->object->id}", [
            'name' => 'Updated Factory Name',
            'type' => 'warehouse',
            'address' => 'Chilonzor',
            'note' => 'Updated note',
            'is_active' => '0',
        ]);

        $response->assertRedirect();
        
        $this->object->refresh();
        $this->assertEquals('Updated Factory Name', $this->object->name);
        $this->assertEquals('warehouse', $this->object->type->value);
        $this->assertFalse($this->object->is_active);
    }

    /**
     * Admin can delete object.
     */
    public function test_admin_can_delete_object(): void
    {
        $response = $this->actingAs($this->admin)->delete("/admin/objects/{$this->object->id}");
        $response->assertRedirect();
        
        $this->assertSoftDeleted('objects', [
            'id' => $this->object->id
        ]);
    }

    /**
     * Admin can add and delete cash account for object.
     */
    public function test_admin_can_manage_object_cash_accounts(): void
    {
        // 1. Add Cash Account
        $response = $this->actingAs($this->admin)->post("/admin/objects/{$this->object->id}/cash-accounts", [
            'name' => 'Plant Card Cashier',
            'type' => 'card',
        ]);

        $response->assertRedirect();

        $account = ObjectCashAccount::where('object_id', $this->object->id)->where('name', 'Plant Card Cashier')->first();
        $this->assertNotNull($account);
        $this->assertEquals('card', $account->type->value);

        // Check balances are initialized to 0
        $this->assertEquals(0, $account->getBalance(Currency::UZS));
        $this->assertEquals(0, $account->getBalance(Currency::USD));

        // 2. Delete Cash Account
        $responseDel = $this->actingAs($this->admin)->delete("/admin/objects/{$this->object->id}/cash-accounts/{$account->id}");
        $responseDel->assertRedirect();

        $this->assertSoftDeleted('object_cash_accounts', [
            'id' => $account->id
        ]);
    }

    /**
     * Admin can manage warehouse stock for object.
     */
    public function test_admin_can_manage_object_warehouse_stock(): void
    {
        $product = Product::create([
            'name' => 'Cement',
            'unit' => ProductUnit::Kg,
            'min_stock_level' => 500,
            'is_active' => true,
        ]);

        // 1. Add Stock
        $response = $this->actingAs($this->admin)->post("/admin/objects/{$this->object->id}/warehouse-stocks", [
            'product_id' => $product->id,
            'quantity' => 1000,
        ]);

        $response->assertRedirect();

        $stock = WarehouseStock::where('object_id', $this->object->id)->where('product_id', $product->id)->first();
        $this->assertNotNull($stock);
        $this->assertEquals(1000, $stock->quantity);

        // Verify low stock indicator helper is working
        $this->assertFalse($stock->isLow());

        // 2. Delete Stock
        $responseDel = $this->actingAs($this->admin)->delete("/admin/objects/{$this->object->id}/warehouse-stocks/{$stock->id}");
        $responseDel->assertRedirect();

        $this->assertDatabaseMissing('warehouse_stocks', [
            'id' => $stock->id
        ]);
    }

    /**
     * Admin can assign and remove employee for object.
     */
    public function test_admin_can_manage_object_employees(): void
    {
        $employeeUser = User::create([
            'name' => 'Worker Jamal',
            'email' => 'worker@test.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::Employee,
            'is_active' => true,
        ]);

        // 1. Assign Employee
        $response = $this->actingAs($this->admin)->post("/admin/objects/{$this->object->id}/employees", [
            'user_id' => $employeeUser->id,
            'position' => 'Builder Master',
            'daily_rate' => '150', // 150 USD daily
            'daily_rate_currency' => 'USD',
            'monthly_rate' => '0',
            'monthly_rate_currency' => 'UZS',
        ]);

        $response->assertRedirect();

        $employee = ObjectEmployee::where('object_id', $this->object->id)->where('user_id', $employeeUser->id)->first();
        $this->assertNotNull($employee);
        $this->assertEquals('Builder Master', $employee->position);
        $this->assertEquals(15000, $employee->daily_rate); // Saved as cents

        // 2. Delete Employee Assignment
        $responseDel = $this->actingAs($this->admin)->delete("/admin/objects/{$this->object->id}/employees/{$employee->id}");
        $responseDel->assertRedirect();

        $this->assertSoftDeleted('object_employees', [
            'id' => $employee->id
        ]);
    }
}
