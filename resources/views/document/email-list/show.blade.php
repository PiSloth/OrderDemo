<x-layouts.app title="View Email Entry">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">View Email Entry</h1>
            <p class="text-sm text-slate-500 dark:text-slate-300">Details for this entry.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('document.email-list.edit', $emailList) }}"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700">
                Edit
            </a>
            <a href="{{ route('document.email-list.index') }}"
                class="px-4 py-2 text-sm font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">
                Back
            </a>
        </div>
    </div>

    <div class="mt-6 overflow-hidden bg-white border rounded-lg border-slate-200 dark:bg-slate-800 dark:border-slate-700">
        <dl class="divide-y divide-slate-200 dark:divide-slate-700">
            <div class="grid grid-cols-1 gap-2 px-6 py-4 sm:grid-cols-3">
                <dt class="text-sm font-medium text-slate-600 dark:text-slate-300">User Name</dt>
                <dd class="text-sm text-slate-900 dark:text-white sm:col-span-2">{{ $emailList->user_name }}</dd>
            </div>
            <div class="grid grid-cols-1 gap-2 px-6 py-4 sm:grid-cols-3">
                <dt class="text-sm font-medium text-slate-600 dark:text-slate-300">Email</dt>
                <dd class="text-sm text-slate-900 dark:text-white sm:col-span-2">{{ $emailList->email }}</dd>
            </div>
            <div class="grid grid-cols-1 gap-2 px-6 py-4 sm:grid-cols-3">
                <dt class="text-sm font-medium text-slate-600 dark:text-slate-300">Department</dt>
                <dd class="text-sm text-slate-900 dark:text-white sm:col-span-2">{{ $emailList->department }}</dd>
            </div>
        </dl>

        <div class="flex items-center justify-end px-6 py-4 border-t border-slate-200 dark:border-slate-700">
            <form action="{{ route('document.email-list.destroy', $emailList) }}" method="POST"
                onsubmit="return confirm('Delete this email entry?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-700">Delete</button>
            </form>
        </div>
    </div>
</x-layouts.app>
