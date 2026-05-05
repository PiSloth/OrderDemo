<div class="mx-auto max-w-4xl space-y-6 px-4 py-6" x-data="{ tab: @entangle('activeTab'), showForm: true }">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Create Issue</h1><button @click="showForm=!showForm"
            class="rounded-xl border px-3 py-2 text-sm">Toggle Form</button>
    </div>
    <div x-show="showForm">
        <form wire:submit="save" class="space-y-4 rounded-2xl border bg-white p-6">
            <div class="flex gap-2"><button type="button" @click="tab='erp'" class="rounded-xl px-4 py-2 text-sm"
                    :class="tab === 'erp' ? 'bg-slate-900 text-white' : 'bg-slate-200'">ERP</button><button type="button"
                    @click="tab='it'" class="rounded-xl px-4 py-2 text-sm"
                    :class="tab === 'it' ? 'bg-slate-900 text-white' : 'bg-slate-200'">IT Support</button></div>
            <select wire:model="issue_category_id" class="w-full rounded-xl border px-3 py-2" required>
                <option value="">Category</option>
                <template x-if="tab==='erp'">
                    @foreach ($erpCategories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </template>
                <template x-if="tab==='it'">
                    @foreach ($itCategories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </template>
            </select>
            <input wire:model="title" class="w-full rounded-xl border px-3 py-2" placeholder="Issue title" required>
            <textarea wire:model="description" class="w-full rounded-xl border px-3 py-2" rows="4"
                placeholder="Issue description" required></textarea>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <select wire:model="issue_by_user_id" class="rounded-xl border px-3 py-2" required>
                    <option value="">Issue By (Current or Other User)</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Photos</label>
                <div class="mt-2 rounded-3xl border border-slate-300 bg-slate-50 p-4 sm:p-5">
                    <input id="issue-camera-photo" type="file" wire:model="cameraPhoto" accept="image/*"
                        capture="environment" class="hidden">
                    <input id="issue-gallery-photos" type="file" wire:model="galleryPhotos" accept="image/*" multiple
                        class="hidden">

                    <div class="flex flex-wrap items-center gap-3">
                        <label for="issue-camera-photo"
                            class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-medium text-white">
                            <span>Use Camera</span>
                        </label>
                        <label for="issue-gallery-photos"
                            class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl bg-white px-4 py-3 text-sm font-medium text-slate-700 ring-1 ring-slate-300">
                            <span>Upload</span>
                        </label>
                    </div>

                    <p class="mt-3 text-xs text-slate-500">Choose camera or upload. Maximum 4 photos.</p>

                    @if (count($submissionPhotos) > 0)
                        <div class="mt-5 grid gap-4 lg:grid-cols-2">
                            @foreach ($submissionPhotos as $index => $photo)
                                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                    @if (method_exists($photo, 'temporaryUrl'))
                                        <img src="{{ $photo->temporaryUrl() }}" alt="Preview {{ $index + 1 }}"
                                            class="h-48 w-full object-cover">
                                    @endif
                                    <div class="space-y-3 p-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex items-center gap-2">
                                                <p class="text-sm font-medium text-slate-700">Photo {{ $index + 1 }}
                                                </p>
                                                <span
                                                    class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] uppercase tracking-[0.15em] text-slate-600">
                                                    {{ ($submissionPhotoSources[$index] ?? 'gallery') === 'camera' ? 'Camera' : 'Upload' }}
                                                </span>
                                            </div>
                                            <button type="button"
                                                wire:click="removeSubmissionPhoto({{ $index }})"
                                                class="text-xs font-medium text-rose-600">Remove</button>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
                @error('cameraPhoto')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
                @error('galleryPhotos')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
                @error('galleryPhotos.*')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
                @error('submissionPhotos')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
                @error('submissionPhotos.*')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <button class="rounded-xl bg-slate-900 px-4 py-2 text-white">Create Issue</button>
        </form>
    </div>
</div>
