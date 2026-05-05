<div class="mx-auto max-w-6xl space-y-6 px-4 py-6" x-data="{ tab: @entangle('tab') }">
    @if (session('message'))<div class="rounded-xl bg-emerald-100 px-4 py-3 text-emerald-800">{{ session('message') }}</div>@endif

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
        <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-4">
            <input wire:model.defer="newName" placeholder="Name" class="rounded-xl border px-3 py-2">
            <input x-show="tab==='priorities' || tab==='importance-levels'" wire:model.defer="newLevel" type="number" placeholder="Level" class="rounded-xl border px-3 py-2">
            <input x-show="tab==='statuses'" wire:model.defer="newCode" placeholder="Code" class="rounded-xl border px-3 py-2">
            <label x-show="tab==='categories'" class="flex items-center gap-2"><input type="checkbox" wire:model="newIsErp"> ERP</label>
            <button wire:click="createItem" class="rounded-xl bg-slate-900 px-4 py-2 text-white">Add</button>
        </div>

        <div x-show="tab==='categories'" class="space-y-2">@foreach($categories as $r)<div class="flex items-center gap-2 rounded border p-2"><input wire:model.defer="editName" @if($editId!==$r->id) disabled @endif value="{{ $r->name }}" class="rounded border px-2 py-1"><label class="text-xs"><input type="checkbox" wire:model="editIsErp" @if($editId!==$r->id) disabled @endif> ERP</label>@if($editId===$r->id)<button wire:click="updateItem('categories')" class="rounded bg-slate-900 px-2 py-1 text-xs text-white">Save</button>@else<button wire:click="startEdit('categories', {{ $r->id }})" class="rounded border px-2 py-1 text-xs">Edit</button>@endif<button wire:click="deleteItem('categories', {{ $r->id }})" class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700">Delete</button></div>@endforeach</div>
        <div x-show="tab==='priorities'" class="space-y-2">@foreach($priorities as $r)<div class="flex items-center gap-2 rounded border p-2">{{ $r->name }} ({{ $r->level }}) <button wire:click="startEdit('priorities', {{ $r->id }})" class="rounded border px-2 py-1 text-xs">Edit</button><button wire:click="deleteItem('priorities', {{ $r->id }})" class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700">Delete</button></div>@endforeach</div>
        <div x-show="tab==='importance-levels'" class="space-y-2">@foreach($importanceLevels as $r)<div class="flex items-center gap-2 rounded border p-2">{{ $r->name }} ({{ $r->level }}) <button wire:click="startEdit('importance-levels', {{ $r->id }})" class="rounded border px-2 py-1 text-xs">Edit</button><button wire:click="deleteItem('importance-levels', {{ $r->id }})" class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700">Delete</button></div>@endforeach</div>
        <div x-show="tab==='statuses'" class="space-y-2">@foreach($statuses as $r)<div class="flex items-center gap-2 rounded border p-2">{{ $r->name }} ({{ $r->code }}) <button wire:click="startEdit('statuses', {{ $r->id }})" class="rounded border px-2 py-1 text-xs">Edit</button><button wire:click="deleteItem('statuses', {{ $r->id }})" class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700">Delete</button></div>@endforeach</div>
        <div x-show="tab==='root-causes'" class="space-y-2">@foreach($rootCauses as $r)<div class="flex items-center gap-2 rounded border p-2">{{ $r->name }} <button wire:click="startEdit('root-causes', {{ $r->id }})" class="rounded border px-2 py-1 text-xs">Edit</button><button wire:click="deleteItem('root-causes', {{ $r->id }})" class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700">Delete</button></div>@endforeach</div>
    </section>
</div>
