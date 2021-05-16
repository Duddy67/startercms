<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Button extends Component
{
    public $button;
    // Default classes and icons.
    public $btnClasses = ['new' => 'btn-success', 'save' => 'btn-success', 'saveClose' => 'btn-primary', 'delete' => 'btn-danger'];
    public $btnIcons = ['new' => 'fa-plus', 'save' => 'fa-save', 'saveClose' => 'fa-reply', 'cancel' => 'fa-times', 'delete' => 'fa-trash'];


    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($button)
    {
        $this->button = $button;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.button');
    }
}
