<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_user_can_sign_in_with_username_and_sign_out(): void
    {
        $user = User::factory()->create([
            'username' => 'operations',
            'password' => 'secret-password',
        ]);

        $this->get(route('login'))->assertOk();

        $this->post(route('login.store'), [
            'username' => 'operations',
            'password' => 'secret-password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_invalid_credentials_do_not_authenticate(): void
    {
        User::factory()->create([
            'username' => 'operations',
            'password' => 'secret-password',
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'username' => 'operations',
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }
}
