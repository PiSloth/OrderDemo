<div class="mx-auto max-w-6xl space-y-6 px-4 py-6" x-data="{ tab: @entangle('tab') }">
    @if (session('message'))
        <div class="rounded-xl bg-emerald-100 px-4 py-3 text-emerald-800">{{ session('message') }}</div>
    @endif

    <section class="rounded-2xl border bg-white p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">Issue Setup Configuration</h1>
            <button wire:click="seedDefaults" class="rounded-xl bg-slate-900 px-4 py-2 text-sm text-white">Seed Defaults</button>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            <button @click="tab='categories'" class="rounded-xl px-3 py-2 text-sm" :class="tab==='categories' ? 'bg-slate-900 text-white' : 'bg-slate-200'">Categories</button>
            <button @click="tab='priorities'" class="rounded-xl px-3 py-2 text-sm" :class="tab==='priorities' ? 'bg-slate-900 text-white' : 'bg-slate-200'">Priorities</button>
            <button @click="tab='importance-levels'" class="rounded-xl px-3 py-2 text-sm" :class="tab==='importance-levels' ? 'bg-slate-900 text-white' : 'bg-slate-200'">Importance</button>
            <button @click="tab='statuses'" class="rounded-xl px-3 py-2 text-sm" :class="tab==='statuses' ? 'bg-slate-900 text-white' : 'bg-slate-200'">Statuses</button>
            <button @click="tab='root-causes'" class="rounded-xl px-3 py-2 text-sm" :class="tab==='root-causes' ? 'bg-slate-900 text-white' : 'bg-slate-200'">Root Causes</button>
        </div>
    </section>

    <section class="rounded-2xl border bg-white p-6">
        <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-5">
            <input wire:model.defer="newName" placeholder="Name" class="rounded-xl border px-3 py-2">
            <input x-show="tab==='priorities' || tab==='importance-levels'" wire:model.defer="newLevel" type="number" placeholder="Level" class="rounded-xl border px-3 py-2">
            <input x-show="tab==='statuses'" wire:model.defer="newCode" placeholder="Code" class="rounded-xl border px-3 py-2">
            <label x-show="tab==='categories'" class="flex items-center gap-2 rounded-xl border px-3 py-2">
                <input type="checkbox" wire:model="newIsErp"> ERP
            </label>
            <button wire:click="createItem" class="rounded-xl bg-slate-900 px-4 py-2 text-white">Add</button>
        </div>

        <div x-show="tab==='categories'" class="space-y-2">
            @foreach($categories as $r)
                <div class="grid grid-cols-1 gap-2 rounded border p-3 md:grid-cols-12 md:items-center">
                    @if($editId === $r->id)
                        <input wire:model.defer="editName" class="rounded border px-2 py-1 md:col-span-5">
                        <label class="flex items-center gap-2 text-sm md:col-span-2">
                            <input type="checkbox" wire:model="editIsErp"> ERP
                        </label>
                        <div class="flex gap-2 md:col-span-5 md:justify-end">
                            <button wire:click="updateItem('categories')" class="rounded bg-slate-900 px-3 py-1 text-xs text-white">Save</button>
                            <button wire:click="cancelEdit" class="rounded border px-3 py-1 text-xs">Cancel</button>
                            <button wire:click="deleteItem('categories', {{ $r->id }})" class="rounded border border-rose-300 px-3 py-1 text-xs text-rose-700">Delete</button>
                        </div>
                    @else
                        <div class="font-medium md:col-span-5">{{ $r->name }}</div>
                        <div class="text-sm text-slate-600 md:col-span-2">{{ $r->is_erp ? 'ERP' : 'IT Support' }}</div>
                        <div class="flex gap-2 md:col-span-5 md:justify-end">
                            <button wire:click="startEdit('categories', {{ $r->id }})" class="rounded border px-3 py-1 text-xs">Edit</button>
                            <button wire:click="deleteItem('categories', {{ $r->id }})" class="rounded border border-rose-300 px-3 py-1 text-xs text-rose-700">Delete</button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div x-show="tab==='priorities'" class="space-y-2">
            @foreach($priorities as $r)
                <div class="grid grid-cols-1 gap-2 rounded border p-3 md:grid-cols-12 md:items-center">
                    @if($editId === $r->id)
                        <input wire:model.defer="editName" class="rounded border px-2 py-1 md:col-span-5">
                        <input wire:model.defer="editLevel" type="number" class="rounded border px-2 py-1 md:col-span-2">
                        <div class="flex gap-2 md:col-span-5 md:justify-end">
                            <button wire:click="updateItem('priorities')" class="rounded bg-slate-900 px-3 py-1 text-xs text-white">Save</button>
                            <button wire:click="cancelEdit" class="rounded border px-3 py-1 text-xs">Cancel</button>
                            <button wire:click="deleteItem('priorities', {{ $r->id }})" class="rounded border border-rose-300 px-3 py-1 text-xs text-rose-700">Delete</button>
                        </div>
                    @else
                        <div class="font-medium md:col-span-5">{{ $r->name }}</div>
                        <div class="text-sm text-slate-600 md:col-span-2">Level {{ $r->level }}</div>
                        <div class="flex gap-2 md:col-span-5 md:justify-end">
                            <button wire:click="startEdit('priorities', {{ $r->id }})" class="rounded border px-3 py-1 text-xs">Edit</button>
                            <button wire:click="deleteItem('priorities', {{ $r->id }})" class="rounded border border-rose-300 px-3 py-1 text-xs text-rose-700">Delete</button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div x-show="tab==='importance-levels'" class="space-y-2">
            @foreach($importanceLevels as $r)
                <div class="grid grid-cols-1 gap-2 rounded border p-3 md:grid-cols-12 md:items-center">
                    @if($editId === $r->id)
                        <input wire:model.defer="editName" class="rounded border px-2 py-1 md:col-span-5">
                        <input wire:model.defer="editLevel" type="number" class="rounded border px-2 py-1 md:col-span-2">
                        <div class="flex gap-2 md:col-span-5 md:justify-end">
                            <button wire:click="updateItem('importance-levels')" class="rounded bg-slate-900 px-3 py-1 text-xs text-white">Save</button>
                            <button wire:click="cancelEdit" class="rounded border px-3 py-1 text-xs">Cancel</button>
                            <button wire:click="deleteItem('importance-levels', {{ $r->id }})" class="rounded border border-rose-300 px-3 py-1 text-xs text-rose-700">Delete</button>
                        </div>
                    @else
                        <div class="font-medium md:col-span-5">{{ $r->name }}</div>
                        <div class="text-sm text-slate-600 md:col-span-2">Level {{ $r->level }}</div>
                        <div class="flex gap-2 md:col-span-5 md:justify-end">
                            <button wire:click="startEdit('importance-levels', {{ $r->id }})" class="rounded border px-3 py-1 text-xs">Edit</button>
                            <button wire:click="deleteItem('importance-levels', {{ $r->id }})" class="rounded border border-rose-300 px-3 py-1 text-xs text-rose-700">Delete</button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div x-show="tab==='statuses'" class="space-y-2">
            @foreach($statuses as $r)
                <div class="grid grid-cols-1 gap-2 rounded border p-3 md:grid-cols-12 md:items-center">
                    @if($editId === $r->id)
                        <input wire:model.defer="editName" class="rounded border px-2 py-1 md:col-span-4">
                        <input wire:model.defer="editCode" class="rounded border px-2 py-1 md:col-span-3">
                        <div class="flex gap-2 md:col-span-5 md:justify-end">
                            <button wire:click="updateItem('statuses')" class="rounded bg-slate-900 px-3 py-1 text-xs text-white">Save</button>
                            <button wire:click="cancelEdit" class="rounded border px-3 py-1 text-xs">Cancel</button>
                            <button wire:click="deleteItem('statuses', {{ $r->id }})" class="rounded border border-rose-300 px-3 py-1 text-xs text-rose-700">Delete</button>
                        </div>
                    @else
                        <div class="font-medium md:col-span-4">{{ $r->name }}</div>
                        <div class="text-sm text-slate-600 md:col-span-3">{{ $r->code }}</div>
                        <div class="flex gap-2 md:col-span-5 md:justify-end">
                            <button wire:click="startEdit('statuses', {{ $r->id }})" class="rounded border px-3 py-1 text-xs">Edit</button>
                            <button wire:click="deleteItem('statuses', {{ $r->id }})" class="rounded border border-rose-300 px-3 py-1 text-xs text-rose-700">Delete</button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div x-show="tab==='root-causes'" class="space-y-2">
            @foreach($rootCauses as $r)
                <div class="grid grid-cols-1 gap-2 rounded border p-3 md:grid-cols-12 md:items-center">
                    @if($editId === $r->id)
                        <input wire:model.defer="editName" class="rounded border px-2 py-1 md:col-span-7">
                        <div class="flex gap-2 md:col-span-5 md:justify-end">
                            <button wire:click="updateItem('root-causes')" class="rounded bg-slate-900 px-3 py-1 text-xs text-white">Save</button>
                            <button wire:click="cancelEdit" class="rounded border px-3 py-1 text-xs">Cancel</button>
                            <button wire:click="deleteItem('root-causes', {{ $r->id }})" class="rounded border border-rose-300 px-3 py-1 text-xs text-rose-700">Delete</button>
                        </div>
                    @else
                        <div class="font-medium md:col-span-7">{{ $r->name }}</div>
                        <div class="flex gap-2 md:col-span-5 md:justify-end">
                            <button wire:click="startEdit('root-causes', {{ $r->id }})" class="rounded border px-3 py-1 text-xs">Edit</button>
                            <button wire:click="deleteItem('root-causes', {{ $r->id }})" class="rounded border border-rose-300 px-3 py-1 text-xs text-rose-700">Delete</button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
</div>
