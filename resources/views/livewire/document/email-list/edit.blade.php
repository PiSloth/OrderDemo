<div>
    {{-- Nothing in the world is as soft and yielding as water. --}}
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Edit Email Entry</h1>
            <p class="text-sm text-slate-500 dark:text-slate-300">Update user name, email, and department.</p>
        </div>

        <a wire:navigate href="{{ route('document.email-list.index') }}"
            class="px-4 py-2 text-sm font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">
            Back
        </a>
    </div>

    <div class="bg-white border rounded-lg border-slate-200 dark:bg-slate-800 dark:border-slate-700">
        <form wire:submit.prevent="save" class="p-6 space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">User Name</label>
                    <input type="text" wire:model.live="user_name"
                        class="block w-full mt-1 border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white" />
                    @error('user_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Department</label>
                    <select wire:model.live="department_id"
                        class="block w-full mt-1 border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                        <option value="">-- Select Department --</option>
                        @foreach (($departments ?? []) as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Email</label>
                    <input type="email" wire:model.live="email"
                        class="block w-full mt-1 border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white" />
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>
