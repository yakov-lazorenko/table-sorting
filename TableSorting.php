<?php

namespace App\Library\Services\TableSorting;

use Illuminate\Http\Request;

/**
 * Table Sorting and Text Search
 *
 * Класс реализует сортировку и поиск в колонках таблицы и в колонках связанных таблиц.
 * Класс добавляет к исходному запросу Eloquent ORM (для заданной модели)
 * необходимые для поиска и сортировки конструкции 'join', 'where' и 'order by'.
 * Класс совместим с Laravel Pagination.
 * Сортировка может быть сделана только по одной колонке.
 */
class TableSorting
{
    /**
     * @var array $сolumnsConfig
     *
     * В этом массиве нужно указать колонки, по которым нужно делать сортировку и поиск.
     * Пример:
     * array = [
     *      ['name' =>'id', 'sort'=>true, 'search'=>true, 'default_sort'=>'asc'],
     *      ['name'=>'name', 'sort'=>true, 'search'=>true],
     *      ['name'=>'name', 'sort'=>true, 'search'=>true],
     *      ['name'=>'licence_holder_id', 'sort'=>true, 'search'=>true, 'relation'=>'licenceHolder', 'relation_column'=>'name']
     *  ]
     *  name - название колонки таблице (в БД),
     *  sort - выполняется сортировка, если этот параметр задан и установлен в true
     *  search - выполняется поиск, если этот параметр задан и установлен в true
     *  default_sort - сортировка по умолчанию (может быть указана только для одной колонки)
     *  relation - название отношения 'belongsTo' (relationship) в модели основной таблицы
     *  relation_column - название колонки в связанной таблице, заданной в параметре 'relation'
     */
    public $сolumnsConfig = [];

    /**
     * @var Request $request
     */
    public $request;

    public $search_string = null;

    public $joined_tables;
    public $initial_query;


    public function __construct(array $columnsConfig, Request $request, $search_string = null)
    {
        $this->columnsConfig = $columnsConfig;
        $this->request = $request;
        $this->setSearchString($search_string);
    }


    /**
    * @var $query - запрос Eloquent ORM для заданной модели
    */
    public function getQuery($query, $search_string = null)
    {
        $this->initial_query = $query;
        if (!empty($search_string)) {
            $this->search_string = $search_string;
            $this->setSearchString($search_string);
        } elseif (!empty($this->search_string)) {
            $this->setSearchString($this->search_string);
        } else {    
            $this->setSearchStringFromRequest();
        }
        $sorting = false;
        $this->joined_tables = [];
        $table = $query->getModel()->getTable();
        $query = $query->select()->addSelect($table . '.id as id');
        $query = $this->addSelectsToQuery($query);
        $query = $this->addColumnsSortingToQuery($query);
        $query = $this->addDefaultSortingToQuery($query);
        return $this->addTextSearchToQuery($query);
    }


    public function setSearchString($search_string)
    {
        if (!empty($search_string) && strlen($search_string) > 1) {
            $this->search_string = $search_string;
        }
    }

    public function getSearchString()
    {
        return !empty($this->search_string) ? $this->search_string : '';
    }


    public function getUrlForColumn($column_name)
    {
        $current_sort_type = $this->getCurrentColumnSortingType($column_name);
        if (!$current_sort_type) {
            $sort_type = 'asc';
        } elseif ($current_sort_type == 'asc') {
            $sort_type = 'desc';
        } elseif ($current_sort_type == 'desc') {
            $sort_type = null;
        } else {
            $sort_type = null;
        }
        $current_sorted_column = false;
        $request_params = $this->request->all();
        foreach ($request_params as $param => $value) {
            if (strpos($param, 'sort_') === 0) {
                $current_sorted_column = substr($param, 5);
                unset($request_params[$param]);
            }
        }
        if (isset($request_params['page'])) {
            unset($request_params['page']);
        }
        if ($sort_type) {
            $request_params[ $this->getColumnRequestParamName($column_name) ] = $sort_type;
        }
        return !empty($request_params) ? url()->current() . '?' . http_build_query($request_params) : url()->current();
    }


    public function renderColumnHeader($column_name, $column_title)
    {
        $tableSorting = $this;
        return view(
            'admin.common.sorted-column-header',
            compact('column_name', 'column_title', 'tableSorting')
        );
    }


    public function addSelectsToQuery($query)
    {
        $table = $this->initial_query->getModel()->getTable();
        $columnsWithRelations = $this->getColumnsWithRelations();
        $columns = \Schema::getColumnListing($table);
        foreach ($columnsWithRelations as $column) {
            $config = $this->getColumnConfig($column);
            $relationColumn = $config['relation_column'];
            if (in_array($relationColumn, $columns) && $relationColumn != 'id') {
                $query->addSelect($table . '.' . $relationColumn . ' as ' . $relationColumn);
            }
        }
        return $query;
    }


    public function addColumnsSortingToQuery($query)
    {
        $table = $this->initial_query->getModel()->getTable();
        foreach($this->columnsConfig as $columnConfig) {
            if (empty($columnConfig['name']) || empty($columnConfig['sort'])){
                continue;
            }
            $sort_type = $this->getCurrentColumnSortingType($columnConfig['name']);
            if ( !$sort_type || !in_array($sort_type, ['asc', 'desc']) ) {
                continue;
            }
            if (!empty($columnConfig['relation']) && !empty($columnConfig['relation_column'])) {
                $relation = $columnConfig['relation'];
                $related_table = $this->initial_query->getModel()->{$relation}()->getRelated()->getTable();
                $related_table_column = $columnConfig['relation_column'];
                $foreign_key = $this->initial_query->getModel()->{$relation}()->getForeignKeyName();
                $related_table_primary_key = $this->initial_query->getModel()->{$relation}()->getOwnerKeyName();                
                if (!in_array($related_table, $this->joined_tables)) {
                    $query = $query->join(
                        $related_table,
                        $related_table . '.' . $related_table_primary_key,
                        '=',
                        $table . '.' . $foreign_key
                    );
                    $this->joined_tables[] = $related_table;
                }
                $query = $query->orderBy($related_table . '.' . $related_table_column, $sort_type);
            } else {
                $query = $query->orderBy($table . '.' . $columnConfig['name'], $sort_type);
            }
            //sort only by one column
            break;
            //
        }
        return $query;
    }


    public function addDefaultSortingToQuery($query)
    {
        list($default_sort_column, $default_sort_type) = $this->getDefaultSortColumnInfo();
        if (!$this->needToDoSorting() && $default_sort_column && $default_sort_type) {
            $table = $this->initial_query->getModel()->getTable();
            $sort_type = $default_sort_type;
            $columnConfig = $this->getColumnConfig($default_sort_column);
            if (!empty($columnConfig['relation']) && !empty($columnConfig['relation_column'])) {
                $relation = $columnConfig['relation'];
                $related_table = $this->initial_query->getModel()->{$relation}()->getRelated()->getTable();
                $related_table_column = $columnConfig['relation_column'];
                $foreign_key = $this->initial_query->getModel()->{$relation}()->getForeignKeyName();
                $related_table_primary_key = $this->initial_query->getModel()->{$relation}()->getOwnerKeyName();
                if (!in_array($related_table, $this->joined_tables)) {
                    $query = $query->join(
                        $related_table,
                        $related_table . '.' . $related_table_primary_key,
                        '=',
                        $table . '.' . $foreign_key
                    );
                    $this->joined_tables[] = $related_table;
                }
                $query = $query->orderBy($related_table . '.' . $related_table_column, $sort_type);
            } else {
                $query = $query->orderBy($table . '.' . $default_sort_column, $default_sort_type);
            }
        }
        return $query;
    }


    public function addTextSearchToQuery($query)
    {
        if (empty($this->search_string)) {
            return $query;
        }
        $isFirstWhere = true;
        $table = $this->initial_query->getModel()->getTable();

        foreach ($this->columnsConfig as $columnConfig) {
            if (empty($columnConfig['name']) || empty($columnConfig['search'])) {
                continue;
            }
            if (!empty($columnConfig['relation']) && !empty($columnConfig['relation_column'])) {
                $relation = $columnConfig['relation'];
                $related_table = $this->initial_query->getModel()->{$relation}()->getRelated()->getTable();
                $related_table_column = $columnConfig['relation_column'];
                $foreign_key = $this->initial_query->getModel()->{$relation}()->getForeignKeyName();
                $table = $this->initial_query->getModel()->getTable();
                $related_table_primary_key = $this->initial_query->getModel()->{$relation}()->getOwnerKeyName();
                if (!in_array($related_table, $this->joined_tables)) {
                    $query = $query->join(
                        $related_table,
                        $related_table . '.' . $related_table_primary_key,
                        '=',
                        $table . '.' . $foreign_key
                    );
                    $this->joined_tables[] = $related_table;
                }
                if ($isFirstWhere) {
                    $query = $query->where($related_table . '.' . $related_table_column, 'like', '%' . $this->search_string . '%');
                    $isFirstWhere = false;
                } else {
                    $query = $query->orWhere($related_table . '.' . $related_table_column, 'like', '%' . $this->search_string . '%');
                }
            } else {
                if ($isFirstWhere) {
                    $query = $query->where($table . '.' . $columnConfig['name'], 'like', '%' . $this->search_string . '%');
                    $isFirstWhere = false;
                } else {
                    $query = $query->orWhere($table . '.' . $columnConfig['name'], 'like', '%' . $this->search_string . '%');
                }
            }
        }

        return $query;
    }//


    public function getColumnsWithRelations()
    {
        $columns = [];
        $table = $this->initial_query->getModel()->getTable();
        foreach ($this->columnsConfig as $columnConfig) {
            if (empty($columnConfig['name']) || empty($columnConfig['search'])) {
                continue;
            }
            if (!empty($columnConfig['relation']) && !empty($columnConfig['relation_column'])) {
                $columns[] = $columnConfig['name'];
            }
        }
        return $columns;
    }


    public function getDefaultSortColumnInfo()
    {
        $default_sort_column = null;
        $default_sort_type = null;
        foreach ($this->columnsConfig as $columnConfig) {
            if (empty($columnConfig['name'])) {
                continue;
            }
            if (!empty($columnConfig['default_sort']) &&
                in_array($columnConfig['default_sort'], ['asc', 'desc'])
            ) {
                $default_sort_column = $columnConfig['name'];
                $default_sort_type = $columnConfig['default_sort'];
                break;
            }
        }
        return [$default_sort_column, $default_sort_type];
    }


    public function needToDoSorting()
    {
        $table = $this->initial_query->getModel()->getTable();
        foreach($this->columnsConfig as $columnConfig) {
            if (empty($columnConfig['name']) || empty($columnConfig['sort'])) {
                continue;
            }
            $sort_type = $this->getCurrentColumnSortingType($columnConfig['name']);
            if ( !$sort_type || !in_array($sort_type, ['asc', 'desc']) ) {
                continue;
            }
            return true;
            //sort only by one column
            break;
            //
        }
        return false;
    }


    public function setSearchStringFromRequest()
    {
        if ( empty($this->request) ||
             empty($this->request->input('search')) ||
             trim($this->request->input('search')) == false ||
             strlen(trim($this->request->input('search'))) < 2
        ) {
            return;
        }
        $this->search_string = trim($this->request->input('search'));
    }


    public function getColumnConfig($column_name)
    {
        foreach ($this->columnsConfig as $columnConfig) {
            if (empty($columnConfig['name'])) {
                continue;
            }
            if ($column_name == $columnConfig['name']) {
                return $columnConfig;
            }
        }
        return null;
    }


    public function getRequestParams()
    {
        $params = [];
        foreach ($this->columnsConfig as $columnConfig) {
            if (empty($columnConfig['name'])) {
                continue;
            }
            $params[ $this->getColumnRequestParamName($columnConfig['name']) ]
                = $this->getCurrentColumnSortingType($columnConfig['name']);
        }
        if (!empty($this->search_string)) {
            $params['search'] = $this->search_string;
        }
        return $params;
    }


    public function getCurrentColumnSortingType($column_name)
    {
        return $this->request->input($this->getColumnRequestParamName($column_name), null);
    }
    

    public function getColumnRequestParamName($column_name)
    {
        return 'sort_' . $column_name;
    }

}

/* Example:

        $columnsConfig = [
            ['name' => 'id', 'sort'=>true, 'search'=>true, 'default_sort' => 'asc'],
            ['name' => 'holder_id', 'sort'=>true, 'search'=>true, 'relation' => 'holder', 'relation_column' => 'name'],
            ['name' => 'street', 'sort'=>true, 'search'=>true],
            ['name' => 'street_number', 'search'=>true],
            ['name' => 'location_id', 'sort'=>true, 'search'=>true, 'relation' => 'location', 'relation_column' => 'name'],
            ['name' => 'phone', 'search'=>true],
            ['name' => 'published', 'sort'=>true, 'search'=>true],
        ];
        $tableSorting = new TableSorting($columnsConfig, $request);
        $items = $tableSorting->getQuery(Items::query())->paginate(10);
        return view('admin.items.index', compact('items', 'tableSorting'));
*/

