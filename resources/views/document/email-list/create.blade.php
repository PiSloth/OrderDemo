<x-layouts.app title="Create Email Entry">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Create Email Entry</h1>
            <p class="text-sm text-slate-500 dark:text-slate-300">Add a new user name, email, and department.</p>
        </div>
    </div>

    <div class="mt-6 bg-white border rounded-lg border-slate-200 dark:bg-slate-800 dark:border-slate-700">
        <form action="{{ route('document.email-list.store') }}" method="POST" class="p-6">
            @csrf

            @include('document.email-list._form', ['submitLabel' => 'Create'])
        </form>
    </div>
</x-layouts.app>
