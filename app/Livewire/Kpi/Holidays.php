<?php

namespace App\Livewire\Kpi;

use App\Models\Kpi\KpiHoliday;
use App\Services\Kpi\KpiAvailabilityService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.kpi')]
class Holidays extends Component
{
    public string $month = '';
    public ?int $editingHolidayId = null;
    public string $holidayDate = '';
    public string $holidayName = '';
    public string $holidayUserId = '';
    public string $holidayRemark = '';
    public bool $holidayIsActive = true;

    public function mount(): void
    {
        Gate::authorize('kpiManageHolidays');

        $this->month = now()->format('Y-m');
        $this->holidayDate = now()->toDateString();
    }

    public function createHoliday(KpiAvailabilityService $availability): void
    {
        Gate::authorize('kpiManageHolidays');

        $validated = $this->validateHoliday();
        $this->ensureUniqueHoliday($validated['holiday_date'], $validated['user_id'] ?? null);

        $holiday = KpiHoliday::query()->create($validated);
        $availability->applyHoliday($holiday);

        $this->resetHolidayForm();
        session()->flash('message', 'Holiday created.');
    }

    public function editHoliday(int $holidayId): void
    {
        Gate::authorize('kpiManageHolidays');

        $holiday = KpiHoliday::query()->findOrFail($holidayId);

        $this->editingHolidayId = $holiday->id;
        $this->holidayDate = $holiday->holiday_date?->toDateString() ?? '';
        $this->holidayName = (string) $holiday->name;
        $this->holidayUserId = $holiday->user_id ? (string) $holiday->user_id : '';
        $this->holidayRemark = (string) ($holiday->remark ?? '');
        $this->holidayIsActive = (bool) $holiday->is_active;
    }

    public function updateHoliday(KpiAvailabilityService $availability): void
    {
        Gate::authorize('kpiManageHolidays');

        if (!$this->editingHolidayId) {
            return;
        }

        $validated = $this->validateHoliday();
        $this->ensureUniqueHoliday($validated['holiday_date'], $validated['user_id'], $this->editingHolidayId);

        $holiday = KpiHoliday::query()->findOrFail($this->editingHolidayId);
        $holiday->update($validated);

        if ($holiday->is_active) {
            $availability->applyHoliday($holiday);
        }

        $this->resetHolidayForm();
        session()->flash('message', 'Holiday updated.');
    }

    public function deleteHoliday(int $holidayId): void
    {
        Gate::authorize('kpiManageHolidays');

        KpiHoliday::query()->findOrFail($holidayId)->delete();
        $this->resetHolidayForm();

        session()->flash('message', 'Holiday deleted.');
    }

    public function cancelHoliday(): void
    {
        $this->resetHolidayForm();
    }

    public function render()
    {
        $periodStart = now()->createFromFormat('Y-m', $this->month)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        return view('livewire.kpi.holidays', [
            'holidays' => KpiHoliday::query()
                ->with('user')
                ->whereBetween('holiday_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->orderBy('holiday_date')
                ->orderBy('user_id')
                ->get(),
        ]);
    }

    protected function validateHoliday(): array
    {
        $validated = $this->validate([
            'holidayDate' => ['required', 'date'],
            'holidayName' => ['required', 'string', 'max:255'],
            'holidayUserId' => ['required', 'exists:users,id'],
            'holidayRemark' => ['nullable', 'string'],
            'holidayIsActive' => ['boolean'],
        ], [], [
            'holidayDate' => 'holiday date',
            'holidayName' => 'holiday name',
            'holidayUserId' => 'user',
            'holidayRemark' => 'remark',
        ]);

        return [
            'holiday_date' => $validated['holidayDate'],
            'name' => trim($validated['holidayName']),
            'user_id' => (int) $validated['holidayUserId'],
            'remark' => trim((string) $validated['holidayRemark']) !== '' ? trim((string) $validated['holidayRemark']) : null,
            'is_active' => (bool) $validated['holidayIsActive'],
        ];
    }

    protected function ensureUniqueHoliday(string $holidayDate, ?int $userId, ?int $ignoreId = null): void
    {
        $exists = KpiHoliday::query()
            ->whereDate('holiday_date', $holidayDate)
            ->when(
                $userId,
                fn($query) => $query->where('user_id', $userId),
                fn($query) => $query->whereNull('user_id')
            )
            ->when($ignoreId, fn($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'holidayDate' => 'A holiday already exists for this date and user scope.',
            ]);
        }
    }

    protected function resetHolidayForm(): void
    {
        $this->editingHolidayId = null;
        $this->holidayDate = now()->toDateString();
        $this->holidayName = '';
        $this->holidayUserId = '';
        $this->holidayRemark = '';
        $this->holidayIsActive = true;
        $this->resetErrorBag();
    }
}
