<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Kullanıcı ve rol yönetimi yalnızca yöneticiye açıktır; kişi kendi hesabını silemez. */
class UserPolicy extends BasePolicy
{
    protected function defaultRoles(): array
    {
        return [UserRole::Admin];
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->isNot($model) && parent::delete($user, $model);
    }
}
