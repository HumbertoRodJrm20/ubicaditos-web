<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class DriverDeactivationService
{
    public function deactivate(User $driver): bool
    {
        return DB::transaction(function () use ($driver) {
            $driver = User::query()->lockForUpdate()->findOrFail($driver->getKey());

            if ($driver->trips()->where('status', 'in_progress')->exists()) {
                return false;
            }

            $driver->drivingBus()->update([
                'driver_id' => null,
                'is_active' => false,
                'status' => 'inactive',
            ]);

            $driver->update(['is_active' => false]);

            return true;
        });
    }
}
