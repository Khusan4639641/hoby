<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourtRegion extends Model
{
    public const IS_NOT_VISIBLE = 0;
    public const IS_VISIBLE     = 1;


    protected $fillable = [
        'name',
        'is_visible',
    ];


    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;


    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s.u';


    protected $orderBy = 'id';
    protected $orderDirection = 'ASC';

    public function scopeOrdered($query)
    {
        if ($this->orderBy)
        {
            return $query->orderBy($this->orderBy, $this->orderDirection);
        }

        return $query;
    }

    public function scopeGetOrdered($query)
    {
        return $this->scopeOrdered($query)->get();
    }
}
