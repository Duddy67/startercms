<table class="table">
    <thead>
        @foreach ($columns as $key => $column)
	    <th scope="col">{{ $column['label'] }}</th>
        @endforeach
    </thead>
    <tbody>
        @foreach ($items as $key => $item)
	    <tr>
	    @foreach ($columns as $keyName => $column)
		<td>{{ $item->$keyName }}</td>
	    @endforeach
	    </tr>
        @endforeach
    </tbody>

</table>
