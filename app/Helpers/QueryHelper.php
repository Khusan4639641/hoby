<?php


namespace App\Helpers;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class QueryHelper
{
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    private static function initializeValues(string $key)
    {
        $result = [];
        $buf = explode("__", $key);
        $default = '';
        switch (count($buf)) {
            case 2:
                array_push($result, $buf[0], $buf[1], $default);
                break;
            case 3:
                array_push($result, $buf[0], $buf[1], $buf[2]);
                break;
            default:
                array_push($result, $buf[0], $default, $default);
                break;
        }
        return $result;
    }

    public function constructQuery($params)
    {
        $columns = Schema::getColumnListing($this->model->getTable());
        $query = $this->model::query();

        foreach ($params as $key => $value) {
            if(is_null($value) || $value === '') continue;

            list($key, $operation, $condition) = self::initializeValues($key);
            $keys = explode('.',$key);
            $column = $keys[count($keys) - 1];
            if (in_array($column, $columns)) {
                switch ($operation) {
                    case "loe": // <=
                        if (!is_array($value)) {
                            if ($condition == 'or')
                                $query->orWhere($key, '<=', $value);
                            else
                                $query->where($key, '<=', $value);
                        }
                        break;
                    case "moe": // >=
                        if (!is_array($value)) {
                            if ($condition == 'or')
                                $query->orWhere($key, '>=', $value);
                            else
                                $query->where($key, '>=', $value);
                        }
                        break;
                    case "less": // <
                        if (!is_array($value)) {
                            if ($condition == 'or')
                                $query->orWhere($key, '<', $value);
                            else
                                $query->where($key, '<', $value);
                        }
                        break;
                    case "more": // >
                        if (!is_array($value)) {
                            if ($condition == 'or')
                                $query->orWhere($key, '>', $value);
                            else
                                $query->where($key, '>', $value);
                        }
                        break;
                    case "not": // ! <>
                        if (!is_array($value))
                            $query->where($key, '!=', $value);
                        elseif (is_array($value))
                            $query->whereNotIn($key, $value);
                        break;
                    case "like":
                        if (!is_array($value)) {
                            if ($condition == 'or')
                                $query->orWhere($key, 'like', '%' . $value . '%');
                            else
                                $query->where($key, 'like', '%' . $value . '%');
                        }
                        break;
                    case "between":
                        if (is_array($value))
                            $query->between($key, $value);
                        break;
                    default:
                        if (is_array($value))
                            $query->whereIn($key, $value);
                        elseif ($condition == 'or')
                            $query->orWhere($key, $value);
                        else
                            $query->where($key, $value);
                        break;
                }
            } else if (strpos($key, '|') > 0) {
                list($model, $column) = explode('|', $key);
                $query->whereHas($model, function (Builder $query) use ($value, $column, $operation) {
                    switch ($operation) {
                        case 'like':
                            $query->where($column, 'like', "%$value%");
                            break;
                        default:
                            $query->where($column, $value);
                    }
                });
            }
        }
        return $query;
    }
}
