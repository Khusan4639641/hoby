<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table = 'menu';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "id",
        "route",
        "permission",
        "position",
        "type",
        "name",
        "sort",
        "parent_id",
        "hash",
        "user_status",
        "denied_affiliate",
        "attr",
        "class",
        "params"
    ];

    public function children(){
        return $this->hasMany( Menu::class, 'parent_id')->orderBy('sort');
    }
}
