<?php

namespace App\Http\Controllers\JewelrySales;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DigitalAsset\SoftwareAccountAuditLog;
use App\Models\Region;
use App\Models\Township;
use App\Services\DigitalAsset\SoftwareAccountAuditService;
use App\Services\JewelrySales\BranchMappingStore;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class JewelrySalesCustomerController extends Controller
{
    public function index(Request $request, BranchMappingStore $mappingStore): Response
    {
        $this->authorizeCustomerPermission($request, 'customer.view', 'Unauthorized to view customers.');

        ['query' => $query, 'filters' => $filters] = $this->buildCustomerQuery($request, $mappingStore);

        $customers = $query->paginate(25)->withQueryString();

        $stats = [
            'total_customers' => Customer::count(),
            'repeat_customers' => Customer::has('sales', '>=', 2)->count(),
            'with_phone' => Customer::whereNotNull('phone_normalized')->count(),
            'with_region' => Customer::whereNotNull('region_id')->count(),
        ];

        return Inertia::render('JewelrySales/Customers', [
            'customers' => $customers,
            'stats' => $stats,
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
            'regions' => Region::orderBy('name')->get(['id', 'name', 'name_mm']),
            'townships' => Township::orderBy('name')->get(['id', 'region_id', 'name', 'name_mm']),
            'filters' => $filters,
        ]);
    }

    public function export(Request $request, BranchMappingStore $mappingStore): JsonResponse
    {
        $this->authorizeCustomerPermission($request, 'customer.export', 'Unauthorized to export customers.');

        ['query' => $query, 'selectedBranch' => $selectedBranch, 'filters' => $filters] = $this->buildCustomerQuery($request, $mappingStore);

        $customers = $query->get()->map(static fn (Customer $c) => [
            'name' => $c->name,
            'phone' => $c->phone_normalized ?: ($c->phone_raw ?: '-'),
            'sales_count' => (int) ($c->sales_count ?? 0),
        ])->values();

        SoftwareAccountAuditService::log(
            action: 'jewelry.customers.export',
            entityType: 'Customer',
            entityId: $selectedBranch?->id,
            softwareAccountId: null,
            outcome: 'success',
            metadata: [
                'branch_name' => $selectedBranch?->name,
                'filters' => $filters,
                'exported_count' => $customers->count(),
            ],
            actorUserId: $request->user()?->id
        );

        return response()->json([
            'customers' => $customers,
            'branch_name' => $selectedBranch?->name,
            'total' => $customers->count(),
        ]);
    }

    public function exportLogs(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless(
            $user && ($user->isSuperUser() || $user->isAdmin() || $user->can('customer.view') || $user->can('customer.export')),
            403,
            'Unauthorized to view customer export logs.'
        );

        $logs = SoftwareAccountAuditLog::query()
            ->with('actorUser:id,name,email')
            ->where('action', 'jewelry.customers.export')
            ->orderByDesc('occurred_at')
            ->limit(50)
            ->get()
            ->map(static fn (SoftwareAccountAuditLog $log) => [
                'id' => $log->id,
                'actor_name' => $log->actorUser?->name ?? 'System',
                'actor_email' => $log->actorUser?->email,
                'branch_name' => $log->metadata['branch_name'] ?? 'All Branches',
                'exported_count' => (int) ($log->metadata['exported_count'] ?? 0),
                'filters' => $log->metadata['filters'] ?? [],
                'ip_address' => $log->ip_address,
                'occurred_at' => $log->occurred_at?->format('Y-m-d H:i:s'),
            ])
            ->values();

        return response()->json([
            'logs' => $logs,
        ]);
    }

    /**
     * @return array{query: Builder, selectedBranch: ?Branch, filters: array<string, mixed>}
     */
    private function buildCustomerQuery(Request $request, BranchMappingStore $mappingStore): array
    {
        $search = trim((string) $request->input('search', ''));
        $branchId = $request->input('branch_id');
        $regionId = $request->input('region_id');
        $townshipId = $request->input('township_id');
        $segment = $request->input('segment'); // 'repeat' | 'one_time' | null
        $sort = $request->input('sort', 'total_spent');

        $selectedBranch = $branchId ? Branch::find((int) $branchId) : null;
        $branchSalesFilter = $selectedBranch
            ? $this->buildBranchSalesFilter($selectedBranch, $mappingStore)
            : null;
        $salesConstraint = $branchSalesFilter ?? static fn ($q) => $q;

        $query = Customer::query()
            ->with(['region:id,name,name_mm', 'township:id,name,name_mm'])
            ->withCount(['sales' => $salesConstraint])
            ->withSum(['sales as total_spent' => $salesConstraint], 'invoice_total')
            ->withSum(['sales as total_weight_g' => $salesConstraint], 'invoice_total_weight_g')
            ->withMax(['sales as last_sale_at' => $salesConstraint], 'sale_at');

        if ($branchId && !$selectedBranch) {
            $query->whereRaw('1 = 0');
        } elseif ($branchSalesFilter !== null) {
            $query->whereHas('sales', $branchSalesFilter);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone_normalized', 'like', "%{$search}%")
                    ->orWhere('phone_raw', 'like', "%{$search}%")
                    ->orWhere('address_raw', 'like', "%{$search}%");
            });
        }

        if ($regionId) {
            $query->where('region_id', $regionId);
        }

        if ($townshipId) {
            $query->where('township_id', $townshipId);
        }

        if ($segment === 'repeat') {
            $branchSalesFilter !== null
                ? $query->whereHas('sales', $branchSalesFilter, '>=', 2)
                : $query->has('sales', '>=', 2);
        } elseif ($segment === 'one_time') {
            $branchSalesFilter !== null
                ? $query->whereHas('sales', $branchSalesFilter, '=', 1)
                : $query->has('sales', '=', 1);
        }

        match ($sort) {
            'invoices' => $query->orderByDesc('sales_count'),
            'weight' => $query->orderByDesc('total_weight_g'),
            'recent' => $query->orderByDesc('last_sale_at'),
            default => $query->orderByDesc('total_spent'),
        };

        return [
            'query' => $query,
            'selectedBranch' => $selectedBranch,
            'filters' => [
                'search' => $search,
                'branch_id' => $branchId ? (string) $branchId : '',
                'region_id' => $regionId,
                'township_id' => $townshipId,
                'segment' => $segment,
                'sort' => $sort,
            ],
        ];
    }

    public function show(Request $request, Customer $customer): Response
    {
        $this->authorizeCustomerPermission($request, 'customer.view', 'Unauthorized to view customer details.');

        $customer->load(['region:id,name,name_mm', 'township:id,name,name_mm']);

        $sales = $customer->sales()
            ->with(['dutyStaff:id,name', 'inputStaff:id,name', 'items.product'])
            ->orderByDesc('sale_at')
            ->get();

        $summary = [
            'invoices_count' => $sales->count(),
            'total_spent' => (float) $sales->sum('invoice_total'),
            'total_weight_g' => (float) $sales->sum('invoice_total_weight_g'),
            'first_sale_at' => $sales->min('sale_at'),
            'last_sale_at' => $sales->max('sale_at'),
        ];

        return Inertia::render('JewelrySales/CustomerShow', [
            'customer' => $customer,
            'sales' => $sales,
            'summary' => $summary,
        ]);
    }

    private function authorizeCustomerPermission(Request $request, string $permission, string $message): void
    {
        $user = $request->user();
        abort_unless(
            $user && ($user->isSuperUser() || $user->isAdmin() || $user->can($permission)),
            403,
            $message
        );
    }

    /**
     * Build a sales query constraint matching a canonical branch and all of its mapped POS aliases.
     */
    private function buildBranchSalesFilter(Branch $branch, BranchMappingStore $mappingStore): Closure
    {
        $storeMappings = $mappingStore->all();
        $branchId = (int) $branch->id;
        $branchNameNormalized = $mappingStore->normalizeAlias($branch->name);

        $aliases = [];
        if ($branchNameNormalized !== '') {
            $aliases[] = $branchNameNormalized;
        }

        foreach ($storeMappings as $alias => $mappedBranchId) {
            if ((int) $mappedBranchId === $branchId) {
                $aliases[] = $alias;
            }
        }

        foreach (config('jewelry_sales.branch_aliases', []) as $alias => $targetName) {
            $normalizedAlias = $mappingStore->normalizeAlias((string) $alias);
            if ($normalizedAlias === '') {
                continue;
            }

            if (isset($storeMappings[$normalizedAlias]) && (int) $storeMappings[$normalizedAlias] !== $branchId) {
                continue;
            }

            if ($mappingStore->normalizeAlias((string) $targetName) === $branchNameNormalized) {
                $aliases[] = $normalizedAlias;
            }
        }

        $aliases = array_values(array_unique(array_filter($aliases)));

        return static function ($q) use ($branchId, $aliases) {
            $q->where(function ($sub) use ($branchId, $aliases) {
                $sub->where('branch_id', $branchId);
                if ($aliases !== []) {
                    $sub->orWhereIn(DB::raw('LOWER(TRIM(branch_name_raw))'), $aliases);
                }
            });
        };
    }
}
