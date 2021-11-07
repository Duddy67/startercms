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
	    <tr>
		<td>
		    <div class="form-check">
			<input type="checkbox" class="form-check-input" data-item-id={{ $row->item_id }} data-index="{{ $i }}">

			@if (isset($row->checked_out))
			    <div class="checked-out">
				<p class="mb-0"><small>{{ $row->checked_out }}&nbsp;&nbsp;<i class="fa fa-lock"></i><br>{{ $row->checked_out_time }}</small></p>
			    </div>
			@endif
		    </div>
		</td>
		@foreach ($columns as $column)
		    @if ($column->name == 'ordering')
			<td>
			    @if (isset($row->ordering['up']))
				<a href="{{ $row->ordering['up'] }}">up</a>
			    @endif

			    @if (isset($row->ordering['down']))
				<a href="{{ $row->ordering['down'] }}">down</a>
			    @endif
			</td>
		    @else
			@php $indent = (in_array($column->name, ['name', 'title']) && preg_match('#^(-{1,}) #', $row->{$column->name}, $matches)) ? strlen($matches[1]) : 0; @endphp
			<td>
			    @php echo (in_array($column->name, ['name', 'title', 'code'])) ? '<a href="'.route($url['route'].'.edit', $query).'">' : ''; @endphp
			    <span class="indent-{{ $indent }}"></span>
			    @if (isset($column->extra) && in_array('raw', $column->extra))
                                {!! $row->{$column->name} !!}
			    @else
                                {{ $row->{$column->name} }}
			    @endif
			    @php echo (in_array($column->name, ['name', 'title', 'code'])) ? '</a>' : ''; @endphp
			</td>
		    @endif
		@endforeach
	    </tr>
        @endforeach
    </tbody>

</table>
