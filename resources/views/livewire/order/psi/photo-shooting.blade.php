<div>
    <div class="relative mb-8 overflow-x-auto shadow-md sm:rounded-lg">
        <h1 class="text-xl">ဇာတ်ပုံရိုက်ရန် အကြောင်းကြားထားသော ပစ္စည်းများ</h1>
        <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        ပစ္စည်းအမည်
                    </th>
                    <th scope="col" class="px-6 py-3">
                        ရောက်ရှိ အရေအတွက်
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Schedule
                    </th>
                    <th scope="col" class="px-6 py-4">
                        အခြေအနေ
                    </th>
                    {{-- <th scope="col" class="px-6 py-4">
                        Effective within 1Hr
                    </th> --}}

                    <th scope="col" class="px-6 py-3">
                        Action
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($jobs as $job)
                    <tr
                        class="border-b odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 dark:border-gray-700">
                        <th scope="row"
                            class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            <img src="{{ asset('storage/' . $job->psiOrder->branchPsiProduct->psiProduct->productPhoto->image) }}"
                                class="w-16 max-w-full max-h-full md:w-32 cursor-help " />

                        </th>
                        <td class="px-6 py-4">
                            {{ $job->psiOrder->arrival_qty }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $job->schedule_date }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $job->photoShootingStatus->name }}
                        </td>
                        <td class="px-6 py-4">
                            @switch($job->photoShootingStatus->id)
                                @case(2)
                                    <x-button outline red
                                        wire:click='statusAction({{ $job->id }},{{ $job->psi_order_id }},3)'
                                        label="Marketing သို့လွှဲပြောင်း" />
                                @break

                                @case(3)
                                    <x-button outline sky
                                        wire:click='statusAction({{ $job->id }},{{ $job->psi_order_id }},4)'
                                        label="Marketing မှ ပစ္စည်းလက်ခံ" />
                                @break

                                @case(4)
                                    <x-button outline sky
                                        wire:click='statusAction({{ $job->id }},{{ $job->psi_order_id }},5)'
                                        label="Inv သို့ ပြန်အပ်" />
                                @break

                                @case(5)
                                    <x-button outline red
                                        wire:click='statusAction({{ $job->id }},{{ $job->psi_order_id }},6)'
                                        label="Inv က ပြန်ယူ" />
                                @break

                                @default
                                @break
                            @endswitch
                        </td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="5">

                                <center>There's no records yet</center>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
