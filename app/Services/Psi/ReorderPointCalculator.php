<?php

namespace App\Services\Psi;

use App\Models\BranchLeadDay;
use App\Models\BranchPsiProduct;
use App\Models\FocusSale;
use App\Models\ReorderPoint;
use App\Models\PsiStock;
use App\Models\PsiSupplier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReorderPointCalculator
{
    /**
     * Recalculate and persist reorder point data for a branch product.
     *
     * @param  int  $branchPsiProductId  branch_psi_products.id
     * @param  float|null  $safetyDayOverride  When provided, uses this value instead of existing safty_day.
     * @param  float|null  $displayQtyOverride  When provided, uses this value instead of existing display_qty.
     *
     * @return array{
     *   ok:bool,
     *   created?:bool,
     *   updated?:bool,
     *   used_default_focus?:bool,
     *   psi_stock_id?:int,
     *   safty_day?:float,
     *   display_qty?:float,
     *   reorder_point?:float,
     *   reorder_due_date?:Carbon,
     *   psi_stock_status_id?:int,
     *   lead_day?:float,
     *   deliver_day?:float,
     *   order_day?:float,
     *   focus_qty?:float,
     *   inventory_balance?:float,
     *   total_day_to_sale?:float,
     *   error?:string
     * }
     */
    public function recalculate(int $branchPsiProductId, ?float $safetyDayOverride = null, ?float $displayQtyOverride = null): array
    {
        $branchProduct = BranchPsiProduct::query()->find($branchPsiProductId);
        if (!$branchProduct) {
            return ['ok' => false, 'error' => 'Branch product not found.'];
        }

        $stock = PsiStock::query()->whereBranchPsiProductId($branchPsiProductId)->first();
        if (!$stock) {
            return ['ok' => false, 'error' => 'Stock info not found.'];
        }

        $focus = FocusSale::query()
            ->whereBranchPsiProductId($branchPsiProductId)
            ->orderByDesc('id')
            ->first();

        $usedDefaultFocus = false;
        if (!$focus) {
            $focusQty = 1.0;
            $usedDefaultFocus = true;
        } else {
            $focusQty = (float) ($focus->qty ?? 0);
            if ($focusQty <= 0) {
                return ['ok' => false, 'error' => 'Focus qty is not found.'];
            }
        }

        $deliverDayRaw = BranchLeadDay::query()
            ->whereBranchPsiProductId($branchPsiProductId)
            ->value('quantity');
        $deliverDay = $deliverDayRaw !== null ? (float) $deliverDayRaw : 0.0;

        $avgLead = PsiSupplier::query()
            ->where('psi_suppliers.psi_product_id', '=', (int) $branchProduct->psi_product_id)
            ->leftJoin('psi_prices', 'psi_prices.id', '=', 'psi_suppliers.psi_price_id')
            ->avg('psi_prices.lead_day');

        if ($avgLead === null) {
            return ['ok' => false, 'error' => 'Supplier lead day is not found.'];
        }

        $orderDay = (float) $avgLead;
        $leadDay = $deliverDay + $orderDay;

        $existing = ReorderPoint::query()->wherePsiStockId((int) $stock->id)->first();
        $safetyDay = $safetyDayOverride !== null
            ? (float) $safetyDayOverride
            : (float) ($existing?->safty_day ?? 3);

        $displayQty = $displayQtyOverride !== null
            ? (float) $displayQtyOverride
            : (float) ($existing?->display_qty ?? 0);

        if ($safetyDay < 0) {
            $safetyDay = 0;
        }

        if ($displayQty < 0) {
            $displayQty = 0;
        }

        $reorderPointQty = (($leadDay + $safetyDay) * $focusQty) + $displayQty;

        $inventoryBalance = (float) ($stock->inventory_balance ?? 0);
        $netBalance = $inventoryBalance - $reorderPointQty;
        $totalDayToSale = $netBalance / $focusQty;

        if ($netBalance < 0) {
            $subDays = (int) ceil(abs($totalDayToSale));
            $dueDate = Carbon::now()->subDays($subDays);
        } else {
            $addDays = (int) $totalDayToSale;
            $dueDate = Carbon::now()->addDays($addDays);
        }

        $statusId = match (true) {
            $totalDayToSale >= 10 => 1,
            $totalDayToSale >= 6 => 2,
            $totalDayToSale > 0 && $totalDayToSale < 6 => 3,
            default => 4,
        };

        $result = DB::transaction(function () use ($existing, $stock, $safetyDay, $displayQty, $reorderPointQty, $dueDate, $statusId) {
            if ($existing) {
                $existing->update([
                    'psi_stock_id' => (int) $stock->id,
                    'safty_day' => $safetyDay,
                    'display_qty' => $displayQty,
                    'reorder_point' => $reorderPointQty,
                    'reorder_due_date' => $dueDate,
                    'psi_stock_status_id' => $statusId,
                ]);

                return ['created' => false, 'updated' => true];
            }

            ReorderPoint::create([
                'psi_stock_id' => (int) $stock->id,
                'safty_day' => $safetyDay,
                'display_qty' => $displayQty,
                'reorder_point' => $reorderPointQty,
                'reorder_due_date' => $dueDate,
                'psi_stock_status_id' => $statusId,
            ]);

            return ['created' => true, 'updated' => false];
        });

        return [
            'ok' => true,
            ...$result,
            'used_default_focus' => $usedDefaultFocus,
            'psi_stock_id' => (int) $stock->id,
            'safty_day' => $safetyDay,
            'display_qty' => $displayQty,
            'reorder_point' => $reorderPointQty,
            'reorder_due_date' => $dueDate,
            'psi_stock_status_id' => $statusId,
            'lead_day' => $leadDay,
            'deliver_day' => $deliverDay,
            'order_day' => $orderDay,
            'focus_qty' => $focusQty,
            'inventory_balance' => $inventoryBalance,
            'total_day_to_sale' => $totalDayToSale,
        ];
    }
}
