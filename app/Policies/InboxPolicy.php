<?php

namespace App\Policies;

use App\Enums\UserRole;

/**
 * Teklif talepleri ve iletişim mesajları: kayıtlar yalnızca site formlarından oluşur.
 * Editör görüntüler ve durum/not günceller; kişisel veri içerdiği için silme yalnızca yöneticide.
 */
abstract class InboxPolicy extends BasePolicy
{
    protected array $abilities = [
        'create' => [],
        'delete' => [UserRole::Admin],
        'deleteAny' => [UserRole::Admin],
        'forceDelete' => [UserRole::Admin],
        'forceDeleteAny' => [UserRole::Admin],
    ];

    protected function defaultRoles(): array
    {
        return [UserRole::Admin, UserRole::Editor];
    }
}
