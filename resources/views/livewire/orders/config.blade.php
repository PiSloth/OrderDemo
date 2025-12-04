    <div class="pt-4 px-4">
        <?php
        $i = 1;
        ?>
        <div>
            {{-- User name start --}}
            <div class="p-3 mb-4 border-2 border-b-blue-700">
                <div class="mt-4 mb-4">
                    <form class="flex flex-wrap gap-1" action="" wire:submit="create_user">
                        <input type="text" class="w-full sm:w-auto" placeholder="username" wire:model="username">
                        <input type="email" class="w-full sm:w-auto" placeholder="email" wire:model="email">
                        <select class="w-full sm:w-auto" wire:model="position_id">
                            <option value="">Select Position</option>
                            @foreach ($positions as $position)
                                <option value="{{ $position->id }}">{{ $position->name }}</option>
                            @endforeach
                        </select>
                        <input type="password" class="w-full sm:w-auto" placeholder="pass" autocomplete="new-password" wire:model="password">
                        <select class="w-full sm:w-auto" wire:model="branch_id">
                            <option value="">Select branch</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        <button
                            class="px-2 py-2 bg-blue-300 text-slate-500 hover:bg-blue-400 hover:text-slate-50">create</button>
                    </form>
                </div>
                {{-- Postion form --}}
                <p class="text-red-300">Users Table</p>
                <div class="">

                    <div class="overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Position</th>
                            </tr>
                        </thead>
                        <tbody>

                            @forelse ($users as $user)
                                <tr wire:key="{{ $user->id }}">

                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->position->name }}</td>
                                </tr>
                            @empty
                                <i>Not yet user</i>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
                {{-- create user --}}

            </div>
            {{-- User name end  --}}
            {{-- // **  --}}
            {{-- Position start --}}
            <div class="p-3 mb-4 border-2 border-b-blue-700">
                <form action="" wire:submit wire:keydown.enter="create_position">
                    <div>
                        <label for="position" class="block text-xl text-gray-500">Position</label>
                        <input id="position" class="w-full rounded-full ring-slate-50" type="text" wire:model="position"
                            placeholder="Type & Enter">
                        {{-- <button
                            class="px-2 py-2 bg-gray-400 rounded text-slate-300 hover:bg-gray-600 hover:text-slate-50">Create</button> --}}
                    </div>
                </form>
                <div class="">
                    @forelse ($positions as $position)
                        <li class="flex justify-between mb-2" wire:key="{{ $position->id }}">
                            {{ $position->name }}
                            <button class="text-red-500"
                                wire:click="delete_position({{ $position->id }})">&times;</button>
                        </li>
                    @empty
                        <p>Not yet</p>
                    @endforelse
                </div>
            </div>
            {{--  Position end --}}
            {{-- // ** --}}
            {{-- category start --}}
            <div class="p-3 mb-4 border-2 border-b-blue-700">
                <form action="" wire:submit="create_category">
                    <input type="text" class="w-full" wire:model="category" placeholder="category">
                    <button
                        class="px-2 py-2 bg-yellow-600 text-slate-200 hover:bg-yellow-800 hover:text-slate-50">create</button>
                </form>
                <div class="flex flex-col justify-between">
                    @foreach ($categories as $category)
                        <div class="mb-2">
                            <li class="flex justify-between" wire:key="{{ $category->id }}">
                                <span>
                                    {{ $category->name }}
                                </span>
                                <button class="text-red-500" wire:click="delete_category({{ $category->id }})"
                                    wire:confirm="This Will Delete">&times;</button>
                            </li>
                        </div>
                    @endforeach
                </div>
            </div>
            {{-- category end --}}
            {{-- // ** --}}
            {{-- Status start --}}
            <div class="p-3 mb-4 border-2 border-b-blue-700">
                <form action="" wire:submit="create_status">
                    <input type="text" class="w-full" wire:model="status" placeholder="status">
                    <button
                        class="px-2 py-2 bg-yellow-600 text-slate-200 hover:bg-yellow-800 hover:text-slate-50">create</button>
                </form>
                <div class="flex flex-col justify-between">
                    @foreach ($statuses as $status)
                        <div class="mb-2">
                            <li class="flex justify-between" wire:key="{{ $status->id }}">
                                <span>
                                    {{ $status->name }}
                                </span>
                                <button class="text-red-500" wire:click="delete_status({{ $status->id }})"
                                    wire:confirm="This Will Delete">&times;</button>
                            </li>
                        </div>
                    @endforeach
                </div>
            </div>
            {{-- status end --}}
            {{-- // ** --}}
            {{-- design start --}}
            <div class="p-3 mb-4 border-2 border-b-blue-700">
                <form action="" wire:submit="create_design">
                    <input type="text" class="w-full" wire:model="design" placeholder="design">
                    <button
                        class="px-2 py-2 bg-yellow-600 text-slate-200 hover:bg-yellow-800 hover:text-slate-50">create</button>
                </form>
                <div class="flex flex-col justify-between">
                    @foreach ($designs as $design)
                        <div class="mb-2">
                            <li class="flex justify-between" wire:key="{{ $design->id }}">
                                <span>
                                    {{ $design->name }}
                                </span>
                                <button class="text-red-500" wire:click="delete_design({{ $design->id }})"
                                    wire:confirm="This Will Delete design {{ $design->name }}">&times;</button>
                            </li>
                        </div>
                    @endforeach
                </div>
            </div>
            {{-- design end --}}
            {{-- // ** --}}
            {{-- quality start --}}
            <div class="p-3 mb-4 border-2 border-b-blue-700">
                <form action="" wire:submit="create_quality">
                    <input type="text" class="w-full" wire:model="quality" placeholder="quality">
                    <button
                        class="px-2 py-2 bg-yellow-600 text-slate-200 hover:bg-yellow-800 hover:text-slate-50">create</button>
                </form>
                <div class="flex flex-col justify-between">
                    @foreach ($qualities as $quality)
                        <div class="mb-2">
                            <li class="flex justify-between" wire:key="{{ $quality->id }}">
                                <span>
                                    {{ $quality->name }}
                                </span>
                                <button class="text-red-500" wire:click="delete_quality({{ $quality->id }})"
                                    wire:confirm="This Will Delete quality {{ $quality->name }}">&times;</button>
                            </li>
                        </div>
                    @endforeach
                </div>
            </div>
            {{-- quality end --}}
            {{-- grade start --}}
            <div class="p-3 mb-4 border-2 border-b-blue-700">
                <form action="" wire:submit="create_grade">
                    <input type="text" class="w-full" wire:model="grade" placeholder="grade">
                    <button
                        class="px-2 py-2 bg-yellow-600 text-slate-200 hover:bg-yellow-800 hover:text-slate-50">create</button>
                </form>
                <div class="flex flex-col justify-between">
                    @foreach ($grades as $grade)
                        <div class="mb-2">
                            <li class="flex justify-between" wire:key="{{ $grade->id }}">
                                <span>
                                    {{ $grade->name }}
                                </span>
                                <button class="text-red-500" wire:click="delete_grade({{ $grade->id }})"
                                    wire:confirm="This Will Delete grade {{ $grade->name }}">&times;</button>
                            </li>
                        </div>
                    @endforeach
                </div>
            </div>
            {{-- grade end --}}
            {{-- Priority start --}}
            <div class="p-3 mb-4 border-2 border-b-blue-700">
                <form class="flex flex-col sm:flex-row gap-4" action="" wire:submit="create_priority">
                    <input type="text" class="w-full" wire:model="priority" placeholder="priority">
                    <x-color-picker wire:model='color' class="w-full sm:w-64" placeholder="Select the priority color" />
                    <button
                        class="px-2 py-2 bg-yellow-600 text-slate-200 hover:bg-yellow-800 hover:text-slate-50">create</button>
                </form>
                <div class="flex flex-col justify-between">
                    @foreach ($priorities as $priority)
                        <div class="mb-2">
                            <li class="flex justify-between bg-[{{ $priority->color }}]"
                                wire:key="{{ $priority->id }}">
                                <span>{{ $priority->color }}</span>
                                <span x-bind:class="bg - [{{ $priority->color }}]"
                                    class="bg-[{{ $priority->color }}]">
                                    {{ $priority->name }}
                                </span>
                                <button class="text-red-500" wire:click="delete_priority({{ $priority->id }})"
                                    wire:confirm="This Will Delete priority {{ $priority->name }}">&times;</button>
                            </li>
                        </div>
                    @endforeach
                </div>
            </div>
            {{-- priority end --}}
            {{-- branch start --}}
            <div class="p-3 mb-4 border-2 border-b-blue-700">
                <form action="" wire:submit="create_branch">
                    <input type="text" class="w-full" wire:model="branch" placeholder="branch">
                    <button
                        class="px-2 py-2 bg-yellow-600 text-slate-200 hover:bg-yellow-800 hover:text-slate-50">create</button>
                </form>
                <div class="flex flex-col justify-between">
                    @foreach ($branches as $branch)
                        <div class="mb-2">
                            <li class="flex justify-between" wire:key="{{ $branch->id }}">
                                <span>
                                    {{ $branch->name }}
                                </span>
                                <button class="text-red-500" wire:click="delete_branch({{ $branch->id }})"
                                    wire:confirm="This Will Delete branch {{ $branch->name }}">&times;</button>
                            </li>
                        </div>
                    @endforeach
                </div>
            </div>
            {{-- branch end --}}
        </div>
    </div>
