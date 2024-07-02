<?php

namespace App\Http\Controllers;

use App\Livewire\Actions\Logout;

class CustomLogout extends Controller
{
    public function doLogout(Logout $logout) {
        $logout();

        return redirect()->route('welcome');
    }
}
