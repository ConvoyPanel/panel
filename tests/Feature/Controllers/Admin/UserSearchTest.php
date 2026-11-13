<?php

use Convoy\Models\User;

// The admin user search behind the Create Server user picker.
function searchUsers(string $query): array
{
    return test()->actingAs(User::where('email', 'admin@panel.test')->sole())
        ->getJson('/api/admin/users?filter[*]='.urlencode($query))
        ->assertOk()
        ->json('data.*.email');
}

beforeEach(function () {
    User::factory()->create(['root_admin' => true, 'name' => 'Admin', 'email' => 'admin@panel.test']);
    User::factory()->create(['name' => 'Jane Customer', 'email' => 'jane@customer.example']);
    User::factory()->create(['name' => 'Bob Other', 'email' => 'bob@other.example']);
});

it('finds a user by part of their email address', function () {
    expect(searchUsers('customer.exa'))->toBe(['jane@customer.example']);
});

it('finds a user by their name in any case', function () {
    expect(searchUsers('Jane'))->toBe(['jane@customer.example'])
        ->and(searchUsers('jANE cus'))->toBe(['jane@customer.example']);
});

it('treats LIKE wildcards in the search as plain text', function () {
    expect(searchUsers('%'))->toBe([])
        ->and(searchUsers('_'))->toBe([]);
});
