<?php

namespace App\Livewire\Operation\Branch;

use App\Models\Scope;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use WireUi\Traits\Actions;

class UserScopeConfig extends Component
{
    use Actions;

    public array $userScopes = [];
    public string $searchUser = '';
    public ?int $filterDepartmentId = null;
    public bool $showScopeModal = false;
    public ?int $editingScopeId = null;
    public string $scopeName = '';

    public function mount(): void
    {
        $this->filterDepartmentId = Auth::user()?->department_id;
        $this->loadUserScopes();
    }

    public function saveUserScopes(int $userId): void
    {
        $user = User::query()->findOrFail($userId);
        $scopeIds = collect($this->userScopes[$userId] ?? [])
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $user->scopes()->sync($scopeIds);
        $this->userScopes[$userId] = $scopeIds;

        $this->notification([
            'title' => 'Saved',
            'description' => 'User scope updated successfully.',
            'icon' => 'success',
        ]);
    }

    public function openCreateScopeModal(): void
    {
        $this->resetValidation();
        $this->reset('editingScopeId', 'scopeName');
        $this->showScopeModal = true;
    }

    public function openEditScopeModal(int $scopeId): void
    {
        $scope = Scope::query()->findOrFail($scopeId);
        $this->resetValidation();
        $this->editingScopeId = $scope->id;
        $this->scopeName = $scope->name;
        $this->showScopeModal = true;
    }

    public function closeScopeModal(): void
    {
        $this->showScopeModal = false;
        $this->resetValidation();
        $this->reset('editingScopeId', 'scopeName');
    }

    public function saveScope(): void
    {
        $scopeId = $this->editingScopeId;
        $validated = $this->validate([
            'scopeName' => ['required', 'string', 'max:255', 'unique:scopes,name,' . ($scopeId ?? 'NULL') . ',id'],
        ]);

        if ($scopeId) {
            $scope = Scope::query()->findOrFail($scopeId);
            $scope->update([
                'name' => $validated['scopeName'],
            ]);
        } else {
            Scope::query()->create([
                'name' => $validated['scopeName'],
                'is_active' => true,
            ]);
        }

        $this->closeScopeModal();
        $this->notification([
            'title' => 'Saved',
            'description' => 'Scope saved successfully.',
            'icon' => 'success',
        ]);
    }

    public function toggleScopeActive(int $scopeId): void
    {
        $scope = Scope::query()->findOrFail($scopeId);
        $scope->update([
            'is_active' => !$scope->is_active,
        ]);

        $this->notification([
            'title' => 'Updated',
            'description' => 'Scope status updated.',
            'icon' => 'success',
        ]);
    }

    public function deleteScope(int $scopeId): void
    {
        $scope = Scope::query()->findOrFail($scopeId);
        $scope->delete();

        if ($this->editingScopeId === $scopeId) {
            $this->closeScopeModal();
        }

        $this->loadUserScopes();
        $this->notification([
            'title' => 'Deleted',
            'description' => 'Scope deleted successfully.',
            'icon' => 'success',
        ]);
    }

    protected function loadUserScopes(): void
    {
        $this->userScopes = User::query()
            ->with('scopes:id')
            ->get(['id'])
            ->mapWithKeys(function (User $user) {
                return [$user->id => $user->scopes->pluck('id')->map(fn($id) => (int) $id)->values()->all()];
            })
            ->toArray();
    }

    public function render()
    {
        $viewer = Auth::user();
        $search = trim($this->searchUser);
        $isSuperAdmin = (string) ($viewer?->role ?? '') === 'superadmin';

        return view('livewire.operation.branch.user-scope-config', [
            'users' => User::query()
                ->with(['branch:id,name', 'department:id,name', 'scopes:id,name'])
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    });
                })
                ->when($isSuperAdmin, function ($query) {
                    if ($this->filterDepartmentId) {
                        $query->where('department_id', $this->filterDepartmentId);
                    }
                }, function ($query) use ($viewer) {
                    $query->where('department_id', $viewer?->department_id);
                })
                ->orderBy('name')
                ->get(),
            'scopeOptions' => Scope::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'departments' => Department::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'scopes' => Scope::query()
                ->orderBy('name')
                ->get(['id', 'name', 'is_active', 'created_at']),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }
}
