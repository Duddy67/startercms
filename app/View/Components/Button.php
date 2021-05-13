<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Button extends Component
{
    public $button;
    public $btnClass = ['new' => 'btn-success', 'delete' => 'btn-danger'];
    public $icon = '';


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
