В этом проекте представлен класс TableSorting, который позволяет делать сортировку и поиск (фильтрацию)
связанных данных из БД (для связанных таблиц) и отображать результат
в виде html-таблиц со стандартной пагинацией PHP-фреймворка Laravel.

Подобная задача часто возникает при разработке админ-панели сайта.

Для доступа к данным таблиц БД используются модели Laravel Eloquent ORM.

Класс реализует сортировку и поиск в заданных колонках таблицы и в колонках связанных 
таблиц с помощью добавления к исходному запросу Eloquent ORM (для заданной модели) 
необходимых для поиска и сортировки конструкций 'join', 'where' и 'order by'.

В представленной реализации сортировка может быть сделана только по одной колонке.


Пример использования в контроллере Laravel:

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
