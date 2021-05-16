<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Input extends Component
{
    public $attribs;
    public $value;


    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($attribs, $value = null)
    {
        $this->attribs = $attribs;
        $this->value = $value;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.input');
    }
}
