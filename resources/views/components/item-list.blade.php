<table id="item-list" class="table table-hover table-striped">
    <thead class="table-success">
	<th scope="col">
	    <input type="checkbox" id="toggle-select">
        </th>
        @foreach ($columns as $key => $column)
	    <th scope="col">{{ $column->label }}</th>
        @endforeach
    </thead>
    <tbody>
        @foreach ($rows as $i => $row)
	     @php 
	         $query = $route['query'];
                 $query[$route['item_name']] = $row['item_id'];
            @endphp
	    <tr class="clickable-row" data-href="{{ route($route['name'], $query) }}">
		<td>
		    <div class="form-check">
			<input type="checkbox" class="form-check-input" data-item-id={{ $row['item_id'] }} data-index="{{ $i }}">
		    </div>
		</td>
		@foreach ($columns as $column)
		    <td>{{ $row[$column->id] }}</td>
		@endforeach
	    </tr>
        @endforeach
    </tbody>

</table>
