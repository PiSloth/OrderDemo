<?php

namespace App\Livewire\Orders;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Example: Using different layouts for different pages
 *
 * To use a different layout for this component, uncomment one of the Layout attributes below:
 *
 * - #[Layout('components.layouts.app')]    - Main app layout with sidebar navigation (default)
 * - #[Layout('components.layouts.simple')] - Simple layout without sidebar (for standalone pages)
 * - #[Layout('layouts.app')]               - Alternative layout (used by AppLayout component)
 *
 * The default layout is configured in config/livewire.php ('layout' => 'components.layouts.app')
 * You can override it per-component using the #[Layout()] attribute as shown above.
 */
#[Title('dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        // dd(auth()->user()->id);
        return view('livewire.orders.dashboard');
    }
}
