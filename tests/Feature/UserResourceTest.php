<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserResourceTest extends TestCase
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

    public function test_can_list_users(): void
    {
        $users = User::factory()->count(3)->create();

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords($users);
    }

    public function test_can_create_user(): void
    {
        $role = Role::findByName('Soporte');

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'password123',
                'status' => UserStatus::Active->value,
                'roles' => [$role->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
        ]);

        $user = User::where('email', 'newuser@example.com')->first();
        $this->assertTrue($user->hasRole('Soporte'));
    }

    public function test_can_edit_user(): void
    {
        $user = User::factory()->create();

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm([
                'name' => 'Updated Name',
                'status' => UserStatus::Inactive->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'status' => UserStatus::Inactive->value,
        ]);
    }

    public function test_can_assign_role_to_user(): void
    {
        $user = User::factory()->create();
        $role = Role::findByName('Mantenimiento');

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm([
                'roles' => [$role->id],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();
        $this->assertTrue($user->hasRole('Mantenimiento'));
    }

    public function test_can_filter_users_by_status(): void
    {
        $activeUser = User::factory()->create(['status' => UserStatus::Active]);
        $inactiveUser = User::factory()->create(['status' => UserStatus::Inactive]);

        Livewire::test(ListUsers::class)
            ->filterTable('status', UserStatus::Active->value)
            ->assertCanSeeTableRecords([$activeUser])
            ->assertCanNotSeeTableRecords([$inactiveUser]);
    }

    public function test_can_search_users(): void
    {
        $user1 = User::factory()->create(['name' => 'Juan Perez']);
        $user2 = User::factory()->create(['name' => 'Maria Garcia']);

        Livewire::test(ListUsers::class)
            ->searchTable('Juan')
            ->assertCanSeeTableRecords([$user1])
            ->assertCanNotSeeTableRecords([$user2]);
    }

    public function test_user_requires_name(): void
    {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => '',
                'email' => 'test@example.com',
                'password' => 'password123',
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    }

    public function test_user_requires_email(): void
    {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Test User',
                'email' => '',
                'password' => 'password123',
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => 'required']);
    }

    public function test_user_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Test User',
                'email' => 'existing@example.com',
                'password' => 'password123',
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => 'unique']);
    }

    public function test_password_required_on_create(): void
    {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => '',
            ])
            ->call('create')
            ->assertHasFormErrors(['password' => 'required']);
    }

    public function test_password_optional_on_edit(): void
    {
        $user = User::factory()->create();
        $originalPassword = $user->password;

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm([
                'name' => 'Updated Name',
                'password' => '',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();
        $this->assertEquals($originalPassword, $user->password);
    }
}
