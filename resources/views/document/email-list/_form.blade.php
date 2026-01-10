@props([
    'emailList' => null,
    'submitLabel' => 'Save',
])

<div class="grid gap-4 sm:grid-cols-2">
    <div class="sm:col-span-1">
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">User Name</label>
        <input type="text" name="user_name" value="{{ old('user_name', $emailList?->user_name) }}"
            class="block w-full mt-1 border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white" />
        @error('user_name')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-1">
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Department</label>
        <input type="text" name="department" value="{{ old('department', $emailList?->department) }}"
            class="block w-full mt-1 border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white" />
        @error('department')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Email</label>
        <input type="email" name="email" value="{{ old('email', $emailList?->email) }}"
            class="block w-full mt-1 border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white" />
        @error('email')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="flex items-center justify-end mt-6 gap-x-3">
    <a href="{{ route('document.email-list.index') }}"
        class="px-4 py-2 text-sm font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">
        Cancel
    </a>

    <button type="submit"
        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700">
        {{ $submitLabel }}
    </button>
</div>
