@extends('layouts.admin.default')

@section('breadcrumbs')
    <ol class="breadcrumb">
        <li><a href="{{ asset('/admin') }}"><i class="fa fa-circle-o"></i> Home</a></li>
        <li class="active">Items</li>
    </ol>
@endsection

@section('page-header')
    <h1>Items <small>list</small></h1>
@endsection

@section('content')
    <div class="box box-default">
        <div class="box-header with-border">
            <h3 class="box-title"></h3>
        </div>
        <div class="box-body">

            <div class="text_search_filter">
                <label>
                    <input type="search" class="search-input" placeholder="" value="{{ $tableSorting->getSearchString() }}">
                    <button class="btn btn-primary">Search</button>
                </label>
            </div>
            <div style="clear:both"></div>

            <table id="list_table" class="table table-responsive table-striped table-hover">
                <thead>
                    <tr>
                        <th scope="col">
                            {!! $tableSorting->renderColumnHeader('id', 'ID') !!}
                        </th>
                        <th scope="col">
                            {!! $tableSorting->renderColumnHeader('holder_id', 'Holder') !!}
                        </th>
                        <th scope="col">
                            {!! $tableSorting->renderColumnHeader('location_id', 'Location') !!}
                        </th>
                        <th scope="col">
                            {!! $tableSorting->renderColumnHeader('street', 'Street') !!}
                        </th>
                        <th scope="col">Street Number</th>
                        <th scope="col">Phone</th>
                        <th scope="col">
                            {!! $tableSorting->renderColumnHeader('published', 'Published') !!}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td>
                            {{ $item->holder->name ?? '' }}
                        </td>
                        <td>
                            {{ $item->location->name ?? '' }}
                        </td>
                        <td>{{ $item->street }}</td>
                        <td>{{ $item->street_number }}</td>
                        <td>{{ $item->phone }}</td>
                        <td>{{ $item->published }}</td>
                    </tr>
                    @endforeach
              </tbody>
            </table>

            {!! $items->appends($tableSorting->getRequestParams())->render(); !!}

        </div>

    </div>

@endsection

@section('custom-scripts')
    <script src="{{ asset('/lib/jquery/jquery-3.3.1.min.js') }}"></script>
    <script src="{{ asset('/js/table-sorting.js') }}"></script>
@endsection

