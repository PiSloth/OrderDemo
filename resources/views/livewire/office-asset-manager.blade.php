<div>
    <div class="mb-6 flex justify-between items-center">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">Office Assets</h2>
        <div class="flex gap-2">
            <x-button flat label="Item" wire:click="createItem" />
            <x-button flat label="Batch" wire:click="createBatch" />
            <x-button primary label="Create Asset" wire:click="createAsset" icon="plus" />
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <x-input wire:model.live="search" placeholder="Search assets..." icon="search" />
            <x-native-select wire:model.live="filterCategory">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </x-native-select>
            <x-native-select wire:model.live="filterLevel">
                <option value="all">All Levels</option>
                <option value="low">Lower Minimum Cost Level</option>
                <option value="high">Over Maximum Cost Level</option>
                <option value="healthy">Healthy Cost Level</option>
            </x-native-select>
            <x-native-select wire:model.live="filterBranch">
                <option value="">All Branches</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </x-native-select>
            <x-native-select wire:model.live="filterDepartment">
                <option value="">All Departments</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                @endforeach
            </x-native-select>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Photo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Batch</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Branch / Dept</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Balance</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($assets as $asset)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($asset->photo)
                                <img src="{{ Storage::url($asset->photo) }}" alt="{{ $asset->name }}" class="h-10 w-10 rounded-full object-cover">
                            @else
                                <div class="h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                    <x-icon name="photograph" class="h-6 w-6 text-gray-400" />
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $asset->item->name ?? $asset->name }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Cost: {{ $asset->cost === null ? '-' : number_format($asset->cost, 2) }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $asset->item->category->name ?? ($asset->category->name ?? '-') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            @if($asset->batch)
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{ $asset->batch->name }}</span>
                                    <x-button.circle xs icon="pencil" wire:click="editBatch({{ $asset->batch->id }})" title="Edit Batch" />
                                </div>
                                <div class="text-xs">
                                    Dept: {{ $asset->batch->department->name ?? '-' }}
                                </div>
                                <div class="text-xs">
                                    Cost range: {{ number_format($asset->batch->minimum_cost, 2) }} - {{ number_format($asset->batch->maximum_cost, 2) }}
                                </div>
                                <div class="text-xs">
                                    Total cost: {{ number_format($asset->batch->total_cost, 2) }}
                                </div>
                                @php
                                    $batchStatus = 'healthy';
                                    if ($asset->batch->total_cost < $asset->batch->minimum_cost) {
                                        $batchStatus = 'low';
                                    } elseif ($asset->batch->total_cost > $asset->batch->maximum_cost) {
                                        $batchStatus = 'high';
                                    }
                                @endphp
                                <div class="mt-1">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $batchStatus === 'high' ? 'bg-red-100 text-red-800' : ($batchStatus === 'low' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                                        {{ strtoupper($batchStatus) }}
                                    </span>
                                </div>
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <div>{{ $asset->branch->name ?? '-' }}</div>
                            <div class="text-xs">{{ $asset->department->name ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $asset->balance <= $asset->minimum_balance ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                {{ $asset->balance }}
                            </span>
                            @if($asset->minimum_balance)
                                <div class="text-xs text-gray-500 mt-1">Min: {{ $asset->minimum_balance }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <x-button.circle positive icon="plus" wire:click="createTransaction({{ $asset->id }}, 'in')" title="Asset In" />
                            <x-button.circle negative icon="minus" wire:click="createTransaction({{ $asset->id }}, 'out')" title="Asset Out" />
                            <x-button.circle primary icon="pencil" wire:click="editAsset({{ $asset->id }})" title="Edit" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            No assets found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4">
            {{ $assets->links() }}
        </div>
    </div>

    <!-- Asset Modal -->
    <x-modal wire:model.defer="showAssetModal">
        <x-card title="{{ $assetId ? 'Edit Asset' : 'Create Asset' }}">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2 flex gap-2 items-end">
                    <div class="flex-1">
                        <x-native-select label="Item" wire:model.defer="office_asset_item_id">
                            <option value="">Select Item</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->category->name ?? '-' }})</option>
                            @endforeach
                        </x-native-select>
                    </div>
                    <div class="pb-1">
                        <x-button flat label="New Item" wire:click="createItem" />
                    </div>
                </div>
                <x-native-select label="Batch" wire:model.defer="asset_batch_id">
                    <option value="">No Batch</option>
                    @foreach($batches as $batch)
                        <option value="{{ $batch->id }}">{{ $batch->name }} ({{ $batch->department->name ?? '-' }})</option>
                    @endforeach
                </x-native-select>
                <x-native-select label="Branch" wire:model.defer="branch_id">
                    <option value="">Select Branch</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </x-native-select>
                <x-native-select label="Department" wire:model.defer="department_id">
                    <option value="">Select Department</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </x-native-select>
                <x-input label="Cost" wire:model.defer="cost" type="number" step="0.01" />
                <x-input label="Initial Balance" wire:model.defer="balance" type="number" />
                <x-input label="Minimum Balance" wire:model.defer="minimum_balance" type="number" />
                <div class="col-span-1 md:col-span-2">
                    <x-input label="Photo" wire:model="photo" type="file" accept="image/*" />
                    @if ($photo)
                        <div class="mt-2">
                            <img src="{{ $photo->temporaryUrl() }}" class="h-20 w-20 object-cover rounded">
                        </div>
                    @endif
                </div>
            </div>
            <x-slot name="footer">
                <div class="flex justify-end gap-x-4">
                    <x-button flat label="Cancel" x-on:click="close" />
                    <x-button primary label="Save" wire:click="saveAsset" />
                </div>
            </x-slot>
        </x-card>
    </x-modal>

    <!-- Item Modal -->
    <x-modal wire:model.defer="showItemModal">
        <x-card title="{{ $itemId ? 'Edit Item' : 'Create Item' }}">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input label="Item Name" wire:model.defer="item_name" />
                <x-native-select label="Category" wire:model.defer="item_asset_category_id">
                    <option value="">Select Category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </x-native-select>
                <div class="md:col-span-2">
                    <x-input label="Item Photo" wire:model="item_photo" type="file" accept="image/*" />
                    @if ($item_photo)
                        <div class="mt-2">
                            <img src="{{ $item_photo->temporaryUrl() }}" class="h-20 w-20 object-cover rounded">
                        </div>
                    @endif
                </div>
            </div>
            <x-slot name="footer">
                <div class="flex justify-end gap-x-4">
                    <x-button flat label="Cancel" x-on:click="close" />
                    <x-button primary label="Save" wire:click="saveItem" />
                </div>
            </x-slot>
        </x-card>
    </x-modal>

    <!-- Batch Modal -->
    <x-modal wire:model.defer="showBatchModal">
        <x-card title="{{ $batchId ? 'Edit Batch' : 'Create Batch' }}">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input label="Batch Name" wire:model.defer="batch_name" />
                <x-native-select label="Department" wire:model.defer="batch_department_id">
                    <option value="">Select Department</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </x-native-select>
                <x-input label="Minimum Cost" wire:model.defer="batch_minimum_cost" type="number" step="0.01" />
                <x-input label="Maximum Cost" wire:model.defer="batch_maximum_cost" type="number" step="0.01" />
            </div>
            <x-slot name="footer">
                <div class="flex justify-end gap-x-4">
                    <x-button flat label="Cancel" x-on:click="close" />
                    <x-button primary label="Save" wire:click="saveBatch" />
                </div>
            </x-slot>
        </x-card>
    </x-modal>

    <!-- Transaction Modal -->
    <x-modal wire:model.defer="showTransactionModal">
        <x-card title="Record Transaction ({{ ucfirst($transactionType) }})">
            <div class="grid grid-cols-1 gap-4">
                <x-input label="Quantity" wire:model.defer="transactionQuantity" type="number" min="1" />
                <x-input label="Date" wire:model.defer="transactionDate" type="date" />
                <x-textarea label="Remark" wire:model.defer="transactionRemark" />
                <x-input label="Attach Image" wire:model="transactionImage" type="file" accept="image/*" />
                @if ($transactionImage)
                    <div class="mt-2">
                        <img src="{{ $transactionImage->temporaryUrl() }}" class="h-20 w-20 object-cover rounded">
                    </div>
                @endif
            </div>
            <x-slot name="footer">
                <div class="flex justify-end gap-x-4">
                    <x-button flat label="Cancel" x-on:click="close" />
                    <x-button primary label="Save" wire:click="saveTransaction" />
                </div>
            </x-slot>
        </x-card>
    </x-modal>
</div>
