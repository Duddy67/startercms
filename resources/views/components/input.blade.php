
@if (isset($attribs->label))
    <label for="{{ $attribs->id }}">{{ $attribs->label }}</label>
@endif

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

@if (isset($attribs->name))
    @error($attribs->name)
	<div class="text-danger">{{ $message }}</div>
    @enderror
@endif
