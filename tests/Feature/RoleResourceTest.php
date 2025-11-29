<?php

namespace Tests\Feature;

use App\Filament\Resources\RoleResource\Pages\CreateRole;
use App\Filament\Resources\RoleResource\Pages\EditRole;
use App\Filament\Resources\RoleResource\Pages\ListRoles;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');
        $this->actingAs($this->admin);
    }

    public function test_can_list_roles(): void
    {
        $roles = Role::all();

        Livewire::test(ListRoles::class)
            ->assertCanSeeTableRecords($roles);
    }

    public function test_can_create_role(): void
    {
        Livewire::test(CreateRole::class)
            ->fillForm([
                'name' => 'Nuevo Rol',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('roles', [
            'name' => 'Nuevo Rol',
        ]);
    }

    public function test_can_edit_role(): void
    {
        $role = Role::create(['name' => 'Test Role', 'guard_name' => 'web']);

        Livewire::test(EditRole::class, ['record' => $role->id])
            ->fillForm([
                'name' => 'Updated Role',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'Updated Role',
        ]);
    }

    public function test_can_assign_permissions_to_role(): void
    {
        $role = Role::create(['name' => 'Test Role', 'guard_name' => 'web']);
        $permission = Permission::findByName('view_users');

        Livewire::test(EditRole::class, ['record' => $role->id])
            ->fillForm([
                'permissions' => [$permission->id],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('view_users'));
    }

    public function test_role_requires_name(): void
    {
        Livewire::test(CreateRole::class)
            ->fillForm([
                'name' => '',
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    }

    public function test_role_name_must_be_unique(): void
    {
        Role::create(['name' => 'Existing Role', 'guard_name' => 'web']);

        Livewire::test(CreateRole::class)
            ->fillForm([
                'name' => 'Existing Role',
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'unique']);
    }

    public function test_can_search_roles(): void
    {
        $role1 = Role::create(['name' => 'Developer', 'guard_name' => 'web']);
        $role2 = Role::create(['name' => 'Designer', 'guard_name' => 'web']);

        Livewire::test(ListRoles::class)
            ->searchTable('Developer')
            ->assertCanSeeTableRecords([$role1])
            ->assertCanNotSeeTableRecords([$role2]);
    }
}
