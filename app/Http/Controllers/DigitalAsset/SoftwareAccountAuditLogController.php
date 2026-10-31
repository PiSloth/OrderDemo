<?php

namespace App\Http\Controllers\DigitalAsset;

use App\Http\Controllers\Controller;
use App\Models\DigitalAsset\SoftwareAccount;
use App\Models\DigitalAsset\SoftwareAccountAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SoftwareAccountAuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $user = auth()->user();
        $canView = $user->isSuperUser()
            || $user->isAdmin()
            || $user->isFromItDepartment()
            || $user->can('security_audit_trial.view')
            || $user->can('security_audit_trail.view')
            || $user->can('security-audit-trial.view')
            || $user->can('security-audit-trail.view')
            || $user->can('audit_trail.view')
            || $user->can('audit.view')
            || $user->can('digital_asset.audit.view');

        if (!$canView) {
            abort(403, 'Unauthorized. You do not have permission to view security audit logs.');
        }

        $query = SoftwareAccountAuditLog::with(['account.platform', 'actorUser']);

        if ($request->filled('action')) {
            $query->where('action', 'like', "%" . $request->input('action') . "%");
        }

        if ($request->filled('outcome')) {
            $query->where('outcome', $request->input('outcome'));
        }

        if ($request->filled('software_account_id')) {
            $query->where('software_account_id', $request->input('software_account_id'));
        }

        if ($request->filled('actor_user_id')) {
            $query->where('actor_user_id', $request->input('actor_user_id'));
        }

        $logs = $query->orderBy('occurred_at', 'desc')->paginate(20)->withQueryString();

        $accounts = SoftwareAccount::orderBy('account_name')->get(['id', 'account_name', 'username']);
        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        return Inertia::render('DigitalAsset/AuditLogs/Index', [
            'logs' => $logs,
            'accounts' => $accounts,
            'users' => $users,
            'filters' => $request->only(['action', 'outcome', 'software_account_id', 'actor_user_id']),
        ]);
    }
}
