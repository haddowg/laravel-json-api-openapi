<?php

/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = ['name'];

    public function posts(): MorphToMany
    {
        return $this
            ->morphedByMany(Post::class, 'taggable')
            ->withPivot('approved');
    }

    public function videos(): MorphToMany
    {
        return $this
            ->morphedByMany(Video::class, 'taggable')
            ->withPivot('approved');
    }
}
