# Using Multiple Layouts in Laravel Livewire

This document explains how to use different Blade layout templates for different pages in your Laravel Livewire application.

## Available Layouts

This application has multiple layout options:

| Layout Path | Description |
|-------------|-------------|
| `components.layouts.app` | Main layout with sidebar navigation, header, and full app features |
| `components.layouts.simple` | Simple layout without sidebar navigation (for standalone/public pages) |
| `layouts.app` | Alternative layout (used by the `AppLayout` component class) |
| `layouts.guest` | Guest/authentication layout for login/register pages |

## How to Use Different Layouts

### Method 1: Using the `#[Layout()]` Attribute (Recommended)

You can specify a different layout for any Livewire component by using the `#[Layout()]` attribute:

```php
<?php

namespace App\Livewire\YourComponent;

use Livewire\Attributes\Layout;
use Livewire\Component;

// Use the simple layout without sidebar
#[Layout('components.layouts.simple')]
class YourComponent extends Component
{
    public function render()
    {
        return view('livewire.your-component');
    }
}
```

### Method 2: Setting Default Layout in Config

The default layout for all Livewire components is configured in `config/livewire.php`:

```php
'layout' => 'components.layouts.app',
```

You can change this to any layout path to set a different default for all components.

### Method 3: Using Layout in Routes

You can also specify layouts when defining routes:

```php
// Using a view with a specific layout
Route::view('welcome', 'welcome')->name('welcome');

// For Livewire full-page components, the layout is determined by:
// 1. The #[Layout()] attribute on the component
// 2. The default layout in config/livewire.php
Route::get('/dashboard', Dashboard::class)->name('dashboard');
```

## Creating a New Layout

To create a new layout:

1. Create a new Blade file in `resources/views/components/layouts/`:

```php
// resources/views/components/layouts/admin.blade.php
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireScripts
</head>
<body>
    <!-- Your admin layout structure -->
    <main>
        {{ $slot }}
    </main>
</body>
</html>
```

2. Use it in your component:

```php
#[Layout('components.layouts.admin')]
class AdminDashboard extends Component
{
    // ...
}
```

## Layout Files Structure

```
resources/views/
├── components/
│   └── layouts/
│       ├── app.blade.php      # Main app layout (with sidebar)
│       ├── simple.blade.php   # Simple layout (no sidebar)
│       └── parts/
│           ├── aside.blade.php
│           └── header.blade.php
├── layouts/
│   ├── app.blade.php          # Alternative layout (AppLayout component)
│   └── guest.blade.php        # Guest/auth layout
```

## Example: Using Simple Layout

To use the simple layout (without sidebar) for a component:

```php
<?php

namespace App\Livewire\Public;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.simple')]
class PublicPage extends Component
{
    public function render()
    {
        return view('livewire.public.public-page');
    }
}
```

## Notes

- The `$slot` variable in layouts is where your component's view content will be rendered
- The `$title` variable can be set using `#[Title('Page Title')]` attribute on components
- You can include shared components (header, footer, etc.) using `@include` or Blade components
