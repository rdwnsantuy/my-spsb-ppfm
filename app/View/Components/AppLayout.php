<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * Render the component using the anonymous Blade view
     * at resources/views/components/app-layout.blade.php
     */
    public function render(): View
    {
        // ARAHKAN KE KOMPONEN YANG BENAR (BUKAN layouts.app)
        return view('components.app-layout');
    }
}
