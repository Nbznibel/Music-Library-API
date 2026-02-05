<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class FilterHelper
{
    /**
     * Apply filters to a query dynamically based on column type.
     *
     * @param  string|\Illuminate\Database\Eloquent\Model  $model
     * @param  array  $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function apply($model, array $filters, ?string $alias = null)
    {
        $instance = is_string($model) ? new $model : $model;
        $query = $instance->newQuery();

        $table = $instance->getTable();

        foreach ($filters as $field => $value) {
            if ($value === null || $value === '') {
                continue; // skip empty filters
            }

            if (!Schema::hasColumn($table, $field)) {
                continue; // skip invalid columns
            }

            $type = DB::getSchemaBuilder()->getColumnType($table, $field);

            switch ($type) {
                case 'string':
                case 'text':
                    $query->where($field, 'like', "%{$value}%");
                    break;

                case 'integer':
                case 'bigint':
                case 'smallint':
                    if (is_numeric($value)) {
                        $query->where($field, $value);
                    }
                    break;

                case 'float':
                case 'decimal':
                case 'double':
                    if (is_numeric($value)) {
                        $query->where($field, $value);
                    }
                    break;

                case 'boolean':
                    $query->where($field, (bool) $value);
                    break;

                case 'date':
                case 'datetime':
                case 'timestamp':
                    if (strtotime($value) !== false) {
                        $query->whereDate($field, date('Y-m-d', strtotime($value)));
                    }
                    break;

                default:
                    $query->where($field, $value);
            }
        }

        // base columns
        $columns = Schema::getColumnListing($table);
        $selects = array_map(fn($col) => "$table.$col", $columns);

        // only add subscriber_type if alias is passed AND column doesn't already exist
        if ($alias && !in_array('subscriber_type', $columns)) {
            $selects[] = DB::raw("'$alias' as subscriber_type");
        }

        return $query->select($selects);
    }



    public static function unionQueries(Builder $query1, Builder $query2, string $alias1 = 'q1', string $alias2 = 'q2')
    {
        $model1 = $query1->getModel();
        $model2 = $query2->getModel();
        $table1 = $model1->getTable();
        $table2 = $model2->getTable();

        $cols1 = Schema::getColumnListing($table1);
        $cols2 = Schema::getColumnListing($table2);
        $projection = array_values(array_unique(array_merge($cols1, $cols2)));
        sort($projection);

        $select1 = [];
        $select2 = [];

        foreach ($projection as $col) {
            $select1[] = Schema::hasColumn($table1, $col)
                ? DB::raw("$table1.$col as $col")
                : DB::raw("NULL as $col");

            $select2[] = Schema::hasColumn($table2, $col)
                ? DB::raw("$table2.$col as $col")
                : DB::raw("NULL as $col");
        }

        // 👇 add discriminator column explicitly in BOTH
        $select1[] = DB::raw("'$alias1' as subscriber_type");
        $select2[] = DB::raw("'$alias2' as subscriber_type");

        $q1 = $query1->select($select1);
        $q2 = $query2->select($select2);

        $union = $q1->unionAll($q2);

        // Wrap union so subscriber_type appears in result set
        return DB::query()->fromSub($union, 'combined');
    }


    public static function applyFilters($query, $filterBlocks, $tableName = null)
    {
        try {
            // Detect table name if not passed
            if (!$tableName) {
                $tableName = $query->getModel()->getTable();
            }

            $modelClass = get_class($query->getModel());
            $modelName = class_basename($modelClass);

            $query->where(function ($mainQ) use ($filterBlocks, $tableName, $modelName) {
                foreach ($filterBlocks as $block) {

                    $mainQ->orWhere(function ($blockQ) use ($block, $tableName, $modelName) {
                        foreach ($block['rows'] as $row) {


                            if ((
                                isset($row['field'], $row['value']) &&
                                $row['field'] === 'type' &&
                                strtolower($row['value']) === 'individual' &&
                                strtolower($modelName) === 'person'
                            ) || (
                                isset($row['field'], $row['value']) &&
                                $row['field'] === 'type' &&
                                strtolower($row['value']) === 'moral' &&
                                strtolower($modelName) === 'moralperson'
                            )) {
                                // This means the block shouldn't return any result
                                // so we force an impossible condition
                                $blockQ->orWhereRaw('1 = 0');
                                // Skip processing this block further
                                continue 1; // skip this block entirely
                            }


                            $method = $row['linkOperator'] === 'or' ? 'orWhere' : 'where';

                            // ✅ Check if column exists
                            if (!Schema::hasColumn($tableName, $row['field'])) {
                                // return $query->whereRaw('1 = 0');
                                throw "Column not found";
                            }

                            // ✅ Check if value empty
                            if (!isset($row['value']) || $row['value'] === "") {
                                // return $query->whereRaw('1 = 0');
                                throw "Column not found";
                            }

                            switch ($row['operator']) {
                                case 'equal_to':
                                    $blockQ->$method($row['field'], '=', $row['value']);
                                    break;
                                case 'not_equal_to':
                                    $blockQ->$method($row['field'], '!=', $row['value']);
                                    break;
                                case 'like':
                                    $blockQ->$method($row['field'], 'like', "%{$row['value']}%");
                                    break;
                                case 'gt':
                                    $blockQ->$method($row['field'], '>', $row['value']);
                                    break;
                                case 'lt':
                                    $blockQ->$method($row['field'], '<', $row['value']);
                                    break;
                                case 'gte':
                                    $blockQ->$method($row['field'], '>=', $row['value']);
                                    break;
                                case 'lte':
                                    $blockQ->$method($row['field'], '<=', $row['value']);
                                    break;
                            }
                        }
                    });
                }
            });

            return $query;
        } catch (\Throwable $e) {
            Log::info(["error advanced search : " => $e->getMessage()]);
            // any exception → empty
            return $query->whereRaw('1 = 0');
        }
    }
}
