<?php

namespace App\Policies;

use App\Enums\UserRole;

/** İçerik modülleri: yönetici ve editör düzenler; kalıcı silme yalnızca yöneticide. */
abstract class ContentPolicy extends BasePolicy
{
    protected array $abilities = [
        'forceDelete' => [UserRole::Admin],
        'forceDeleteAny' => [UserRole::Admin],
    ];

    protected function defaultRoles(): array
    {
        return [UserRole::Admin, UserRole::Editor];
    }
}
