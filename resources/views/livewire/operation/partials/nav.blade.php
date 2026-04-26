<section class="rounded-3xl border border-slate-200 bg-white p-2 shadow-sm">
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('operation.daily-notes') }}" wire:navigate
            class="inline-flex items-center rounded-2xl px-4 py-2.5 text-sm font-medium transition {{ request()->routeIs('operation.daily-notes') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
            Daily Notes
        </a>

        @can('manageOperationTitles')
            <a href="{{ route('operation.titles') }}" wire:navigate
                class="inline-flex items-center rounded-2xl px-4 py-2.5 text-sm font-medium transition {{ request()->routeIs('operation.titles') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                Titles
            </a>
        @endcan
    </div>
</section>
