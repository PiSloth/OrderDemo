<div class="mx-auto max-w-4xl space-y-6 px-4 py-5">
    <section class="rounded-3xl bg-slate-900 px-5 py-6 text-white shadow-md">
        <h1 class="text-2xl font-semibold">Branch Checklist</h1>
        <p class="mt-2 text-sm text-slate-200">Generate today checklist and complete each item from mobile-friendly cards.</p>

        <button wire:click="generate" type="button"
            class="mt-4 inline-flex items-center rounded-2xl bg-emerald-500 px-4 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-emerald-600 active:translate-y-0.5">
            Generate Checklist
        </button>
    </section>

    <section class="grid grid-cols-1 gap-4">
        @forelse ($items as $item)
            <button wire:key="history-{{ $item->id }}" wire:click="openCard({{ $item->id }})" type="button"
                class="w-full rounded-2xl bg-white p-4 text-left shadow-md transition duration-200 hover:scale-105">
                <h2 class="text-base font-semibold text-slate-900">{{ $item->checklist?->title ?? 'Untitled checklist' }}</h2>
                @if ($item->checklist?->description)
                    <p class="mt-1 text-sm text-slate-600">{{ $item->checklist->description }}</p>
                @endif
            </button>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500">
                No pending checklist item for today.
            </div>
        @endforelse
    </section>

    <div x-data="{ open: @entangle('showModal') }" x-show="open" x-cloak
        class="fixed inset-0 z-50 flex items-end justify-center p-4 backdrop-blur-md sm:items-center"
        x-transition.opacity>
        <div @click.away="$wire.closeModal()"
            class="w-full max-w-lg p-2"
            x-transition:enter="transform transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transform transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95">
            <x-textarea wire:model.defer="remark" placeholder="Type remark..."
                class="w-full rounded-2xl border border-slate-200/60 bg-slate-300/40 text-white placeholder:text-white/70 shadow-lg backdrop-blur-xl" />

            <div class="mt-5 flex gap-4">
                <button wire:click="markDone" type="button"
                    class="h-12 flex-1 rounded-2xl border border-emerald-200/60 bg-emerald-600/70 px-5 text-base font-semibold text-white shadow-lg backdrop-blur-xl transition hover:bg-emerald-400/50 active:translate-y-1 active:shadow-sm">
                    Done
                </button>
                <button wire:click="markNotDone" type="button"
                    class="h-12 flex-1 rounded-2xl border border-pink-200/60 bg-pink-400/35 px-5 text-base font-semibold text-white shadow-lg backdrop-blur-xl transition hover:bg-pink-400/50 active:translate-y-1 active:shadow-sm">
                    Not Done
                </button>
            </div>
        </div>
    </div>
</div>
