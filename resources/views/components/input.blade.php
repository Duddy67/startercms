
@if (isset($attribs->label) && $attribs->type != 'checkbox')
    <label for="{{ $attribs->id }}">{{ $attribs->label }}</label>
@endif

@php $disabled = (isset($attribs->extra) && in_array('disabled', $attribs->extra)) ? 'disabled' : '' @endphp
@php $class = (isset($attribs->class)) ? $attribs->class : '' @endphp

@if ($attribs->type == 'text' || $attribs->type == 'password' || $attribs->type == 'date')
    <input  id="{{ $attribs->id }}" {{ $disabled }} 

    @if ($attribs->type == 'date')
	type="text" class="form-control date {{ $class }}"
    @else 
	type="{{ $attribs->type }}" class="form-control {{ $class }}" 
    @endif

    @if (isset($attribs->name))
	name="{{ $attribs->name }}"
    @endif

    @if (isset($attribs->placeholder))
	placeholder="{{ $attribs->placeholder }}"
    @endif

    @if ($disabled)
	readonly
    @endif

    @if ($value)
	value="{{ $value }}"
    @endif

    >
@elseif ($attribs->type == 'select')
    @php $multiple = (isset($attribs->extra) && in_array('multiple', $attribs->extra)) ? 'multiple' : '' @endphp
    @php $multi = ($multiple) ? '[]' : '' @endphp

    <select id="{{ $attribs->id }}" class="form-control select2" {{ $multiple }} {{ $disabled }} name="{{ $attribs->name.$multi }}">
	@if (isset($attribs->blank))
	    <option value="">{{ $attribs->blank }}</option>
	@endif

        @foreach ($attribs->options as $option)
	    @if ($multiple)
		@php $selected = ($value !== null && in_array($option['value'], $value)) ? 'selected="selected"' : '' @endphp
	    @else
		@php $selected = ($option['value'] == $value) ? 'selected="selected"' : '' @endphp
	    @endif

	    <option value="{{ $option['value'] }}" {{ $selected }}>{{ $option['text'] }}</option>
        @endforeach
    </select> 
@elseif ($attribs->type == 'checkbox')
    @if (!isset($attribs->position) || $attribs->position == 'left')
	<label class="form-check-label" for="{{ $attribs->id }}">{{ $attribs->label }}</label>
    @endif

    <input type="checkbox" id="{{ $attribs->id }}" class="form-check-input"

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

    @if (isset($attribs->position) && $attribs->position == 'right')
	<label class="form-check-label" for="{{ $attribs->id }}">{{ $attribs->label }}</label>
    @endif
@endif

@if (isset($attribs->name))
    @error($attribs->name)
	<div class="text-danger">{{ $message }}</div>
    @enderror
@endif
