<table id="item-list" class="table table-hover table-striped">
    <thead class="table-success">
	<th scope="col">
	    <input type="checkbox" id="toggle-select">
        </th>
        @foreach ($columns as $key => $column)
	    <th scope="col">
		@lang ($column->label)
	    </th>
        @endforeach
    </thead>
    <tbody>
        @foreach ($rows as $i => $row)
	     @php 
	         $query = $url['query'];
                 $query[$url['item_name']] = $row->item_id;
            @endphp
	    <tr class="clickable-row" data-href="{{ route($url['route'].'.edit', $query) }}">
		<td>
		    <div class="form-check">
			<input type="checkbox" class="form-check-input" data-item-id={{ $row->item_id }} data-index="{{ $i }}">
		    </div>
		    @if (isset($row->checked_out))
			<div class="">
			    <p class="mb-0"><small>{{ $row->checked_out }}&nbsp;&nbsp;<i class="fa fa-lock"></i><br>{{ $row->checked_out_time }}</small></p>
			</div>
		    @endif
		</td>
		@foreach ($columns as $column)
		    <td>{{ $row->{$column->name} }}</td>
		@endforeach
	    </tr>
        @endforeach
    </tbody>

</table>
