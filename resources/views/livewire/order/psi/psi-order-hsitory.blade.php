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
                        QC Passed
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
                @forelse ($orders as $job)
                    <tr
                        class="border-b odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 dark:border-gray-700">
                        <th scope="row"
                            class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            <img src="{{ asset('storage/' . $job->branchPsiProduct->psiProduct->productPhoto->image) }}"
                                class="w-16 max-w-full max-h-full md:w-32 cursor-help " />

                        </th>
                        <td class="px-6 py-4">
                            {{ $job->arrival_qty }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $job->qc_passed_qty }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $job->psiStatus->name }}
                        </td>
                        <td class="px-6 py-4">
                            @switch($job->psi_status_id)
                                @case(5)
                                    <x-button outline red wire:click='startRegisteration({{ $job->id }})'
                                        label="ကုဒ်သွင်းခြင်း စတင်နေသည်" />
                                @break

                                @case(6)
                                    <x-button outline sky
                                        wire:click='endRegisteration({{ $job->id }},{{ $job->psi_order_id }})'
                                        label="ကုဒ်သွင်း၍ ပြီးစီးပြီ" />
                                @break

                                @case(8)
                                    <x-button outline sky wire:click='receiveByBranch({{ $job->id }})'
                                        label="ပစ္စည်းလက်ခံရရှိပြီ" />
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
