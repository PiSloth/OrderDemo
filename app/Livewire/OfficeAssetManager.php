<?php

namespace App\Livewire;

use App\Models\AssetCategory;
use App\Models\AssetBatch;
use App\Models\Branch;
use App\Models\Department;
use App\Models\OfficeAsset;
use App\Models\OfficeAssetItem;
use App\Models\OfficeAssetTransaction;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use WireUi\Traits\Actions;
use Illuminate\Validation\Rule;

class OfficeAssetManager extends Component
{
    use WithFileUploads, WithPagination, Actions;

    public $search = '';
    public $filterBranch = '';
    public $filterDepartment = '';
    public $filterCategory = '';

    public $filterLevel = 'all';

    // Asset Form
    public $showAssetModal = false;
    public $assetId;
    public $asset_batch_id;
    public $office_asset_item_id;
    public $asset_category_id;
    public $branch_id;
    public $department_id;
    public $name;
    public $photo;
    public $cost;
    public $balance = 0;
    public $minimum_balance;

    // Item Form
    public $showItemModal = false;
    public $itemId;
    public $item_asset_category_id;
    public $item_name;
    public $item_photo;

    // Batch Form
    public $showBatchModal = false;
    public $batchId;
    public $batch_name;
    public $batch_department_id;
    public $batch_minimum_cost;
    public $batch_maximum_cost;

    // Transaction Form
    public $showTransactionModal = false;
    public $transactionAssetId;
    public $transactionType = 'in';
    public $transactionQuantity;
    public $transactionDate;
    public $transactionRemark;
    public $transactionImage;

    public function mount()
    {
        $this->transactionDate = date('Y-m-d');
    }

    public function rules()
    {
        return [
            'asset_batch_id' => 'nullable|exists:asset_batches,id',
            'office_asset_item_id' => 'required|exists:office_asset_items,id',
            // kept for backward compatibility, but UI should rely on item
            'asset_category_id' => 'nullable|exists:asset_categories,id',
            'branch_id' => 'required|exists:branches,id',
            'department_id' => 'required|exists:departments,id',
            // name comes from item; keep nullable here
            'name' => 'nullable|string|max:255',
            'photo' => 'nullable|image|max:2048',
            'cost' => 'nullable|numeric|min:0',
            'balance' => 'required|integer|min:0',
            'minimum_balance' => 'nullable|integer|min:0',
        ];
    }

    protected function locationUniqueRule()
    {
        return Rule::unique('office_assets', 'office_asset_item_id')
            ->where(
                fn($q) => $q
                    ->where('office_asset_item_id', $this->office_asset_item_id)
                    ->where('branch_id', $this->branch_id)
                    ->where('department_id', $this->department_id)
            )
            ->ignore($this->assetId);
    }

    public function itemRules()
    {
        return [
            'item_asset_category_id' => 'required|exists:asset_categories,id',
            'item_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('office_asset_items', 'name')
                    ->where(fn($q) => $q->where('asset_category_id', $this->item_asset_category_id))
                    ->ignore($this->itemId),
            ],
            'item_photo' => 'nullable|image|max:2048',
        ];
    }

    public function batchRules()
    {
        return [
            'batch_name' => 'required|string|max:255',
            'batch_department_id' => 'required|exists:departments,id',
            'batch_minimum_cost' => 'required|numeric|min:0',
            'batch_maximum_cost' => 'required|numeric|min:0|gte:batch_minimum_cost',
        ];
    }

    public function createAsset()
    {
        $this->resetAssetForm();
        $this->showAssetModal = true;
    }

    public function editAsset($id)
    {
        $this->resetAssetForm();
        $asset = OfficeAsset::with('item')->findOrFail($id);
        $this->assetId = $asset->id;
        $this->asset_batch_id = $asset->asset_batch_id;
        $this->office_asset_item_id = $asset->office_asset_item_id;
        $this->asset_category_id = $asset->asset_category_id;
        $this->branch_id = $asset->branch_id;
        $this->department_id = $asset->department_id;
        $this->name = $asset->item->name ?? $asset->name;
        $this->cost = $asset->cost;
        $this->balance = $asset->balance;
        $this->minimum_balance = $asset->minimum_balance;
        $this->showAssetModal = true;
    }

    public function saveAsset()
    {
        $this->validate();
        $this->validate([
            'office_asset_item_id' => [$this->locationUniqueRule()],
        ]);

        $previousBatchId = null;
        if ($this->assetId) {
            $previousBatchId = OfficeAsset::whereKey($this->assetId)->value('asset_batch_id');
        }

        $photoPath = null;
        if ($this->photo) {
            $photoPath = $this->photo->store('office_assets', 'public');
        }

        $item = OfficeAssetItem::with('category')->findOrFail($this->office_asset_item_id);

        if ($this->assetId) {
            $asset = OfficeAsset::findOrFail($this->assetId);
            $data = [
                'asset_batch_id' => $this->asset_batch_id,
                'office_asset_item_id' => $this->office_asset_item_id,
                'branch_id' => $this->branch_id,
                'department_id' => $this->department_id,
                // denormalized copy for backward compatibility
                'name' => $item->name,
                'asset_category_id' => $item->asset_category_id,
                'cost' => $this->cost,
                'balance' => $this->balance,
                'minimum_balance' => $this->minimum_balance,
            ];
            if ($photoPath) {
                $data['photo'] = $photoPath;
            }
            $asset->update($data);

            $this->recalculateBatchTotalCost($asset->asset_batch_id);
            if ($previousBatchId && $previousBatchId !== $asset->asset_batch_id) {
                $this->recalculateBatchTotalCost($previousBatchId);
            }

            $this->notification()->success('Asset updated successfully.');
        } else {
            $asset = OfficeAsset::create([
                'asset_batch_id' => $this->asset_batch_id,
                'office_asset_item_id' => $this->office_asset_item_id,
                'branch_id' => $this->branch_id,
                'department_id' => $this->department_id,
                'name' => $item->name,
                'asset_category_id' => $item->asset_category_id,
                'photo' => $photoPath,
                'cost' => $this->cost,
                'balance' => $this->balance,
                'minimum_balance' => $this->minimum_balance,
            ]);

            $this->recalculateBatchTotalCost($asset->asset_batch_id);

            $this->notification()->success('Asset created successfully.');
        }

        $this->showAssetModal = false;
        $this->resetAssetForm();
    }

    public function resetAssetForm()
    {
        $this->reset(['assetId', 'asset_batch_id', 'office_asset_item_id', 'asset_category_id', 'branch_id', 'department_id', 'name', 'photo', 'cost', 'balance', 'minimum_balance']);
        $this->resetValidation();
    }

    public function createItem()
    {
        $this->resetItemForm();
        $this->showItemModal = true;
    }

    public function editItem($id)
    {
        $this->resetItemForm();
        $item = OfficeAssetItem::findOrFail($id);
        $this->itemId = $item->id;
        $this->item_asset_category_id = $item->asset_category_id;
        $this->item_name = $item->name;
        $this->showItemModal = true;
    }

    public function saveItem()
    {
        $this->validate($this->itemRules());

        $photoPath = null;
        if ($this->item_photo) {
            $photoPath = $this->item_photo->store('office_asset_items', 'public');
        }

        if ($this->itemId) {
            $item = OfficeAssetItem::findOrFail($this->itemId);
            $data = [
                'asset_category_id' => $this->item_asset_category_id,
                'name' => $this->item_name,
            ];
            if ($photoPath) {
                $data['photo'] = $photoPath;
            }
            $item->update($data);
            $this->notification()->success('Item updated successfully.');
            $this->office_asset_item_id = $item->id;
        } else {
            $item = OfficeAssetItem::create([
                'asset_category_id' => $this->item_asset_category_id,
                'name' => $this->item_name,
                'photo' => $photoPath,
            ]);
            $this->notification()->success('Item created successfully.');
            $this->office_asset_item_id = $item->id;
        }

        $this->showItemModal = false;
        $this->resetItemForm();
    }

    public function resetItemForm()
    {
        $this->reset(['itemId', 'item_asset_category_id', 'item_name', 'item_photo']);
        $this->resetValidation();
    }

    public function createBatch()
    {
        $this->resetBatchForm();
        $this->showBatchModal = true;
    }

    public function editBatch($id)
    {
        $this->resetBatchForm();
        $batch = AssetBatch::findOrFail($id);
        $this->batchId = $batch->id;
        $this->batch_name = $batch->name;
        $this->batch_department_id = $batch->department_id;
        $this->batch_minimum_cost = $batch->minimum_cost;
        $this->batch_maximum_cost = $batch->maximum_cost;
        $this->showBatchModal = true;
    }

    public function saveBatch()
    {
        $this->validate($this->batchRules());

        if ($this->batchId) {
            $batch = AssetBatch::findOrFail($this->batchId);
            $batch->update([
                'name' => $this->batch_name,
                'department_id' => $this->batch_department_id,
                'minimum_cost' => $this->batch_minimum_cost,
                'maximum_cost' => $this->batch_maximum_cost,
            ]);
            $this->notification()->success('Batch updated successfully.');
        } else {
            AssetBatch::create([
                'name' => $this->batch_name,
                'department_id' => $this->batch_department_id,
                'minimum_cost' => $this->batch_minimum_cost,
                'maximum_cost' => $this->batch_maximum_cost,
                'total_cost' => 0,
            ]);
            $this->notification()->success('Batch created successfully.');
        }

        $this->showBatchModal = false;
        $this->resetBatchForm();
    }

    public function resetBatchForm()
    {
        $this->reset(['batchId', 'batch_name', 'batch_department_id', 'batch_minimum_cost', 'batch_maximum_cost']);
        $this->resetValidation();
    }

    public function createTransaction($assetId, $type)
    {
        $this->resetTransactionForm();
        $this->transactionAssetId = $assetId;
        $this->transactionType = $type;
        $this->showTransactionModal = true;
    }

    public function saveTransaction()
    {
        $this->validate([
            'transactionAssetId' => 'required|exists:office_assets,id',
            'transactionType' => 'required|in:in,out',
            'transactionQuantity' => 'required|integer|min:1',
            'transactionDate' => 'required|date',
            'transactionRemark' => 'nullable|string',
            'transactionImage' => 'nullable|image|max:2048',
        ]);

        $asset = OfficeAsset::findOrFail($this->transactionAssetId);

        if ($this->transactionType === 'out' && $asset->balance < $this->transactionQuantity) {
            $this->notification()->error('Insufficient balance.');
            return;
        }

        $imagePath = null;
        if ($this->transactionImage) {
            $imagePath = $this->transactionImage->store('office_asset_transactions', 'public');
        }

        OfficeAssetTransaction::create([
            'office_asset_id' => $this->transactionAssetId,
            'type' => $this->transactionType,
            'quantity' => $this->transactionQuantity,
            'date' => $this->transactionDate,
            'remark' => $this->transactionRemark,
            'image' => $imagePath,
            'user_id' => auth()->id(),
        ]);

        if ($this->transactionType === 'in') {
            $asset->increment('balance', $this->transactionQuantity);
        } else {
            $asset->decrement('balance', $this->transactionQuantity);
        }

        $this->recalculateBatchTotalCost($asset->asset_batch_id);

        $this->notification()->success('Transaction recorded successfully.');
        $this->showTransactionModal = false;
        $this->resetTransactionForm();
    }

    public function resetTransactionForm()
    {
        $this->reset(['transactionAssetId', 'transactionType', 'transactionQuantity', 'transactionRemark', 'transactionImage']);
        $this->transactionDate = date('Y-m-d');
        $this->resetValidation();
    }

    protected function recalculateBatchTotalCost($batchId): void
    {
        if (!$batchId) {
            return;
        }

        $total = OfficeAsset::query()
            ->where('asset_batch_id', $batchId)
            ->selectRaw('COALESCE(SUM(COALESCE(cost, 0) * balance), 0) as total_cost')
            ->value('total_cost');

        AssetBatch::whereKey($batchId)->update([
            'total_cost' => $total ?? 0,
        ]);
    }

    public function render()
    {
        $assets = OfficeAsset::with(['item.category', 'category', 'branch', 'department', 'batch.department'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->whereHas('item', function ($iq) {
                        $iq->where('name', 'like', '%' . $this->search . '%');
                    })->orWhere('name', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterBranch, function ($query) {
                $query->where('branch_id', $this->filterBranch);
            })
            ->when($this->filterDepartment, function ($query) {
                $query->where('department_id', $this->filterDepartment);
            })
            ->when($this->filterCategory, function ($query) {
                $query->where(function ($q) {
                    $q->whereHas('item', function ($iq) {
                        $iq->where('asset_category_id', $this->filterCategory);
                    })->orWhere('asset_category_id', $this->filterCategory);
                });
            })
            ->when($this->filterLevel !== 'all', function ($query) {
                if ($this->filterLevel === 'low') {
                    $query->whereHas('batch', function ($bq) {
                        $bq->whereColumn('total_cost', '<', 'minimum_cost');
                    });
                }
                if ($this->filterLevel === 'high') {
                    $query->whereHas('batch', function ($bq) {
                        $bq->whereColumn('total_cost', '>', 'maximum_cost');
                    });
                }
                if ($this->filterLevel === 'healthy') {
                    $query->whereHas('batch', function ($bq) {
                        $bq->whereColumn('total_cost', '>=', 'minimum_cost')
                            ->whereColumn('total_cost', '<=', 'maximum_cost');
                    });
                }
            })
            ->paginate(10);

        return view('livewire.office-asset-manager', [
            'assets' => $assets,
            'categories' => AssetCategory::all(),
            'items' => OfficeAssetItem::with('category')->orderBy('name')->get(),
            'batches' => AssetBatch::with('department')->orderBy('name')->get(),
            'branches' => Branch::all(),
            'departments' => Department::all(),
        ]);
    }
}
