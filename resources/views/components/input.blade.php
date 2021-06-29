
@if (isset($attribs->label) && $attribs->type != 'checkbox')
    <label for="{{ $attribs->id }}">@lang ($attribs->label)</label>
@endif

@php $disabled = (isset($attribs->extra) && in_array('disabled', $attribs->extra)) ? 'disabled' : '' @endphp
@php $class = (isset($attribs->class)) ? $attribs->class : '' @endphp
@php $name = (isset($attribs->name)) ? $attribs->name : null @endphp
@php $name = ($name && isset($attribs->group)) ? $attribs->group.'['.$name.']' : $name @endphp
@php $multiple = (isset($attribs->extra) && in_array('multiple', $attribs->extra)) ? 'multiple' : '' @endphp
@php $multi = ($multiple) ? '[]' : '' @endphp

@if ($attribs->type == 'text' || $attribs->type == 'password' || $attribs->type == 'date' || $attribs->type == 'file')
    <input  id="{{ $attribs->id }}" {{ $disabled }} {{ $multiple }} 

    @if ($attribs->type == 'date')
	type="text" class="form-control date {{ $class }}"
    @else 
	type="{{ $attribs->type }}" class="form-control {{ $class }}" 
    @endif

    @if ($name)
	name="{{ $name.$multi }}"
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

    <select id="{{ $attribs->id }}" class="form-control select2" {{ $multiple }} {{ $disabled }} name="{{ $name.$multi }}"
    @if (isset($attribs->onchange))
	onchange="{{ $attribs->onchange }}"
    @endif
    >
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

    @if ($name)
	name="{{ $name }}"
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
@elseif ($attribs->type == 'textarea')
    <textarea id="{{ $attribs->id }}" class="form-control"

    @if ($name)
	name="{{ $name }}"
    @endif

    @if (isset($attribs->rows))
        rows="{{ $attribs->rows }}"
    @endif

    @if (isset($attribs->cols))
        cols="{{ $attribs->cols}}"
    @endif
    >
	@if (isset($attribs->content))
	    {{ $attribs->content }}
	@endif
    </textarea>
@endif

@if ($name)
    @error($name)
	<div class="text-danger">{{ $message }}</div>
    @enderror
@endif
