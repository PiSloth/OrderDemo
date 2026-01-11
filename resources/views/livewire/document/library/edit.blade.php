<div class="space-y-6" x-data="{ locked: false }" x-on:document-save-failed.window="locked = false">
    <div x-show="locked" style="display:none"
        class="fixed inset-0 z-50 bg-slate-900/50">
        <div
            class="absolute left-1/2 top-1/2 w-[calc(100%-2rem)] max-w-sm -translate-x-1/2 -translate-y-1/2 p-6 bg-white rounded-lg shadow-lg dark:bg-slate-800">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 animate-spin text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <div>
                    <div class="text-sm font-semibold text-slate-900 dark:text-white">Saving document…</div>
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-300">Please wait. Don’t close this page.</div>
                </div>
            </div>
        </div>
    </div>
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Edit Document</h1>
            <p class="text-sm text-slate-500 dark:text-slate-300">Updates are saved as new revisions.</p>
        </div>

        <a wire:navigate href="{{ route('document.library.show', $document) }}"
            class="px-4 py-2 text-sm font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">
            Back
        </a>
    </div>

    <div class="bg-white border rounded-lg border-slate-200 dark:bg-slate-800 dark:border-slate-700">
        <form wire:submit.prevent="save" x-on:submit="locked = true" class="p-6 space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Title</label>
                    <input type="text" wire:model.live="title"
                        class="block w-full mt-1 border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white" />
                    @error('title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Document Type</label>
                    <select wire:model.live="company_document_type_id"
                        class="block w-full mt-1 border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                        <option value="">-- Select Type --</option>
                        @foreach (($documentTypes ?? []) as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                    @error('company_document_type_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <div class="mt-2">
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-300">Or create new type</label>
                        <input type="text" wire:model.live="new_document_type"
                            class="block w-full mt-1 border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white" />
                        @error('new_document_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
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

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Announcement Date</label>
                    <input type="date" wire:model.live="announced_at"
                        class="block w-full mt-1 border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white" />
                    @error('announced_at')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Content</label>

                <div
                    class="mt-1"
                    wire:ignore
                >
                    <script type="application/json" data-quill-initial>@json($body)</script>
                    <div
                        data-quill-editor
                        data-model="body"
                        data-upload-url="{{ route('document.library.upload-image') }}"
                        data-csrf="{{ csrf_token() }}"
                        class="bg-white dark:bg-slate-900"
                    ></div>
                </div>

                @error('body')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-3">
                <button type="submit" wire:loading.attr="disabled" wire:target="save" :disabled="locked"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700 disabled:opacity-60 disabled:cursor-not-allowed">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>
