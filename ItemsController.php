<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\{Items, Location, Holder};
use App\Library\Services\TableSorting\TableSorting;
use Illuminate\Http\Request;

class ItemsController extends Controller
{

    /**
     * Show the items list
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        /**
         *   Конфиг для сортировки списка аптек по колонкам таблицы (в БД) аптек и связанных таблиц.
         */
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
    }

}
