# table-sorting

Визуализация данных из нескольких связанных таблиц БД. Сортировка и фильтрация связанных данных из БД с отображением результатов в виде html-таблиц.

Инструмент позволяет отображать данные из нескольких связанных таблиц БД, производит сортировку и фильтрацию этих данных. Для этого он автоматически выстраивает нужный SQL запрос исходя из настроек, в которых
прописаны связи между таблицами БД.

Инструмент предназначен для использования с PHP-фреймворком Laravel и Eloquent ORM.
Для доступа к данным таблиц БД используются модели Eloquent ORM, а для визуализации данных - стандартная пагинация Laravel.

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
