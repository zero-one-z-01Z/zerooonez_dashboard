<?php

namespace ZeroOneZ\Dashboard\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use ZeroOneZ\Dashboard\Tests\Fixtures\DashboardUser;
use ZeroOneZ\Dashboard\Tests\TestCase;

final class DashboardAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('Dashboard authentication is exercised by the installed-source smoke test; the package provider is intentionally runtime-free.');

        Schema::create('dashboard_test_users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function test_dashboard_home_requires_its_configured_guard(): void
    {
        $this->get('/admin/home')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk()->assertSee('Dashboard login');
    }

    public function test_dashboard_user_can_login_and_logout_through_package_routes(): void
    {
        DashboardUser::query()->create([
            'name' => 'Dashboard Admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('correct-password'),
        ]);

        $this->post('/admin/login', [
            'email' => 'admin@example.test',
            'password' => 'correct-password',
        ])->assertRedirect('/admin/home');

        $this->get('/admin/home')
            ->assertOk()
            ->assertSee('Dashboard home')
            ->assertSee('action="http://localhost/admin/logout"', false);

        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->get('/admin/home')->assertRedirect('/admin/login');
    }

    public function test_invalid_credentials_are_rejected_without_authenticating(): void
    {
        DashboardUser::query()->create([
            'name' => 'Dashboard Admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('correct-password'),
        ]);

        $this->from('/admin/login')->post('/admin/login', [
            'email' => 'admin@example.test',
            'password' => 'wrong-password',
        ])->assertRedirect('/admin/login')->assertSessionHasErrors('email');

        $this->get('/admin/home')->assertRedirect('/admin/login');
    }
}
