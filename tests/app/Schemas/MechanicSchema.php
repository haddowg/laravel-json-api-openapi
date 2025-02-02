<?php

/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace App\Schemas;

use App\Models\Mechanic;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\HasOne;
use LaravelJsonApi\Eloquent\Fields\Relations\HasOneThrough;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Schema;

class MechanicSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = Mechanic::class;

    public function fields(): array
    {
        return [
            ID::make(),
            DateTime::make('createdAt')->readOnly(),
            Str::make('name'),
            HasOne::make('car'),
            HasOneThrough::make('carOwner'),
            DateTime::make('updatedAt')->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
        ];
    }

    public function pagination(): ?Paginator
    {
        return null;
    }
}
