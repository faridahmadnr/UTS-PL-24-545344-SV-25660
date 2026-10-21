<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name'     => 'Farid Ahmad',
            'email'    => 'farid@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['access_token', 'token_type', 'user']);
    }

    public function test_user_can_login_and_receive_token(): void
    {
        $user = User::factory()->create([
            'email'    => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'user@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type']);
    }

    public function test_unauthenticated_request_is_rejected_with_401(): void
    {
        $response = $this->getJson('/api/books');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_books(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Book::create([
            'title'       => 'Clean Code',
            'author'      => 'Robert C. Martin',
            'year'        => 2008,
            'description' => 'A handbook of agile software craftsmanship',
        ]);

        $response = $this->getJson('/api/books');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_authenticated_user_can_create_book(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = [
            'title'       => 'The Pragmatic Programmer',
            'author'      => 'Andy Hunt & Dave Thomas',
            'year'        => 1999,
            'description' => 'From journeyman to master',
        ];

        $response = $this->postJson('/api/books', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'The Pragmatic Programmer');

        $this->assertDatabaseHas('books', ['title' => 'The Pragmatic Programmer']);
    }

    public function test_create_book_validation_fails_with_422(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Missing required fields
        $response = $this->postJson('/api/books', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'author', 'year']);
    }

    public function test_authenticated_user_can_view_single_book(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $book = Book::create([
            'title'  => 'Refactoring',
            'author' => 'Martin Fowler',
            'year'   => 1999,
        ]);

        $response = $this->getJson('/api/books/' . $book->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Refactoring');
    }

    public function test_view_book_returns_404_if_not_found(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/books/9999');

        $response->assertStatus(404);
    }

    public function test_authenticated_user_can_update_book(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $book = Book::create([
            'title'  => 'Old Title',
            'author' => 'Author A',
            'year'   => 2020,
        ]);

        $response = $this->putJson('/api/books/' . $book->id, [
            'title'  => 'Updated Title',
            'author' => 'Author A',
            'year'   => 2021,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Title');
    }

    public function test_authenticated_user_can_delete_book(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $book = Book::create([
            'title'  => 'Book to delete',
            'author' => 'Author B',
            'year'   => 2022,
        ]);

        $response = $this->deleteJson('/api/books/' . $book->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }
}
