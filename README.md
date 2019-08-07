# table-sorting

В этом проекте представлен класс TableSorting, который позволяет делать
сортировку и поиск (фильтрацию) связанных данных из БД (для связанных таблиц)
и отображать результат в виде html-таблиц со стандартной пагинацией PHP-фреймворка Laravel.

Подобная задача часто возникает при разработке админ-панели сайта. Однако, в случае,
когда нужно отображать данные из нескольких связанных таблиц, требуется вручную прописывать
сложные запросы в БД, что требует много времени. Разработанный в данном проекте инструментарий
решает эту проблему, автоматически выстраивая нужный SQL запрос исходя из настроек, в которых
прописаны связи между таблицими БД.

Для доступа к данным таблиц БД используются модели Eloquent ORM.

Класс TableSorting реализует сортировку и поиск в заданных колонках таблицы 
и в колонках связанных таблиц с помощью добавления к исходному запросу 
Eloquent ORM (для заданной модели) необходимых для поиска и сортировки 
конструкций 'join', 'where' и 'order by'.

В представленной реализации сортировка может быть сделана только по одной колонке.

Проект предназначен для использования с PHP-фреймворком Laravel и Eloquent ORM.


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
