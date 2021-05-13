@php $class = ($button->id == 'new' || $button->id == 'delete') ? $btnClass[$button->id] : 'btn-primary' @endphp

@if (isset($button->class))
    @php $class = $button->class @endphp
@endif

@if (isset($button->icon))
    @php $icon = '<i class="'.$button->icon.'"></i>' @endphp
@endif

<button type="button" class="btn btn-space {{ $class }}">{!! $icon !!} {{ $button->label }}</button>
