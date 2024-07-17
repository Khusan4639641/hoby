<?php

namespace App\Scopes;

use App\Models\V3\RoleV3;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BaseCollectCuratorScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param Builder $builder
     * @param Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $curator_roles_ids = RoleV3::select("id")
            ->whereName("debt-collect-curator")
            ->orWhere("name", "debt-collect-curator-extended")
            ->pluck("id")
            ->toArray()
        ;
        $builder->whereIn("role_id", $curator_roles_ids);
    }
}
