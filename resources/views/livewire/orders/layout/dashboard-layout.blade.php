<div x-data="{ asideOpen: false }">
    @include('components.layouts.parts.header')

    @include('components.layouts.parts.aside')

    @yield('content')

</div>
