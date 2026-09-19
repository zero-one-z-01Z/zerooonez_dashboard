<?php

namespace App\Dashboard\Services;

use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use function Laravel\Prompts\alert;

/**
 * Class DataTableService
 *
 * This service handles the processing of Queries and Data for the Front end Library Datatable, including filtering, sorting, and pagination.
 */
class DataTableService
{
    /**
     * The Query Builder instance.
     *
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $query;

    /**
     * The Request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request,$queryColumns;
    protected $stop_sort = false,$sort_by = null,$sort_dir = null;



    /**
     * The parsed columns.
     *
     * @var array
     */
//    private $parsedColumns = [];
    private $translatable = [];

    /**
     * The filters closure.
     */
    private ?Closure $filtersClosure;

    /**
     * DataTableService constructor.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  The query builder instance.
     * @param  \Illuminate\Http\Request  $request  The request instance.
     * @param  Closure|null  $filters  The filters closure where you define filters, which can be null.
     */
    public function __construct(\Illuminate\Http\Request $request, \Illuminate\Database\Eloquent\Builder $query, ?Closure $filters = null,$stop_sort = false,$queryColumns = null,$sort_by = null,$sort_dir = null)
    {
        //Validate the request contains the required fields
        $this->validateRequest($request);

        //Parse the columns

//        $this->parseColumns($request->columns);

        $this->translatable = $query->getModel()->translatable ?? [];
        if(!isset($request->order)){
            $stop_sort = true;
        }
        $this->stop_sort = $stop_sort;
        $this->sort_by = $sort_by;
        $this->sort_dir = $sort_dir;
        //Set the request and query
        $this->request = $request;
        $this->queryColumns = $queryColumns;


        //Set the query
        $this->query = $query;

        //Set the filters closure
        $this->filtersClosure = $filters;
    }

    /**
     * Validate the request parameters.
     *
     * @param  \Illuminate\Http\Request  $request  The request instance.
     *
     * @throws \Illuminate\Validation\ValidationException If validation fails.
     */
    protected function validateRequest(\Illuminate\Http\Request $request): void
    {
        $rules = [
            'draw' => 'required|integer',
            'start' => 'required|integer',
            'length' => 'required|integer',
            'order' => 'nullable|array',
            'order.*.column' => 'required|integer',
            'order.*.dir' => 'required|string|in:asc,desc',
            'columns' => 'required|array',
            'search.value' => 'nullable|string',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Apply filters to the query based on the request parameters.
     *
     * @param  array  $filterableColumns  The columns that can be Searched.
     * @return self The current instance of the DataTableService class.
     */
    public function applyFilters(array $filterableColumns): self
    {

        // if Filters Closure is set, execute it
        if ($this->filtersClosure) {
            call_user_func($this->filtersClosure, $this->query);
        }

        //apply search
        $this->applySearch($filterableColumns);

        //apply other filters
        $this->applyExternalFilters();

        return $this;
    }

    protected function applySearch(array $filterableColumns): void
    {
        if (! empty($this->request->input('search.value'))) {
            $searchValue = $this->request->input('search.value');
            $this->query->where(function ($q) use ($filterableColumns, $searchValue) {
                foreach ($filterableColumns as $column) {
                    if (in_array($column, $this->translatable)) {
                        $q->orWhere(function ($subQuery) use ($column, $searchValue) {
                            $subQuery->where("{$column}_en", 'LIKE', "%{$searchValue}%")
                                ->orWhere("{$column}_ar", 'LIKE', "%{$searchValue}%");
                        });
                    }
                    elseif (strpos($column, '.') !== false) {
                        $parts = explode('.', $column);
                        $relationColumn = array_pop($parts);       // last segment = column
                        $relations = $parts;                        // remaining = relation chain

                        $q->orWhereHas($relations[0], function ($q2) use ($relations, $relationColumn, $searchValue) {
                            $q2->withoutGlobalScope('insurance_check');
                            $this->applyNestedWhereHas($q2, array_slice($relations, 1), $relationColumn, $searchValue);
                        });
                    } else {
                        $q->orWhere($column, 'LIKE', "%{$searchValue}%");
                    }
                }
            });
        }
    }

    private function applyNestedWhereHas($query, array $relations, string $column, string $searchValue): void
    {
        if (empty($relations)) {
            $table = $query->getModel()->getTable();
            $query->where("{$table}.{$column}", 'LIKE', "%{$searchValue}%");
        } else {
            $query->whereHas($relations[0], function ($q) use ($relations, $column, $searchValue) {
                $q->withoutGlobalScope('insurance_check'); // ✅ only for nested user queries
                $this->applyNestedWhereHas($q, array_slice($relations, 1), $column, $searchValue);
            });
        }
    }

    protected function applyExternalFilters(): void
    {
        // Get the external filters from the request (passed as associative array)
        $externalFilters = $this->request->input('filters', []);

        foreach ($externalFilters as $column => $value) {

            if ($column == 'date_column') {
                // if (Schema::hasColumn($this->query->getModel()->getTable(), $value)) {
                // Apply filter to the query
                if (! is_null($value)) {
                    [$startDate, $endDate] = explode(' - ', $externalFilters['date_range']);
                    // Convert to Carbon instances
                    $start = Carbon::createFromFormat('m/d/Y', $startDate)->startOfDay();
                    $end = Carbon::createFromFormat('m/d/Y', $endDate)->endOfDay();

                    // Apply date range filter
                    $this->query->whereBetween($value, [$start, $end]);
                }
                // }
            } else {
                if (strpos($column, '.') !== false) {
                    [$relation, $relationColumn] = explode('.', $column);
                    $this->query->whereHas($relation, function ($q) use ($relationColumn, $value) {
                        $q->where($relationColumn, 'LIKE', '%'.$value.'%');
                    });
                } else {
                    if ($column !== 'date_range') {
                        // Apply filter to the query
                        if (! is_null($value)) {
                            $this->query->where($column, $value);
                        }
                    }
                }
            }
        }
    }

    public function applySorting(): void
    {
        if(!$this->stop_sort){
            $order = $this->request->order;
            $columns = $this->request->columns;
            $orderColumnIndex = $order[0]['column'];
            $direction = $order[0]['dir'];

            $column = $columns[(int) $orderColumnIndex]['data'] ?? null;
            $queryColumns = in_array($column, $this->queryColumns);
            if($column){
                if ($queryColumns) {
                    $parsedOrderColumn = $this->parseColumn($column);
                    if (! empty($parsedOrderColumn['relationships'])) {
                        $this->applySortingWithRelationship($this->query, $parsedOrderColumn, $direction);
                    } elseif ($parsedOrderColumn['language']) {
                        $this->applySortingWithLanguage($this->query, $parsedOrderColumn, $direction);
                    } else {
                        $this->query->orderBy($parsedOrderColumn['attribute'], $direction);
                    }
                }
            }
            if($orderColumnIndex == 0){
                $this->query->orderBy($this->sort_by??'created_at', $this->sort_dir??'desc');
            }


        }
    }

    private function applySortingWithRelationship(Builder $query, array $parsedColumn, string $direction): void
    {
        $relationships = $parsedColumn['relationships'];
        $attribute = $parsedColumn['attribute'];
        $language = $parsedColumn['language'];

        $query->orderBy(function ($subQuery) use ($query, $relationships, $attribute, $language) {
            $lastRelationship = end($relationships);
            $mainTable = $query->getQuery()->from;

            $relationshipInstance = $query->getModel()->{$lastRelationship}()->getRelated();
            $lastRelationshipTable = $relationshipInstance->getTable();

            if ($language) {
                $subQuery->select($attribute.'->'.$language);
            } else {
                $subQuery->select($attribute);
            }

            $subQuery->from($lastRelationshipTable)
                ->whereColumn(
                    $lastRelationshipTable.'.id',
                    $mainTable.'.'.Str::singular($lastRelationshipTable).'_id'
                );

            // Handle nested relationships
            $tempRelation = $lastRelationshipTable;

            for ($i = count($relationships) - 2; $i >= 0; $i--) {
                $currentRelation = $relationships[$i];
                $subQuery->join(
                    $currentRelation,
                    $currentRelation.'.id',
                    '=',
                    $tempRelation.'.'.Str::singular($currentRelation).'_id'
                );
                $tempRelation = $currentRelation;
            }

            return $subQuery;
        }, $direction);
    }

    private function applySortingWithLanguage(Builder $query, array $parsedColumn, string $direction): void
    {
        $columnName = $parsedColumn['attribute'].'_'.$parsedColumn['language'];
        $query->orderBy($columnName, $direction);
    }

    public function applyPagination(array $filterableColumns): array
    {
        // Handle pagination
        $start = $this->request->input('start', 0);        // Start record index
        $length = $this->request->input('length', 10);     // Number of records per page

        $totalRecords = $this->query->count();             // Total records before filtering
        $this->query->skip($start)->take($length);         // Apply pagination

        return [$this->query->get()->makeVisible($filterableColumns), $totalRecords];
    }

    public function getData(array $filterableColumns): array
    {
        // Apply filtering, sorting, and pagination
        $this->applyFilters($filterableColumns)
            ->applySorting();

        [$data, $totalRecords] = $this->applyPagination($filterableColumns);

        return [
            'draw' => intval($this->request->input('draw')),  // Draw counter
            'recordsTotal' => $totalRecords,                 // Total records before filtering
            'recordsFiltered' => $totalRecords,              // Total records after filtering
            'data' => $data,                                 // Paginated, filtered data
        ];
    }

//    private function parseColumns(array $columns): void
//    {
//        foreach ($columns as $column) {
//            if (isset($column['data']) && $column['data'] !== null) {
//                if($column['data']){
//                    $this->parsedColumns[] = $this->parseColumn($column['data']);
//                }
//            }
//        }
//    }

    private function parseColumn(string $column): array
    {
        $parts = explode('.', $column);
        $lastIndex = count($parts) - 1;

        $parsed = [
            'original' => $column,
            'relationships' => [],
            'attribute' => '',
            'language' => null,
        ];

        // Check if the last part is a language specifier
        if (in_array(end($parts), ['ar', 'en'])) {
            $parsed['language'] = array_pop($parts);
            $lastIndex--;
        }

        // If there are still multiple parts, treat all but the last as relationships
        if ($lastIndex > 0) {
            $parsed['relationships'] = array_slice($parts, 0, $lastIndex);
            $parsed['attribute'] = $parts[$lastIndex];
        } else {
            // If there's only one part left (or originally), it's the attribute
            $parsed['attribute'] = $parts[0];
        }

        return $parsed;
    }


}
