<x-layouts.app title="Email List">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Email List</h1>
            <p class="text-sm text-slate-500 dark:text-slate-300">Manage user name, email, and department.</p>
        </div>

        <a href="{{ route('document.email-list.create') }}"
            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700">
            <x-icon name="plus" class="w-4 h-4 mr-2" />
            New
        </a>
    </div>

    @if (session('success'))
        <div class="p-3 mt-4 text-sm text-green-800 bg-green-100 border border-green-200 rounded">
            {{ session('success') }}
        </div>
    @endif

    <div class="mt-6 overflow-hidden bg-white border rounded-lg border-slate-200 dark:bg-slate-800 dark:border-slate-700">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-900/40">
                    <tr>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">Name</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">Email</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">Department</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200 dark:bg-slate-800 dark:divide-slate-700">
                    @forelse ($emailLists as $row)
                        <tr>
                            <td class="px-4 py-3 text-sm text-slate-900 dark:text-white">{{ $row->user_name }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">{{ $row->email }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">{{ $row->department }}</td>
                            <td class="px-4 py-3 text-sm text-right whitespace-nowrap">
                                <a href="{{ route('document.email-list.show', $row) }}"
                                    class="text-slate-700 hover:text-slate-900 dark:text-slate-200 dark:hover:text-white">View</a>
                                <span class="mx-2 text-slate-300">|</span>
                                <a href="{{ route('document.email-list.edit', $row) }}"
                                    class="text-primary-600 hover:text-primary-700">Edit</a>
                                <span class="mx-2 text-slate-300">|</span>
                                <form action="{{ route('document.email-list.destroy', $row) }}" method="POST" class="inline"
                                    onsubmit="return confirm('Delete this email entry?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-700">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-sm text-center text-slate-500 dark:text-slate-300">
                                No entries yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 bg-white border-t border-slate-200 dark:bg-slate-800 dark:border-slate-700">
            {{ $emailLists->links() }}
        </div>
    </div>
</x-layouts.app>
