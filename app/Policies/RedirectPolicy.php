<?php

namespace App\Policies;

use App\Enums\UserRole;

/** Yönlendirmeleri editör de ekleyip düzenleyebilir; yanlış silme SEO'yu bozacağı için silme yöneticide. */
class RedirectPolicy extends ContentPolicy
{
    protected array $abilities = [
        'delete' => [UserRole::Admin],
        'deleteAny' => [UserRole::Admin],
        'forceDelete' => [UserRole::Admin],
        'forceDeleteAny' => [UserRole::Admin],
    ];
}
