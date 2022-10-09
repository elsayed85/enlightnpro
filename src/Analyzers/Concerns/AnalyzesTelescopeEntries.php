<?php

namespace Enlightn\EnlightnPro\Analyzers\Concerns;

use Illuminate\Database\QueryException;

trait AnalyzesTelescopeEntries
{
    /**
     * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
     */
    protected function executeQuery($query)
    {
        try {
            return $query->get();
        } catch (QueryException $e) {
            // Some database drivers don't support JSON queries unless the data type is json.
            $this->setExceptionMessage('For this analyzer to work on your database driver, you must change your '
                .'content column data type in your "telescope_entries" table to json.');

            return collect();
        }
    }
}
