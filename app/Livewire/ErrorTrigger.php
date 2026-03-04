<?php

namespace App\Livewire;

use Livewire\Component;

class ErrorTrigger extends Component
{
    public function trigger(): void
    {
        throw new \Exception('Deliberate 500 to reproduce filament/filament#19332');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.error-trigger');
    }
}
