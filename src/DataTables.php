<?php

namespace ACFBentveld\DataTables;

use Request;
use Schema;
use ACFBentveld\DataTables\DataTablesException;
use ACFBentveld\DataTables\DataTablesQueryBuilders;
use Illuminate\Support\Facades\DB;

/**
 * An laravel jquery datatables package
 *
 * @author Wim Pruiksma
 */
class DataTables extends DataTablesQueryBuilders
{
    /**
     * The collectiosn model
     *
     * @var mixed
     * @author Wim Pruiksma
     */
    protected $model;

    /**
     * Set to true to enable caching
     *
     * @var boolean
     */
    protected $remember = false;

    /**
     * Set the keys for encrypting
     *
     * @var array
     * @author Wim Pruiksma
     */
    protected $encrypt;

    /**
     * Set the search keys
     *
     * @var array
     * @author Wim Pruiksma
     */
    protected $search;

    /**
     * The database columns
     *
     * @var mixed
     * @author Wim Pruiksma
     */
    protected $columns;

    /**
     * The database table name
     *
     * @var string
     * @author Wim Pruiksma
     */
    protected $table;

    /**
     * Searchable keys
     *
     * @var array
     * @author Wim Pruiksma
     */
    protected $searchable;

    protected $distinctColumn = null;

    protected $start;

    protected $length;
    
    protected $order;
    
    /**
     * Cursor to seek sql
     *
     * @var array
     * @author Luis Macayo
     */
    protected $draw = 0;

    /**
     * Query to execute
     *
     * @var array
     * @author Luis Macayo
     */
    protected $sql = null;

    /**
     * Column used for keyset (cursor) pagination.
     * When set, replaces OFFSET with WHERE column > cursor.
     *
     * @var string|null
     */
    protected $cursorColumn = null;
    
    /**
     * The table ID
     *
     * @var mixed
     * @author Wim Pruiksma
     */
    protected $tableid = false;

    /**
     * If datables has searchable keys
     *
     * @var boolean
     * @author Wim Pruiksma
     */
    protected $hasSearchable = false;

    /**
     * Set the class and create a new model instance
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return $this
     * @throws DataTablesException
     * @author Wim Pruiksma
     */
    public function model($model)
    {
        $this->instanceCheck($model);
        $this->build();
        $this->model   = $model;
        $this->table   = $this->model->getTable();
        $this->columns = Schema::getColumnListing($this->table);
        return $this;
    }

    /**
     * The collect method
     * Really bad for performance
     *
     * @param \Illuminate\Database\Eloquent\Collection $collection
     * @return $this
     * @throws DataTablesException
     * @author Wim Pruiksma
     */
    public function collect($collection)
    {
        $this->instanceCheck($collection);
        $allowedID     = $collection->pluck('id');
        $first         = $collection->first();
        $empty         = $first ? new $first : null;
        $this->build();
        $this->model   = $first ? $first::query()->whereIn('id', $allowedID) : null;
        $this->table   = $first ? $empty->getTable() : null;
        $this->columns = Schema::getColumnListing($this->table);
        return $this;
    }

    /**
     * The query method
     * Get results from query sql
     *
     * @param mixed $query
     * @return $this
     * @throws DataTablesException
     * @author Luis Macayo
     */
    public function query($query)
    {
        $this->sql = $query;
        $this->build();

        // Obtener columnas desde la consulta si es posible
        if ($query instanceof \Illuminate\Database\Eloquent\Builder) {
            $this->model = $query;
            $this->table = $query->getModel()->getTable();
            $this->columns = Schema::getColumnListing($this->table);
        } else {
            // Para SQL crudo, intentar inferir columnas o establecer un array vacío
            $this->model = null;
            $this->table = null;
            $this->columns = [];
        }

        return $this;
    }

    /**
     * Build the collection for the datatable
     *
     * @return $this
     * @author Wim Pruiksma
     */
    public function build()
    {
        if (Request::has('draw')) {
            $this->response = 'json';
        
            $this->draw   = Request::get('draw');
            $this->column = $this->filterColumns(Request::get('columns'));
            $col = $this->column[Request::get('order')[0]['column']];

            foreach(Request::get('order') as $order) {
                $col = $this->column[$order['column']];
                $this->order[]  = [
                    'column' => isset($col['name']) ? $col['name'] : $col['data'],
                    'dir' => $order['dir']
                ];
            }

            $this->start  = (int) Request::get('start', 0);
            $this->length = (int) Request::get('length', 10);
            $this->search = Request::has('search') && Request::get('search')['value'] ? Request::get('search') : null;
        }
        return $this;
    }

    /**
     * Filter columns on nullable results
     * Remove them from the arrya
     *
     * @param array $columns
     */
    private function filterColumns(array $columns = null)
    {
        if(!$columns){
            return [];
        }
        $fields = [];
        foreach($columns as $key => $column){
            if( $column['data'] ||  $column['name']){
                $fields[] = $column;
            }
        }
        return $fields;
    }

    /**
     * Check the instance of the given model or collection
     *
     * @param type $instance
     * @return boolean
     * @throws DataTablesException
     * @author Wim Pruiksma
     */
    protected function instanceCheck($instance)
    {
        if (
            !$instance instanceof \Illuminate\Database\Eloquent\Model &&
            !$instance instanceof \Illuminate\Database\Eloquent\Collection &&
            !$instance instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany &&
            !$instance instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo &&
            !$instance instanceof \Illuminate\Database\Eloquent\Relations\HasMany &&
            !$instance instanceof \Illuminate\Database\Eloquent\Relations\HasOne
        ) {
            throw new DataTablesException('Model must be an instance of Illuminate\Database\Eloquent\Model or an instance of Illuminate\Database\Eloquent\Collection');
        }
        return true;
    }

    /**
     * Enable keyset (cursor) pagination to avoid deep OFFSET scans.
     * Replaces LIMIT x OFFSET n with WHERE column > :cursor LIMIT n.
     * The response JSON will include a "nextCursor" key.
     *
     * @param string $column  Indexed column to seek on (default: 'id')
     * @return $this
     */
    public function cursorPaginate(string $column = 'id'): self
    {
        $this->cursorColumn = $column;
        return $this;
    }

    /**
     * Enable caching
     * Check if the cache exists.
     * If the cache exists, stop executing and return the json
     *
     * @return $this
     * @deprecated since version 2.0.17
     */
    public function remember(string $name, int $minutes = 60)
    {
        $this->remember = true;
        $this->cacheName = "$name";
        $this->cacheFor = $minutes;
        return $this;
    }

    /**
     * Set the searchkeys
     *
     * @param mixed $searchkeys
     * @return $this
     */
    public function searchable(... $searchkeys)
    {
        $last = [];
        foreach($searchkeys as $key => $value){
            if(str_contains($value, '.')){
                $last[] = $value;
            }else{
                $this->searchable[] = $value;
            }
        }
        $this->searchable = array_merge($this->searchable, $last);
        $this->hasSearchable = true;
        return $this;
    }

    /**
     * Run the query
     * return as json string
     * @author Wim Pruiksma
     */
    public function get()
    {
        if(!Request::has('draw')
            || ($this->tableid !== false
            && Request::has("table")
            && Request::get('table') !== $this->tableid) ){
            return false;
        }
        
        $data = $this->execute();
        $data['draw'] = $this->draw;

        $response = response()->json($data);

        foreach($response->headers->all() as $header => $value){
            $set = join(',', $value);
            header("$header: $set");
        }
        echo $response->getContent();
        exit;
    }

    protected function execute()
    {
        $count = 0;
        $filteredCount = 0;

        if ($this->sql !== null) {
            // Handle raw SQL or query builder
            if ($this->sql instanceof \Illuminate\Database\Eloquent\Builder) {
                // Para Eloquent Builder
                $countQuery = clone $this->sql;
                
                // Manejar distinct en el conteo
                // if ($this->distinctColumn) {
                //     $count = $countQuery->distinct($this->distinctColumn)->count($this->distinctColumn);
                // } else {
                // }
                $count = $countQuery->count();
                
                // Aplicar búsqueda si existe
                if ($this->search && $this->hasSearchable) {
                    $filteredQuery = clone $this->sql;
                    $filteredQuery = $this->applySearchToQuery($filteredQuery);
                    
                    // if ($this->distinctColumn) {
                    //     $filteredCount = $filteredQuery->distinct($this->distinctColumn)->count($this->distinctColumn);
                    // } else {
                    // }
                    $filteredCount = $filteredQuery->count();
                } else {
                    $filteredCount = $count;
                }
                
                // Aplicar ordenamiento y paginación
                $query = $this->sql;
                foreach ($this->order as $order) {
                    $query = $query->orderBy($order['column'], $order['dir']);
                }

                if ($this->cursorColumn !== null) {
                    $cursor = (int) Request::get('cursor', 0);
                    $results = $query->where($this->cursorColumn, '>', $cursor)
                        ->limit($this->length)
                        ->get();
                } else {
                    $results = $query->skip($this->start)->take($this->length)->get();
                }
                
            } else {
                // Para SQL crudo
                if ($this->distinctColumn) {
                    // Para SQL con DISTINCT, necesitamos contar de manera diferente
                    // $countSql = "SELECT COUNT(DISTINCT {$this->distinctColumn}) as count FROM ({$this->sql}) as subquery";
                } else {
                    // $countSql = "SELECT COUNT(*) as count FROM ({$this->sql}) as subquery";
                }
                $countSql = "SELECT COUNT(*) as count FROM ({$this->sql}) as subquery";
                
                $countResult = DB::select($countSql);
                $count = $countResult[0]->count ?? 0;
                $filteredCount = $count;
                
                // Ejecutar consulta con paginación
                // $baseSql = $this->distinctColumn ? "SELECT DISTINCT {$this->distinctColumn}, * FROM ({$this->sql}) as base" : $this->sql;
                $baseSql = $this->sql;
                
                // Agregar ordenamiento si existe
                if (!empty($this->order)) {
                    $orderClauses = [];
                    foreach ($this->order as $order) {
                        $orderClauses[] = "{$order['column']} {$order['dir']}";
                    }
                    $baseSql .= " ORDER BY " . join(', ', $orderClauses);
                }
                
                $paginatedSql = $baseSql . " LIMIT {$this->start}, {$this->length}";
                $results = collect(DB::select($paginatedSql));
            }
            
            $collection = $this->encryptKeys($results->toArray());

            if ($this->cursorColumn !== null && !$results->isEmpty()) {
                $data['nextCursor'] = $results->last()->{$this->cursorColumn};
            }

        } else {
            // Código para modelo Eloquent normal
            // if ($this->distinctColumn) {
            //     // 
            //     $count = $this->model->distinct($this->distinctColumn)->count($this->distinctColumn);
            // } else {
            // }
            $count = $this->model->count();

            if ($this->model && $this->search && $this->hasSearchable) {
                $searchedModel = $this->searchOnModel();
                
                // if ($this->distinctColumn) {
                //     $filteredCount = $searchedModel->distinct($this->distinctColumn)->count($this->distinctColumn);
                // } else {
                // }
                $filteredCount = $searchedModel->count();
                
                $this->model = $searchedModel;
            } else {
                $filteredCount = $count;
            }

            // Cursor pagination en path modelo normal
            if ($this->cursorColumn !== null && $this->model) {
                $cursor = (int) Request::get('cursor', 0);
                $cursorQuery = $this->model->where($this->cursorColumn, '>', $cursor)
                    ->limit($this->length);
                foreach ($this->order as $order) {
                    $cursorQuery = $cursorQuery->orderBy($order['column'], $order['dir']);
                }
                $results = $cursorQuery->get();
                $collection = $this->encryptKeys($results->toArray());
                if (!$results->isEmpty()) {
                    $data['nextCursor'] = $results->last()->{$this->cursorColumn};
                }
                $data['recordsTotal'] = $count;
                $data['recordsFiltered'] = $filteredCount;
                $data['data'] = $collection;
                return $data;
            }

            $model = $this->model ? $this->sortModel() : null;
            $build = collect([]);

            if ($model) {
                $model->each(function ($item, $key) use ($build) {
                    $build->put($key, $item);
                });

                $collection = $this->encryptKeys($build->values()->toArray());
            }
        }

        $data['recordsTotal'] = $count;
        $data['recordsFiltered'] = $filteredCount;
        $data['data'] = $collection ?? [];

        return $data;
    }

    /**
     * Order the model
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function sortModel()
    {
        if ($this->distinctColumn) {
            $this->model = $this->model->distinct($this->distinctColumn);
        }

        $build = $this->hasSearchable
            ? (($this->length < 0) ? $this->model : $this->model->skip($this->start)->take($this->length))
            : $this->model;

        $model = null;
        $sqlPaginated = false;

        foreach ($this->order as $index => $order) {
            $sortByRelation = str_contains($order['column'], '.');

            if ($index === 0) {
                if ($sortByRelation) {
                    // Columna de relación: no se puede empujar ORDER BY a SQL sin JOIN — ordena en PHP
                    $model = $order['dir'] === 'asc'
                        ? $build->get()->sortBy($order['column'])
                        : $build->get()->sortByDesc($order['column']);
                } elseif (!$this->hasSearchable && !$this->search) {
                    // Optimización: empuja ORDER BY + LIMIT al SQL en vez de traer toda la tabla a PHP
                    $model = $build->orderBy($order['column'], $order['dir'])
                        ->skip($this->start)
                        ->take($this->length)
                        ->get();
                    $sqlPaginated = true;
                } else {
                    $model = $build->orderBy($order['column'], $order['dir'])->get();
                }
            } else {
                $model = $order['dir'] === 'asc'
                    ? $model->sortBy($order['column'])
                    : $model->sortByDesc($order['column']);
            }
        }

        if ($this->search && !$this->hasSearchable) {
            $model = $this->searchOnCollection($model);
        }

        if (!$this->hasSearchable && !$sqlPaginated) {
            return $model->slice($this->start, $this->length);
        }

        return $model;
    }
    /**
     * Search on the model
     *
     * @param \Illuminate\Database\Eloquent\Collection $collection
     * @return \Illuminate\Database\Eloquent\Collection
     * @author Wim Pruiksma
     */
    private function searchOnModel()
    {
        return  $this->model->where(function($query){
                    foreach($this->searchable as $index => $column){
                        $this->searchOnRelation($column, $query);
                        $this->searchOnQuery($column, $query, $index);
                    }
                });
    }

    /**
     * Execute the search queries
     *
     * @param string $column
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $index
     */
    private function searchOnQuery(string $column, \Illuminate\Database\Eloquent\Builder $query, int $index)
    {
        if($index === 0 && !str_contains($column, '.')){
            $query->whereRaw("lower($column) LIKE ?", "%{$this->search['value']}%"); 
        }elseif($index > 0 && !str_contains($column, '.')){
            $query->orWhereRaw("lower($column) LIKE ?", "%{$this->search['value']}%");
        }
    }

    /**
     * Search on relation
     *
     * @param string $column
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    public function searchOnRelation(string $column, \Illuminate\Database\Eloquent\Builder $query)
    {
        if(str_contains($column, '.')){
            $explode = explode('.', $column);

            $query->orWhereHas($explode[0], function($query) use($explode){
                $query->whereRaw("lower($explode[1]) LIKE ?", "%{$this->search['value']}%");
            });
        }
    }    

    /**
     * Create a macro search on the collection
     *
     * @param mixed $collection
     * @return collection
     */
    private function searchOnCollection($collection)
    {
        $this->createSearchMacro();
        $this->createSearchableKeys();
        $search = $this->search['value'];
        $result = [];
        foreach ($this->searchable as $searchKey) {
            $result[] = $collection->like($searchKey, strtolower($search));
        }
        return collect($result)->flatten();
    }

    /**
     * Create searchable keys
     * If none given it creates its own
     *
     * @author Wim Pruiksma
     */
    private function createSearchableKeys()
    {
        $builder = $this->model;
        foreach ($this->column as $column) {
            $name = str_before($column['data'], '.');
            if ($column['searchable'] != true) {
                continue;
            }
            if (in_array($name, $this->columns)) {
                $this->searchable[] = $name;
                continue;
            }
            if ($name !== 'function' && $builder->has($name) && $builder->first()) {
                if (optional($builder->first()->$name)->first()) {
                    $collect = $builder->first()->$name;
                    foreach ($collect->first()->toArray() as $col => $value) {
                        $type  = $collect instanceof \Illuminate\Database\Eloquent\Collection ? '.*.' : '.';
                        $this->searchable[] = $name.$type.$col;
                    }
                }
            }
        }
    }

    /**
     * Create a macro for the collection
     * It searches inside the collections
     *
     * @author Wim Pruiksma
     */
    private function createSearchMacro()
    {
        \Illuminate\Database\Eloquent\Collection::macro('like',
            function ($key, $search) {
                return $this->filter(function ($item) use ($key, $search) {
                    $collection = data_get($item, $key, '');
                    if (is_array($collection)) {
                        foreach ($collection as $collect) {
                            $contains = str_contains(strtolower($collect),
                                    $search) || str_contains(strtolower($collect),
                                    $search) || strtolower($collect) == $search;
                            if ($contains) {
                                return true;
                            }
                        }
                    } else {
                        return str_contains(strtolower(data_get($item, $key, '')),
                            $search);
                    }
                });
            });
    }

    /**
     * Encrypt the given keys
     *
     * @param array $data
     * @return array
     * @author Wim Pruiksma
     */
    protected function encryptKeys($data)
    {
        foreach($data as $key => $value){
            if(is_array($value)){
                $data[$key] = $this->encryptKeys($value);
            }else{
                $data[$key] = $this->encryptValues($key, $value);
            }
        }
        return $data;
    }

    /**
     * Encrypt the value keys
     *
     * @param mixed $value
     * @return mixed
     */
    private function encryptValues($key, $value)
    {
        if(!is_array($this->encrypt)){
            return $value;
        }
        if(in_array($key, $this->encrypt)){
            return encrypt($value);
        }else{
            return $value;
        }
    }

    /**
     * Set the keys to encrypt
     *
     * @param mixed $encrypt
     * @return $this
     * @author Wim Pruiksma
     */
    public function encrypt(...$encrypt)
    {
        $this->encrypt = (isset($encrypt[0]) && is_array($encrypt[0])) ? $encrypt[0]
                : $encrypt;
        return $this;
    }
    
    /**
     * Set the table
     *
     * @param string $table
     * @return $this
     * @author Wim Pruiksma
     */
    public function table(string $table)
    {
        $this->tableid = $table;
        return $this;
    }

    /**
     * Use the function to exclude certain column
     *
     * @param mixed $noselect
     * @return $this
     * @deprecated in version ^2.0.0
     * @author Wim Pruiksma
     */
    public function noSelect($noselect)
    {
        return $this->exclude($noselect);
    }

    /**
     * Keys are always returned so this method is depricated
     *
     * @return $this
     * @deprecated in version ^2.0.0
     * @author Wim Pruiksma
     */
    public function withKeys()
    {
        return $this;
    }

    /**
     * Aplicar búsqueda a query builder
     */
    private function applySearchToQuery($query)
    {
        return $query->where(function($q) {
            foreach($this->searchable as $index => $column){
                if(str_contains($column, '.')){
                    $this->searchOnRelation($column, $q);
                } else {
                    $this->searchOnQuery($column, $q, $index);
                }
            }
        });
    }

    /**
     * Aplicar ordenamiento y límite a query builder con manejo de DISTINCT
     */
    private function applyOrderAndLimit($query)
    {
        // Aplicar distinct primero si está configurado
        if ($this->distinctColumn) {
            $query = $query->distinct($this->distinctColumn);
        }
        
        // Aplicar ordenamiento
        foreach ($this->order as $order) {
            $query = $query->orderBy($order['column'], $order['dir']);
        }
        
        // Aplicar paginación
        return $query->skip($this->start)->take($this->length);
    }

    private function getCountForRawSql($sql)
    {
        if ($this->distinctColumn) {
            // Para consultas con DISTINCT, contar valores únicos
            $countSql = "SELECT COUNT(DISTINCT {$this->distinctColumn}) as count FROM ({$sql}) as subquery";
        } else {
            $countSql = "SELECT COUNT(*) as count FROM ({$sql}) as subquery";
        }
        
        try {
            $result = DB::select($countSql);
            return $result[0]->count ?? 0;
        } catch (\Exception $e) {
            // Fallback si hay error en la consulta de count
            return 0;
        }
    }

    /**
     * Agregar paginación a SQL crudo con manejo de DISTINCT
     */
    private function addPaginationToSql($sql)
    {
        $baseSql = $sql;
        
        // Si hay distinct column, asegurarse de seleccionarla
        if ($this->distinctColumn && !str_contains(strtoupper($sql), 'DISTINCT')) {
            $baseSql = "SELECT DISTINCT {$this->distinctColumn}, * FROM ({$sql}) as base_query";
        }
        
        // Agregar ordenamiento
         if (!empty($this->order)) {
             $orderClauses = [];
             foreach ($this->order as $order) {
                 $orderClauses[] = "{$order['column']} {$order['dir']}";
             }
             $baseSql .= " ORDER BY " . join(', ', $orderClauses);
         }
        
        // Agregar paginación (sintaxis MySQL)
        return $baseSql . " LIMIT {$this->start}, {$this->length}";
    }
}