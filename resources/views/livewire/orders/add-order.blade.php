    <div x-data="{ weight: '', size: '', counterstock: '', sell_rate: '', note: '', detail: '', qty: '' }" class="py-10 pl-10 pr-10 lg:pl-72">
        <button id="theme-toggle" type="button"
            class="text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 rounded-lg text-sm p-2.5">
            <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                xmlns="http://www.w3.org/2000/svg">
                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
            </svg>
            <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"
                    fill-rule="evenodd" clip-rule="evenodd"></path>
            </svg>
        </button>
        <div class="block p-6 bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-900 dark:border-gray-700">
            <h2 class="mb-5 text-2xl font-bold text-center dark:text-gray-200">ပစ္စည်းအော်ဒါတင်ယူရန်</h2>
            <div wire:loading class="absolute px-2 text-sm bg-blue-800 rounded text-slate-50">Searching. . . .</div>
            <div>
                <form class="p-2" wire:submit='create_order'>
                    <div class="grid grid-cols-1 mb-5 md:grid-cols-3 md:space-x-4">
                        <div class="priority-selection">
                            <label for="priority"
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Priority</label>
                            <select id="priority"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                wire:model.live='priority_id'>
                                <option value="" selected>Select Priority Level</option>
                                @foreach ($priorities as $priority)
                                    <option value="{{ $priority->id }}">{{ $priority->name }}</option>
                                @endforeach
                            </select>
                            @error('priority_id')
                                <span class="text-sm text-red-400">that's required</span>
                            @enderror
                        </div>

                        <div class="mt-3 priority-selection md:mt-0">
                            <label for="grade"
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Grade</label>
                            <select id="grade"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                wire:model.live='grade_id'>
                                <option value="" selected>Select a Grade</option>
                                @foreach ($grades as $grade)
                                    <option value="{{ $grade->id }}">{{ $grade->name }}</option>
                                @endforeach
                            </select>
                            @error('grade_id')
                                <span class="text-sm text-red-400">that's required</span>
                            @enderror
                        </div>

                        <div class="mt-3 priority-selection md:mt-0">
                            <label for="category"
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Category</label>
                            <select id="category"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                wire:model.live='category_id'>
                                <option value="" selected>Select a Category</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <span class="text-sm text-red-400">that's required</span>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 mb-5 md:grid-cols-3 md:space-x-4">
                        <div class="priority-selection">
                            <x-select label="Quality" wire:model.live="quality_id" placeholder="quality" :async-data="route('qualities.index')"
                                option-label="name" option-value="id" />
                            @error('quality_id')
                                <span class="text-sm text-red-400">that's required</span>
                            @enderror
                        </div>

                        <div class="mt-3 priority-selection md:mt-0">
                            {{-- <label for="priority" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Design</label> --}}

                            <x-select label="Design" wire:model.live="design_id" placeholder="Choose a desing"
                                :async-data="route('designs.index')" option-label="name" option-value="id" />
                            @error('design_id')
                                <span class="text-sm text-red-400">that's required</span>
                            @enderror
                        </div>

                        <div class="mt-3 priority-selection md:mt-0">
                            <label for="detail"
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Detail</label>
                            <input x-model="detail" type="text" required id="detail" autocomplete=none
                                wire:model="detail" placeholder="Detail"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 mb-5 md:grid-cols-3 md:space-x-4">
                        <div class="mb-3 priority-selection md:mt-0">
                            <label for="weight"
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Weight/Gram</label>
                            <input type="number" step=0.01 x-model="weight" required id="weight" autocomplete=none
                                wire:model.live="weight" placeholder="Weight"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                            <div id="gramToMmUnit" class="absolute text-sm text-blue-900 dark:text-blue-300" wire:ignore>
                            </div>
                        </div>

                        <div class="priority-selection">
                            <label for="size"
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Size</label>
                            <input type="text" id="size" x-model="size" required wire:model="size"
                                placeholder="Size"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                        </div>

                        <div class="mt-3 priority-selection md:mt-0">
                            <label for="qty"
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Qty</label>
                            <input x-model="qty" id="qty" type="number" required wire:model="qty"
                                placeholder="Qty"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                        </div>

                    </div>

                    <div class="grid grid-cols-1 mb-5 md:grid-cols-3 md:space-x-4">
                        <div class="mt-3 priority-selection md:mt-0">
                            <label for="stock"
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Counter Stock</label>
                            <input type="number" id="stock" x-model="counterstock" required
                                wire:model="counterstock" placeholder="Counter Stock"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                        </div>

                        <div class="flex flex-col">
                            <label for="sell_rate"
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Sell/Month</label>
                            <input type="number" id="sell_rate" x-model="sell_rate" required wire:model="sell_rate"
                                placeholder="Sell Rate"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                        </div>
                        <div class="flex flex-col">
                            <label for="branch"
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Branch</label>
                            <span required wire:model="branch_id"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">{{ auth()->user()->branch->name }}</span>
                        </div>
                    </div>

                    <div class="flex flex-col">
                        <label for="note"
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Note</label>
                        <textarea id="note" x-model="note" rows="4" wire:model="note"
                            class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            placeholder="Write your thoughts here..."></textarea>
                        @error('note')
                            <span class="text-sm text-red-400">Write your thoughts</span>
                        @enderror
                    </div>
                    <div class="my-2">
                        <div wire:loading wire:target='productImg'>
                            <span class="text-green-700">uploading . . . .</span>
                        </div>
                        <input wire:model="productImg" id="image" accept="image/jpeg,image/jpg"
                            class="my-2 text-gray-700 border border-gray-500 rounded dark:text-gray-200" type="file" />
                        @error('productImg')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                        @enderror
                        @if ($productImg)
                            <div class="w-36 h-36">
                                <img src="{{ $productImg->temporaryUrl() }}" />
                            </div>
                        @endif

                    </div>

                    <button type="button" onclick="$openModal('checkOrder')"
                        class=" first-letter:text-white bg-gradient-to-r mt-5 from-red-400 via-red-500 to-red-600 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-red-300 dark:focus:ring-red-800 shadow-lg shadow-red-500/50 dark:shadow-lg dark:shadow-red-800/80 font-medium rounded-lg px-5 py-2.5 text-center me-2 mb-2 flex items-center justify-center">
                        <img src="{{ asset('images/note.png') }}" alt="Note icon" class="w-6 h-6 mr-2">Create
                    </button>
                    <!-- Main modal -->
                    <x-modal.card blur title="အချက်အလက်များကို စစ်ဆေးပါ" wire:model='checkOrder' name="check_order">
                        <div wire:target='create_order'>
                            <div class="">
                                <!-- Modal content -->
                                <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">

                                    <!-- Modal body -->
                                    <div class="p-4 space-y-4 md:p-5">
                                        <span class="text-base leading-relaxed text-gray-500 dark:text-gray-400">
                                            <b>Priority</b>:
                                            @foreach ($priority_item as $priority)
                                                {{ $priority->name }}
                                            @endforeach
                                            <span class="text-sm text-red-400">
                                                {{ $emptyPriority }}
                                            </span>

                                        </span>
                                        <p class="text-base leading-relaxed text-gray-500 dark:text-gray-400">
                                            <b>Grade</b>:
                                            @foreach ($grade_item as $grade)
                                                {{ $grade->name }}
                                            @endforeach
                                            <span class="text-sm text-red-400">
                                                {{ $emptyGrade }}
                                            </span>
                                        </p>
                                        <p class="text-base leading-relaxed text-gray-500 dark:text-gray-400">
                                            <b>Category</b>:
                                            @foreach ($category_item as $category)
                                                {{ $category->name }}
                                            @endforeach
                                            <span class="text-sm text-red-400">
                                                {{ $emptyCategory }}
                                            </span>
                                        </p>
                                        <p class="text-base leading-relaxed text-gray-500 dark:text-gray-400">
                                            <b>Quality</b>:
                                            @foreach ($quality_item as $quality)
                                                {{ $quality->name }}
                                            @endforeach
                                            <span class="text-sm text-red-400">
                                                {{ $emptyQuality }}
                                            </span>
                                        </p>
                                        <p class="text-base leading-relaxed text-gray-500 dark:text-gray-400">
                                            <b>Design</b>:
                                            @foreach ($design_item as $design)
                                                {{ $design->name }}
                                            @endforeach
                                            <span class="text-sm text-red-400">
                                                {{ $emptyDesign }}
                                            </span>
                                        </p>
                                        <p class="text-base leading-relaxed text-gray-500 dark:text-gray-400">
                                            <b>Detail</b>: <span x-text="detail"></span>
                                        </p>
                                        <p class="text-base leading-relaxed text-gray-500 dark:text-gray-400">
                                            <b>Weight/Gram</b>: <span x-text="weight"></span>
                                        </p>
                                        <p class="text-base leading-relaxed text-gray-500 dark:text-gray-400">
                                            <b>Size</b>: <span x-text="size"></span>
                                        </p>
                                        <p class="text-base leading-relaxed text-gray-500 dark:text-gray-400">
                                            <b>Qty</b>: <span x-text="qty"></span>
                                        </p>
                                        <p class="text-base leading-relaxed text-gray-500 dark:text-gray-400">
                                            <b>Counter Stock</b>: <span x-text="counterstock"></span>
                                        </p>
                                        <p class="text-base leading-relaxed text-gray-500 dark:text-gray-400">
                                            <b>Sell/month</b>: <span x-text="sell_rate"></span>
                                        </p>
                                        <p class="text-base leading-relaxed text-gray-500 dark:text-gray-400">
                                            <b>Branch</b>: {{ auth()->user()->branch->name }}
                                            {{-- @foreach ($branch_item as $branch)
                                                 {{ $branch->name }}
                                            @endforeach
                                            <span class="text-sm text-red-400">
                                                {{ $emptyBranch }}
                                            </span> --}}
                                        </p>
                                        <p class="text-base leading-relaxed text-gray-500 dark:text-gray-400">
                                            <b>Note</b>: <span x-text="note"></span>
                                        </p>
                                    </div>
                                    <!-- Modal footer -->
                                    <x-slot name="footer">
                                        <div class="flex items-center">
                                            <button type="submit" x-on:click="close"
                                                class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">အတည်ပြုသည်</button>
                                            <button type="button"
                                                class="ms-3 text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600"
                                                x-on:click="close">ငြင်းပယ်သည်</button>
                                        </div>
                                    </x-slot>
                                </div>
                            </div>
                        </div>
                    </x-modal.card>
            </div>
            <div>
                </form>
            </div>
        </div>

        @if ($category_id)
            <div class="relative my-5 overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
                    <thead class="text-xs text-gray-100 uppercase bg-orange-400 dark:bg-orange-400 dark:text-gray-100">
                        <tr>
                            <th scope="col" class="px-6 py-3">
                                Design
                            </th>
                            <th scope="col" class="px-6 py-3">
                                weight
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Qty
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3">

                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($liveOrders as $live_order)
                            @if ($loop->first)
                                <div class="mb-3 dark:text-gray-200">Similar results found {{ $loop->count }} </div>
                            @endif
                            <a href="/order/detail?order_id={{ $live_order->id }}" wire:navigate
                                wire:key="{{ $live_order->id }}">
                                <tr class="bg-white dark:bg-gray-900">
                                    <td class="px-6 py-4">
                                        {{ $live_order->design->name }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <button
                                            onclick="mmUnitCalcReturn({{ $live_order->weight }},{{ $live_order->id }})">{{ $live_order->weight }}</button>
                                        <div id="weightId{{ $live_order->id }}"></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $live_order->qty }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $live_order->status->name }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <a href="/order/detail?order_id={{ $live_order->id }}" wire:navigate
                                            wire:key="{{ $live_order->id }}">ShowMore</a>
                                    </td>
                                </tr>
                            </a>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <p class="py-2 text-lg text-center dark:text-gray-200">Empty</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>


            {{-- {{ $liveOrders->links() }} --}}
    </div>
    @endif
    </div>

    <script>
        function toggleDiv() {
            var myDiv = document.getElementById("myDiv");
            console.log("Throunged toggle");
            myDiv.classList.toggle("hidden");
        }

        function mmUnitCalc(gramWeight) {
            let kyat = gramWeight * (1 / 16.606);
            kyat.toFixed(2)
            let answerKyat = Math.floor(kyat);
            console.log(Math.floor(kyat));

            let pae = (kyat - answerKyat) * 16;
            let answerPae = Math.floor(pae);

            let yawe = (pae - answerPae) * 8;
            let answerYawe = yawe.toFixed(2);
            if (answerKyat > 0) {
                return `${answerKyat} ကျပ် ${answerPae} ပဲ ${answerYawe} ရွေး`;
            } else if (answerPae > 0) {
                return ` ${answerPae} ပဲ ${answerYawe} ရွေး`;
            } else {
                return `${answerYawe} ရွေး`;
            }
        }

        function mmUnitCalcReturn(gramWeight, orderId) {
            let answer = mmUnitCalc(gramWeight);
            console.log("weightId" + orderId)
            document.getElementById("weightId" + orderId).innerHTML = answer;
        }
    </script>
