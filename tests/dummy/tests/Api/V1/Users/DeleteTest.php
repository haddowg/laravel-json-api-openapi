<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace App\Tests\Api\V1\Users;

use App\Models\User;
use App\Tests\Api\V1\TestCase;

class DeleteTest extends TestCase
{
    public function test(): void
    {
        $user = User::factory()->createOne();

        $response = $this
            ->actingAs(User::factory()->createOne())
            ->jsonApi('users')
            ->delete(url('/api/v1/users', $user));

        $response->assertNotFound()->assertErrorStatus([
            'detail' => 'not found message',
            'status' => '404',
            'title' => 'Not Found',
        ]);
    }

    public function testUnauthenticated(): void
    {
        $user = User::factory()->createOne();

        $response = $this
            ->jsonApi('users')
            ->delete(url('/api/v1/users', $user));

        $response->assertNotFound()->assertErrorStatus([
                'detail' => 'not found message',
                'status' => '404',
                'title' => 'Not Found',
            ]);
    }
}
