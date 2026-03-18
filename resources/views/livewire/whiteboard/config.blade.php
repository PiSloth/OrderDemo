<div x-data="{
    tabs: ['content-types', 'flags', 'email-lists'],
    activeTab: 'content-types',
    touchStartX: 0,
    touchEndX: 0,
    startSwipe(event) {
        this.touchStartX = event.changedTouches[0].screenX;
    },
    endSwipe(event) {
        this.touchEndX = event.changedTouches[0].screenX;
        const delta = this.touchStartX - this.touchEndX;
        if (Math.abs(delta) < 50) {
            return;
        }
        const currentIndex = this.tabs.indexOf(this.activeTab);
        if (delta > 0 && currentIndex < this.tabs.length - 1) {
            this.activeTab = this.tabs[currentIndex + 1];
        }
        if (delta < 0 && currentIndex > 0) {
            this.activeTab = this.tabs[currentIndex - 1];
        }
    }
}" class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Whiteboard Configuration</h1>
        <p class="text-sm text-slate-500 dark:text-slate-300">Manage content types, urgency flags, and sharing targets
            from one configuration surface.</p>
    </div>

    @if (session()->has('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm" @touchstart="startSwipe($event)"
        @touchend="endSwipe($event)">
        <div class="border-b border-slate-200 px-4 pt-4">
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="activeTab = 'content-types'"
                    :class="activeTab === 'content-types' ? 'bg-slate-900 text-white' :
                        'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                    class="rounded-full px-4 py-2 text-sm font-medium transition">
                    Content Types
                </button>
                <button type="button" @click="activeTab = 'flags'"
                    :class="activeTab === 'flags' ? 'bg-slate-900 text-white' :
                        'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                    class="rounded-full px-4 py-2 text-sm font-medium transition">
                    Flags
                </button>
                <button type="button" @click="activeTab = 'email-lists'"
                    :class="activeTab === 'email-lists' ? 'bg-slate-900 text-white' :
                        'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                    class="rounded-full px-4 py-2 text-sm font-medium transition">
                    Email Lists
                </button>
            </div>
            <p class="py-3 text-xs uppercase tracking-widest text-slate-400">Swipe left or right on mobile to switch
                tabs.</p>
        </div>

        <div class="p-4">
            <section x-show="activeTab === 'content-types'" x-cloak class="space-y-5">
                <div class="rounded-xl bg-slate-50 p-4">
                    <h2 class="text-lg font-semibold text-slate-900">Add Content Type</h2>
                    <form wire:submit.prevent="createContentType" class="mt-4 grid grid-cols-1 gap-3 lg:grid-cols-4">
                        <input type="text" wire:model.defer="newContentType.name" placeholder="Issue"
                            class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">

                        <x-color-picker wire:model.defer="newContentType.color" placeholder="Select the car color" />

                        {{-- <input type="text" placeholder="#2563EB"
                            class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"> --}}

                        <input type="text" wire:model.defer="newContentType.description"
                            placeholder="Category description"
                            class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <label
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700">
                            <input type="checkbox" wire:model.defer="newContentType.requires_decision"
                                class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            Requires decision
                        </label>
                        <div class="lg:col-span-4 flex justify-end">
                            <button type="submit"
                                class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Create
                                Type</button>
                        </div>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Name</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Color</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Description</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Decision</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($contentTypes as $contentType)
                                <tr>
                                    @if ($editContentTypeId === $contentType->id)
                                        <td class="px-4 py-3"><input type="text"
                                                wire:model.defer="editContentType.name"
                                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm"></td>
                                        <td class="px-4 py-3">
                                            <x-color-picker wire:model.defer="editContentType.color"
                                                placeholder="Select the car color" />

                                            {{-- <input type="text"
                                                wire:model.defer="editContentType.color"
                                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm"> --}}
                                        </td>
                                        <td class="px-4 py-3"><input type="text"
                                                wire:model.defer="editContentType.description"
                                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm"></td>
                                        <td class="px-4 py-3">
                                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                                <input type="checkbox"
                                                    wire:model.defer="editContentType.requires_decision"
                                                    class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                                Required
                                            </label>
                                        </td>
                                        <td class="px-4 py-3 text-right space-x-2">
                                            <button wire:click="updateContentType"
                                                class="text-emerald-700 hover:text-emerald-900">Save</button>
                                            <button wire:click="cancelContentTypeEdit"
                                                class="text-slate-500 hover:text-slate-700">Cancel</button>
                                        </td>
                                    @else
                                        <td class="px-4 py-3 font-medium text-slate-800">{{ $contentType->name }}</td>
                                        <td class="inline-flex mt-2 items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold text-slate-700"
                                            style="border-color: {{ $contentType->color }}; background-color: {{ $contentType->color }}20; color: {{ $contentType->color }};">
                                            {{ $contentType->name }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $contentType->description }}</td>
                                        <td class="px-4 py-3 text-slate-600">
                                            {{ $contentType->requires_decision ? 'Required' : 'Optional' }}</td>
                                        <td class="px-4 py-3 text-right space-x-3">
                                            <button wire:click="editType({{ $contentType->id }})"
                                                class="text-indigo-600 hover:text-indigo-800">Edit</button>
                                            <button wire:click="deleteContentType({{ $contentType->id }})"
                                                class="text-red-600 hover:text-red-800">Delete</button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section x-show="activeTab === 'flags'" x-cloak class="space-y-5">
                <div class="rounded-xl bg-amber-50 p-4">
                    <h2 class="text-lg font-semibold text-slate-900">Add Flag</h2>
                    <form wire:submit.prevent="createFlag" class="mt-4 grid grid-cols-1 gap-3 lg:grid-cols-3">
                        <input type="text" wire:model.defer="newFlag.name" placeholder="Urgent"
                            class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <input type="text" wire:model.defer="newFlag.color" placeholder="#F59E0B"
                            class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <input type="text" wire:model.defer="newFlag.description" placeholder="Flag description"
                            class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <div class="lg:col-span-3 flex justify-end">
                            <button type="submit"
                                class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700">Create
                                Flag</button>
                        </div>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Name</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Color</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Description</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($flags as $flag)
                                <tr>
                                    @if ($editFlagId === $flag->id)
                                        <td class="px-4 py-3"><input type="text" wire:model.defer="editFlag.name"
                                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm"></td>
                                        <td class="px-4 py-3"><input type="text" wire:model.defer="editFlag.color"
                                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm"></td>
                                        <td class="px-4 py-3"><input type="text"
                                                wire:model.defer="editFlag.description"
                                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm"></td>
                                        <td class="px-4 py-3 text-right space-x-2">
                                            <button wire:click="updateFlag"
                                                class="text-emerald-700 hover:text-emerald-900">Save</button>
                                            <button wire:click="cancelFlagEdit"
                                                class="text-slate-500 hover:text-slate-700">Cancel</button>
                                        </td>
                                    @else
                                        <td class="px-4 py-3 font-medium text-slate-800">{{ $flag->name }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $flag->color }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $flag->description }}</td>
                                        <td class="px-4 py-3 text-right space-x-3">
                                            <button wire:click="editFlag({{ $flag->id }})"
                                                class="text-indigo-600 hover:text-indigo-800">Edit</button>
                                            <button wire:click="deleteFlag({{ $flag->id }})"
                                                class="text-red-600 hover:text-red-800">Delete</button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section x-show="activeTab === 'email-lists'" x-cloak class="space-y-5">
                <div class="rounded-xl bg-sky-50 p-4">
                    <h2 class="text-lg font-semibold text-slate-900">Add Email List Entry</h2>
                    <form wire:submit.prevent="createEmailList" class="mt-4 grid grid-cols-1 gap-3 lg:grid-cols-4">
                        <input type="text" wire:model.defer="newEmailList.user_name" placeholder="Display name"
                            class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <input type="email" wire:model.defer="newEmailList.email" placeholder="person@company.com"
                            class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <select wire:model.defer="newEmailList.department_id"
                            class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select department</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                        <div class="flex justify-end">
                            <button type="submit"
                                class="w-full rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700">Create
                                Entry</button>
                        </div>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Name</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Email</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Department</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($emailLists as $emailList)
                                <tr>
                                    @if ($editEmailListId === $emailList->id)
                                        <td class="px-4 py-3"><input type="text"
                                                wire:model.defer="editEmailList.user_name"
                                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm"></td>
                                        <td class="px-4 py-3"><input type="email"
                                                wire:model.defer="editEmailList.email"
                                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm"></td>
                                        <td class="px-4 py-3">
                                            <select wire:model.defer="editEmailList.department_id"
                                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm">
                                                <option value="">Select department</option>
                                                @foreach ($departments as $department)
                                                    <option value="{{ $department->id }}">{{ $department->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-4 py-3 text-right space-x-2">
                                            <button wire:click="updateEmailList"
                                                class="text-emerald-700 hover:text-emerald-900">Save</button>
                                            <button wire:click="cancelEmailListEdit"
                                                class="text-slate-500 hover:text-slate-700">Cancel</button>
                                        </td>
                                    @else
                                        <td class="px-4 py-3 font-medium text-slate-800">{{ $emailList->user_name }}
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">{{ $emailList->email }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $emailList->department?->name }}</td>
                                        <td class="px-4 py-3 text-right space-x-3">
                                            <button wire:click="editEmailList({{ $emailList->id }})"
                                                class="text-indigo-600 hover:text-indigo-800">Edit</button>
                                            <button wire:click="deleteEmailList({{ $emailList->id }})"
                                                class="text-red-600 hover:text-red-800">Archive</button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>
