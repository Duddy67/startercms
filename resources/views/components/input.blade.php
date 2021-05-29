
@if (isset($attribs->label))
    <label for="{{ $attribs->id }}">{{ $attribs->label }}</label>
@endif

@if ($attribs->type == 'text' || $attribs->type == 'password' || $attribs->type == 'date')
    <input  id="{{ $attribs->id }}" 

    @if ($attribs->type == 'date')
	type="text" class="form-control date"
    @else 
	type="{{ $attribs->type }}" class="form-control" 
    @endif

    @if (isset($attribs->name))
	name="{{ $attribs->name }}"
    @endif

    @if (isset($attribs->placeholder))
	placeholder="{{ $attribs->placeholder }}"
    @endif

    @if (isset($attribs->readonly) && $attribs->readonly)
	readonly
    @endif

    @if ($value)
	value="{{ $value }}"
    @endif

    >
@elseif ($attribs->type == 'select')
    <select id="{{ $attribs->id }}" class="form-control" name="{{ $attribs->name }}">
	@if (isset($attribs->blank))
	    <option value="">{{ $attribs->blank }}</option>
	@endif
        @foreach ($attribs->options as $option)
	    @php $selected = ($option['value'] == $value) ? 'selected="selected"' : ''; @endphp
	    <option value="{{ $option['value'] }}" {{ $selected }}>{{ $option['text'] }}</option>
        @endforeach
    </select> 
@elseif ($attribs->type == 'checkbox')
    <input type="checkbox" id="{{ $attribs->id }}" class="form-controller"

    @if (isset($attribs->name))
	name="{{ $attribs->name }}"
    @endif

    @if (isset($attribs->disabled) && $attribs->disabled)
	disabled="disabled"
    @endif

    @if ($value)
	value="{{ $value }}"
    @endif

    @if ($attribs->checked)
	checked
    @endif

    >
@endif

@if (isset($attribs->name))
    @error($attribs->name)
	<div class="text-danger">{{ $message }}</div>
    @enderror
@endif
