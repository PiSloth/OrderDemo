<div x-data="{ 
    formOpen: false, 
    editOpen: false,
    tagsOpen: false,
    importOpen: false,
        copied: {},
        copyText(text) {
            if (!text) return;
            try {
                if (navigator?.clipboard?.writeText) {
                    navigator.clipboard.writeText(text);
                    return;
                }
            } catch (e) {}

            const el = document.createElement('textarea');
            el.value = text;
            el.setAttribute('readonly', 'readonly');
            el.style.position = 'absolute';
            el.style.left = '-9999px';
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
        },
        markCopied(key) {
            if (!key) return;
            this.copied[key] = true;
            setTimeout(() => { this.copied[key] = false; }, 2000);
        },
        isCopied(key) {
            return !!this.copied[key];
        }
    }"
    x-on:close-edit-modal.window="editOpen = false"
    x-on:keydown.escape.window="tagsOpen = false; importOpen = false; if ($wire?.cancelEditTag) { $wire.cancelEditTag(); }"
    class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Email List</h1>       
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('document.email-list.export', request()->query()) }}"
                class="inline-flex items-center justify-center w-10 h-10 border rounded-md border-slate-300 text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-white"
                title="Export">
                <x-icon name="download" class="w-5 h-5" />
            </a>

            <button type="button" @click="importOpen = true"
                class="inline-flex items-center justify-center w-10 h-10 border rounded-md border-slate-300 text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-white"
                title="Import">
                <x-icon name="upload" class="w-5 h-5" />
            </button>
           

            <button type="button" @click="formOpen = true" wire:click="startCreate"
                class="inline-flex items-center px-3 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700">
                <x-icon name="plus" class="w-4 h-4 mr-2" />
                New
            </button>

            <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                <input type="checkbox" class="rounded border-slate-300" wire:model.live="archived" />
                Show Archived
            </label>
        </div>
    </div>

    @if (session('success'))
        <div class="p-3 text-sm text-green-800 bg-green-100 border border-green-200 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if (!empty($importErrors ?? []))
        <div class="p-3 text-sm text-amber-900 bg-amber-50 border border-amber-200 rounded">
            <div class="font-medium">Import warnings</div>
            <ul class="mt-2 space-y-1 list-disc list-inside">
                @foreach ($importErrors as $msg)
                    <li>{{ $msg }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Import Modal -->
    <div x-show="importOpen" x-cloak class="fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/50" @click="importOpen = false"></div>

        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-2xl overflow-hidden bg-white rounded-lg shadow-lg dark:bg-slate-800">
                <div class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-700">
                    <div>
                        <div class="text-base font-semibold text-slate-900 dark:text-white">Import Email Addresses</div>
                        <div class="text-sm text-slate-500 dark:text-slate-300">Upload CSV or XLSX and we will create/update records by email.</div>
                    </div>

                    <button type="button" @click="importOpen = false"
                        class="inline-flex items-center justify-center w-9 h-9 border rounded-md border-slate-300 text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-white"
                        aria-label="Close import modal">
                        <x-icon name="x" class="w-5 h-5" />
                    </button>
                </div>

                <form wire:submit.prevent="import" class="p-4 space-y-4">
                    <div class="text-sm text-slate-600 dark:text-slate-200">
                        Required columns: <span class="font-medium">email</span> and either <span class="font-medium">department_id</span> or <span class="font-medium">department</span>.
                        Optional: <span class="font-medium">user_name</span>, <span class="font-medium">tags</span> (comma/semicolon separated).
                    </div>

                    <div>
                        <input type="file" wire:model="importFile" accept=".csv,.txt,.xlsx"
                            class="block w-full text-sm text-slate-700 file:mr-4 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 dark:text-slate-200 dark:file:bg-slate-700 dark:file:text-slate-100" />
                        @error('importFile')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <div wire:loading wire:target="importFile" class="mt-2 text-sm text-slate-500">Uploading…</div>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button type="button" @click="importOpen = false"
                            class="px-4 py-2 text-sm font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Cancel</button>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700"
                            wire:loading.attr="disabled" wire:target="import">
                            Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div x-show="formOpen" x-cloak x-transition
        class="bg-white border rounded-lg border-slate-200 dark:bg-slate-800 dark:border-slate-700">
        <div class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-700">
            <div>
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">
                    Create Entry
                </h2>
                <p class="text-sm text-slate-500 dark:text-slate-300">
                    Add a new record.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" wire:click="startCreate" @click="formOpen = true"
                    class="px-3 py-2 text-sm font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">
                    Clear
                </button>

                <button type="button" @click="formOpen = false"
                    class="inline-flex items-center justify-center w-9 h-9 border rounded-md border-slate-300 text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-white"
                    aria-label="Close form">
                    <x-icon name="x" class="w-5 h-5" />
                </button>
            </div>
        </div>

        <form wire:submit.prevent="create" class="p-4 space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">User Name</label>
                    <input type="text" wire:model.live="new_user_name"
                        class="block w-full mt-1 border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white" />
                    @error('new_user_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Department</label>
                    <select wire:model.live="new_department_id"
                        class="block w-full mt-1 border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                        <option value="">-- Select Department --</option>
                        @foreach (($departments ?? []) as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    @error('new_department_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Email</label>
                    <input type="email" wire:model.live="new_email"
                        class="block w-full mt-1 border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white" />
                    @error('new_email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700">
                    Create
                </button>
            </div>
        </form>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div class="grid w-full gap-3 sm:max-w-5xl sm:grid-cols-3">
            <input type="text" placeholder="Search name, email, department..."
                class="block w-full border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                wire:model.live.debounce.300ms="search" />

            <select wire:model.live="department_id"
                class="block w-full border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                <option value="">All Departments</option>
                @foreach (($departments ?? []) as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="tag_id"
                class="block w-full border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                <option value="">All Tags</option>
                @foreach (($tags ?? []) as $tag)
                    <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex justify-end sm:justify-start">
            <button type="button" @click="tagsOpen = true"
                class="px-3 py-2 text-sm font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">
                Manage Tags
            </button>
        </div>
    </div>

    <!-- Tags Modal -->
    <div x-show="tagsOpen" x-cloak class="fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/50" @click="tagsOpen = false; if ($wire?.cancelEditTag) { $wire.cancelEditTag(); }"></div>

        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-4xl overflow-hidden bg-white rounded-lg shadow-lg dark:bg-slate-800">
                <div class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-700">
                    <div>
                        <div class="text-base font-semibold text-slate-900 dark:text-white">Email Tags</div>
                        <div class="text-sm text-slate-500 dark:text-slate-300">Create tags and quickly add all tagged emails to To/Cc.</div>
                    </div>

                    <button type="button" @click="tagsOpen = false; if ($wire?.cancelEditTag) { $wire.cancelEditTag(); }"
                        class="inline-flex items-center justify-center w-9 h-9 border rounded-md border-slate-300 text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-white"
                        aria-label="Close tags modal">
                        <x-icon name="x" class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-4 space-y-4 max-h-[80vh] overflow-y-auto">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div class="text-sm text-slate-500 dark:text-slate-300">Tip: use the Tag filter dropdown to filter the list.</div>

                        <form wire:submit.prevent="createTag" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <input type="text" placeholder="New tag name" wire:model.live="new_tag_name"
                                class="block w-full border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white" />
                            <button type="submit"
                                class="inline-flex items-center px-3 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700">
                                Add Tag
                            </button>
                        </form>
                    </div>

                    @if ($editTagId)
                        <div class="p-3 border rounded-md border-slate-200 dark:border-slate-700">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                <div class="text-sm font-medium text-slate-700 dark:text-slate-200">Edit Tag</div>
                                <input type="text" wire:model.live="edit_tag_name"
                                    class="block w-full border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white" />
                                <div class="flex gap-2">
                                    <button type="button" wire:click="updateTag"
                                        class="px-3 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700">Save</button>
                                    <button type="button" wire:click="cancelEditTag"
                                        class="px-3 py-2 text-sm font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Cancel</button>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="overflow-hidden border rounded-lg border-slate-200 dark:border-slate-700">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead class="bg-slate-50 dark:bg-slate-900/40">
                                <tr>
                                    <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">Tag</th>
                                    <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">Count</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-200 dark:bg-slate-800 dark:divide-slate-700">
                                @forelse (($tags ?? []) as $tag)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-slate-900 dark:text-white">{{ $tag->name }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">{{ $tag->email_lists_count ?? 0 }}</td>
                                        <td class="px-4 py-3 text-sm text-right whitespace-nowrap">
                                            <div class="inline-flex items-center gap-2">
                                                <button type="button" wire:click="addToByTag({{ $tag->id }})"
                                                    class="px-2 py-1 text-xs font-medium border rounded border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">To</button>
                                                <button type="button" wire:click="addCcByTag({{ $tag->id }})"
                                                    class="px-2 py-1 text-xs font-medium border rounded border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Cc</button>

                                                <button type="button" wire:click="$set('tag_id', '{{ $tag->id }}')"
                                                    class="inline-flex items-center justify-center w-8 h-8 border rounded-md border-slate-300 text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-white"
                                                    title="Filter">
                                                    <x-icon name="filter" class="w-4 h-4" />
                                                </button>
                                                <button type="button" wire:click="openEditTag({{ $tag->id }})"
                                                    class="inline-flex items-center justify-center w-8 h-8 border rounded-md border-slate-300 text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-white"
                                                    title="Edit">
                                                    <x-icon name="pencil" class="w-4 h-4" />
                                                </button>
                                                <button type="button" wire:click="deleteTag({{ $tag->id }})"
                                                    class="inline-flex items-center justify-center w-8 h-8 border rounded-md border-slate-300 text-red-600 hover:bg-red-50 hover:text-red-700 dark:border-slate-600 dark:hover:bg-red-900/20"
                                                    title="Delete"
                                                    onclick="if(!confirm('Delete this tag?')){event.preventDefault();event.stopImmediatePropagation();}">
                                                    <x-icon name="trash" class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-6 text-sm text-center text-slate-500 dark:text-slate-300">No tags yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (count($toEmails ?? []) || count($ccEmails ?? []))
        <div class="bg-white border rounded-lg border-slate-200 dark:bg-slate-800 dark:border-slate-700">
            <div class="p-4 space-y-4">
                @if (count($toEmails ?? []))
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start">
                        <div class="w-10 pt-2 text-sm font-medium text-slate-700 dark:text-slate-200">To</div>
                        <div class="flex-1">
                            <div class="flex flex-wrap gap-2 p-2 border rounded-md min-h-[44px] border-slate-200 dark:border-slate-700 dark:bg-slate-900">
                                @foreach (($toEmails ?? []) as $email)
                                    <span class="inline-flex items-center gap-2 px-2 py-1 text-xs font-medium rounded-full bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-100">
                                        <span class="break-all">{{ $email }}</span>
                                        <button type="button" wire:click="removeTo('{{ $email }}')"
                                            class="text-slate-600 hover:text-slate-900 dark:text-slate-200 dark:hover:text-white"
                                            aria-label="Remove">
                                            ×
                                        </button>
                                    </span>
                                @endforeach
                            </div>

                            <input type="text" readonly value="{{ implode('; ', $toEmails ?? []) }}"
                                class="hidden w-full mt-2 border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white" />
                        </div>

                        <div class="flex gap-2 mt-2 sm:mt-0 sm:ml-3">
                            <button type="button" @click="copyText(@js(implode('; ', $toEmails ?? []))); markCopied('copy-to')"
                                class="inline-flex items-center justify-center w-10 h-10 border rounded-md dark:border-slate-600"
                                :class="isCopied('copy-to') ? 'border-green-500 bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' : 'border-slate-300 text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700'"
                                title="Copy">
                                <span x-show="!isCopied('copy-to')"><x-icon name="duplicate" class="w-5 h-5" /></span>
                                <span x-show="isCopied('copy-to')"><x-icon name="share" class="w-5 h-5" /></span>
                            </button>
                            <button type="button" wire:click="clearTo"
                                class="px-3 py-2 text-sm font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">
                                Clear
                            </button>
                        </div>
                    </div>
                @endif

                @if (count($ccEmails ?? []))
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start">
                        <div class="w-10 pt-2 text-sm font-medium text-slate-700 dark:text-slate-200">Cc</div>
                        <div class="flex-1">
                            <div class="flex flex-wrap gap-2 p-2 border rounded-md min-h-[44px] border-slate-200 dark:border-slate-700 dark:bg-slate-900">
                                @foreach (($ccEmails ?? []) as $email)
                                    <span class="inline-flex items-center gap-2 px-2 py-1 text-xs font-medium rounded-full bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-100">
                                        <span class="break-all">{{ $email }}</span>
                                        <button type="button" wire:click="removeCc('{{ $email }}')"
                                            class="text-slate-600 hover:text-slate-900 dark:text-slate-200 dark:hover:text-white"
                                            aria-label="Remove">
                                            ×
                                        </button>
                                    </span>
                                @endforeach
                            </div>

                            <input type="text" readonly value="{{ implode('; ', $ccEmails ?? []) }}"
                                class="hidden w-full mt-2 border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white" />
                        </div>

                        <div class="flex gap-2 mt-2 sm:mt-0 sm:ml-3">
                            <button type="button" @click="copyText(@js(implode('; ', $ccEmails ?? []))); markCopied('copy-cc')"
                                class="inline-flex items-center justify-center w-10 h-10 border rounded-md dark:border-slate-600"
                                :class="isCopied('copy-cc') ? 'border-green-500 bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' : 'border-slate-300 text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700'"
                                title="Copy">
                                <span x-show="!isCopied('copy-cc')"><x-icon name="duplicate" class="w-5 h-5" /></span>
                                <span x-show="isCopied('copy-cc')"><x-icon name="share" class="w-5 h-5" /></span>
                            </button>
                            <button type="button" wire:click="clearCc"
                                class="px-3 py-2 text-sm font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">
                                Clear
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="overflow-hidden bg-white border rounded-lg border-slate-200 dark:bg-slate-800 dark:border-slate-700">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-900/40">
                    <tr>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">Name</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">Email</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">Department</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">Tags</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200 dark:bg-slate-800 dark:divide-slate-700">
                    @forelse ($emailLists as $row)
                        <tr>
                            <td class="px-4 py-3 text-sm text-slate-900 dark:text-white">{{ $row->user_name }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="break-all">{{ $row->email }}</span>

                                    <button type="button" wire:click="addToById({{ $row->id }})"
                                        class="px-2 py-1 text-xs font-medium border rounded border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">
                                        To
                                    </button>

                                    <button type="button" wire:click="addCcById({{ $row->id }})"
                                        class="px-2 py-1 text-xs font-medium border rounded border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">
                                        Cc
                                    </button>

                                    <button type="button" @click="copyText(@js($row->email)); markCopied('copy-email-{{ $row->id }}')"
                                        class="inline-flex items-center justify-center w-8 h-8 border rounded-md dark:border-slate-600"
                                        :class="isCopied('copy-email-{{ $row->id }}') ? 'border-green-500 bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' : 'border-slate-300 text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700'"
                                        title="Copy">
                                        <span x-show="!isCopied('copy-email-{{ $row->id }}')"><x-icon name="duplicate" class="w-4 h-4" /></span>
                                        <span x-show="isCopied('copy-email-{{ $row->id }}')"><x-icon name="share" class="w-4 h-4" /></span>
                                    </button>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">{{ $row->department?->name }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
                                <div class="flex flex-wrap items-center gap-2">
                                    @foreach (($row->tags ?? []) as $tag)
                                        <span class="inline-flex items-center gap-2 px-2 py-1 text-xs font-medium rounded-full bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-100">
                                            <span class="break-all">{{ $tag->name }}</span>
                                            <button type="button" wire:click="detachTag({{ $row->id }}, {{ $tag->id }})"
                                                class="text-slate-600 hover:text-slate-900 dark:text-slate-200 dark:hover:text-white"
                                                aria-label="Remove">
                                                ×
                                            </button>
                                        </span>
                                    @endforeach

                                    <select wire:change="attachTag({{ $row->id }}, $event.target.value)"
                                        class="px-2 py-1 text-xs font-medium border rounded border-slate-300 text-slate-700 bg-white hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:bg-slate-900 dark:hover:bg-slate-700">
                                        <option value="">+ Tag</option>
                                        @foreach (($tags ?? []) as $tag)
                                            <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-2">
                                    @if (!$archived)
                                        <button type="button" @click="editOpen = true" wire:click="openEdit({{ $row->id }})"
                                            class="inline-flex items-center justify-center w-8 h-8 border rounded-md border-slate-300 text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-white"
                                            title="Edit">
                                            <x-icon name="pencil" class="w-4 h-4" />
                                        </button>
                                        <button type="button" wire:click="archive({{ $row->id }})"
                                            class="inline-flex items-center justify-center w-8 h-8 border rounded-md border-slate-300 text-amber-600 hover:bg-amber-50 hover:text-amber-700 dark:border-slate-600 dark:hover:bg-amber-900/20"
                                            title="Archive"
                                            onclick="if(!confirm('Archive this entry?')){event.preventDefault();event.stopImmediatePropagation();}">
                                            <x-icon name="archive" class="w-4 h-4" />
                                        </button>
                                    @else
                                        <button type="button" wire:click="restore({{ $row->id }})"
                                            class="inline-flex items-center justify-center w-8 h-8 border rounded-md border-slate-300 text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-white"
                                            title="Restore">
                                            <x-icon name="refresh" class="w-4 h-4" />
                                        </button>
                                        <button type="button" wire:click="deletePermanently({{ $row->id }})"
                                            class="inline-flex items-center justify-center w-8 h-8 border rounded-md border-slate-300 text-red-600 hover:bg-red-50 hover:text-red-700 dark:border-slate-600 dark:hover:bg-red-900/20"
                                            title="Delete"
                                            onclick="if(!confirm('Delete permanently? This cannot be undone.')){event.preventDefault();event.stopImmediatePropagation();}">
                                            <x-icon name="trash" class="w-4 h-4" />
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-sm text-center text-slate-500 dark:text-slate-300">
                                No entries found.
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

    <!-- Edit Modal -->
    <div x-show="editOpen" x-cloak class="fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/50" @click="editOpen = false; $wire.closeEdit()"></div>

        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-xl overflow-hidden bg-white rounded-lg shadow-lg dark:bg-slate-800">
                <div class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-700">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">Edit Entry</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-300">Update the selected record.</p>
                    </div>

                    <button type="button" @click="editOpen = false; $wire.closeEdit()"
                        class="inline-flex items-center justify-center w-9 h-9 border rounded-md border-slate-300 text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-white"
                        aria-label="Close edit modal">
                        <x-icon name="x" class="w-5 h-5" />
                    </button>
                </div>

                <form wire:submit.prevent="update" class="p-4 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">User Name</label>
                            <input type="text" wire:model.live="edit_user_name"
                                class="block w-full mt-1 border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white" />
                            @error('edit_user_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Department</label>
                            <select wire:model.live="edit_department_id"
                                class="block w-full mt-1 border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                                <option value="">-- Select Department --</option>
                                @foreach (($departments ?? []) as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            @error('edit_department_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Email</label>
                            <input type="email" wire:model.live="edit_email"
                                class="block w-full mt-1 border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white" />
                            @error('edit_email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <button type="button" @click="editOpen = false; $wire.closeEdit()"
                            class="px-4 py-2 text-sm font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">
                            Cancel
                        </button>

                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
