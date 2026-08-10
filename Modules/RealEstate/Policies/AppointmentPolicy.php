<?php

namespace Modules\RealEstate\Policies;

use Modules\Auth\Entities\User;
use Modules\RealEstate\Entities\Appointment;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['appointments.list', 'viewings.list']);
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->hasPermissionTo('appointments.show') || $user->hasPermissionTo('viewings.show')) {
            return true;
        }

        return $appointment->user_id === $user->id
            || $appointment->agent_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('appointments.create')
            || $user->hasPermissionTo('viewings.create');
    }

    public function update(User $user, Appointment $appointment): bool
    {
        if ($user->hasPermissionTo('appointments.edit') || $user->hasPermissionTo('viewings.edit')) {
            return true;
        }

        return $appointment->agent_id === $user->id;
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        if ($user->hasPermissionTo('appointments.delete') || $user->hasPermissionTo('viewings.delete')) {
            return true;
        }

        return $appointment->user_id === $user->id;
    }

    public function confirm(User $user, Appointment $appointment): bool
    {
        return $this->canChangeStatus($user, $appointment);
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        if ($user->hasPermissionTo('appointments.cancel') || $user->hasPermissionTo('viewings.cancel')) {
            return true;
        }

        return $appointment->agent_id === $user->id
            || $appointment->user_id === $user->id;
    }

    public function reschedule(User $user, Appointment $appointment): bool
    {
        return $this->canChangeStatus($user, $appointment);
    }

    public function complete(User $user, Appointment $appointment): bool
    {
        return $this->canChangeStatus($user, $appointment);
    }

    public function markNoShow(User $user, Appointment $appointment): bool
    {
        return $this->canChangeStatus($user, $appointment);
    }

    private function canChangeStatus(User $user, Appointment $appointment): bool
    {
        if ($user->hasPermissionTo('appointments.edit') || $user->hasPermissionTo('viewings.edit')) {
            return true;
        }

        return $appointment->agent_id === $user->id;
    }
}
