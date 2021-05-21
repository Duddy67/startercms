<table id="item-list" class="table table-hover">
    <thead>
	<th scope="col">#</th>
        @foreach ($columns as $key => $column)
	    <th scope="col">{{ $column->label }}</th>
        @endforeach
    </thead>
    <tbody>
        @foreach ($rows as $i => $row)
	    <tr class="clickable-row" data-href="{{ route($route, $row['item_id']) }}">
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
