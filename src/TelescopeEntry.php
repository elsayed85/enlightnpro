<?php

namespace Enlightn\EnlightnPro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TelescopeEntry extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'telescope_entries';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = null;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'content' => 'json',
    ];

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'uuid';

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Prevent Eloquent from overriding uuid with `lastInsertId`.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Get the current connection name for the model.
     *
     * @return string
     */
    public function getConnectionName()
    {
        return config('telescope.storage.database.connection');
    }

    /**
     * Scope a query to select the count of total entries.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSelectTotalEntries($query)
    {
        return $query->selectSub('count(*)', 'totalEntries');
    }

    /**
     * Scope a query to add content params to the select clause.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $params
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSelectParams($query, array $params)
    {
        return $query->select(collect($params)->map(function ($param) {
            return "content->{$param} as {$param}";
        })->toArray());
    }

    /**
     * Scope a query to add content params to the select clause.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $params
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAddParams($query, array $params)
    {
        collect($params)->each(function ($param) use ($query) {
            $query->addSelect("content->{$param} as {$param}");
        });

        return $query;
    }

    /**
     * Scope a query to add content params to the select clause.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $param
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereParam($query, string $param, $operator = null, $value = null)
    {
        return $query->where("content->{$param}", ...array_slice(func_get_args(), 2));
    }

    /**
     * Scope a query to order by total entries.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $colNumbers
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGroupByColNumbers($query, array $colNumbers)
    {
        collect($colNumbers)->each(function ($colNumber) use ($query) {
            $query->groupBy(DB::raw((string) $colNumber));
        });

        return $query;
    }

    /**
     * Scope a query to order by total entries.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByTotalEntries($query)
    {
        return $query->orderByDesc('totalEntries');
    }
}
