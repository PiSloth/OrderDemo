<div class="mx-auto max-w-7xl space-y-5 px-4 py-5"
    x-data="{ tab: 'titles' }"
    @touchstart="window.__branchCfgTouchX = $event.changedTouches[0].screenX"
    @touchend="
        const dx = $event.changedTouches[0].screenX - (window.__branchCfgTouchX || 0);
        if (Math.abs(dx) > 50) {
            tab = dx < 0 ? 'checklists' : 'titles';
        }
    ">
    <section class="rounded-3xl bg-white p-4 shadow-sm">
        <div class="relative inline-grid w-full grid-cols-2 rounded-2xl bg-slate-100 p-1 sm:w-auto">
            <div class="absolute top-1 bottom-1 w-1/2 rounded-xl bg-white shadow-sm transition-all duration-300"
                :class="tab === 'titles' ? 'left-1' : 'left-1/2'"></div>

            <button type="button" @click="tab = 'titles'"
                class="relative z-10 rounded-xl px-4 py-2 text-sm font-semibold transition"
                :class="tab === 'titles' ? 'text-slate-900' : 'text-slate-600'">
                Title Manager
            </button>
            <button type="button" @click="tab = 'checklists'"
                class="relative z-10 rounded-xl px-4 py-2 text-sm font-semibold transition"
                :class="tab === 'checklists' ? 'text-slate-900' : 'text-slate-600'">
                Checklist Config
            </button>
        </div>
    </section>

    <div x-show="tab === 'titles'" x-transition.opacity>
        <livewire:operation.title-manager />
    </div>

    <div x-show="tab === 'checklists'" x-transition.opacity>
        <livewire:operation.branch.branch-checklist.crud.index />
    </div>
</div>

