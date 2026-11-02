<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Filament'in kullandığı tüm yetenekleri tek bir allows() kararına bağlar.
 * Alt sınıflar her yetenek için hangi rollerin izinli olduğunu $abilities ile tanımlar.
 */
abstract class BasePolicy
{
    /** @var array<string, array<int, UserRole>> yetenek => izinli roller; tanımsız yetenek için kullanılır */
    protected array $abilities = [];

    /** @return array<int, UserRole> */
    abstract protected function defaultRoles(): array;

    public function before(User $user): ?bool
    {
        return $user->is_active ? null : false;
    }

    protected function allows(User $user, string $ability): bool
    {
        $roles = $this->abilities[$ability] ?? $this->defaultRoles();

        return in_array($user->role, $roles, true);
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'viewAny');
    }

    public function view(User $user, Model $model): bool
    {
        return $this->allows($user, 'view');
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'create');
    }

    public function update(User $user, Model $model): bool
    {
        return $this->allows($user, 'update');
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->allows($user, 'delete');
    }

    public function deleteAny(User $user): bool
    {
        return $this->allows($user, 'deleteAny');
    }

    public function restore(User $user, Model $model): bool
    {
        return $this->allows($user, 'restore');
    }

    public function restoreAny(User $user): bool
    {
        return $this->allows($user, 'restoreAny');
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return $this->allows($user, 'forceDelete');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->allows($user, 'forceDeleteAny');
    }

    public function reorder(User $user): bool
    {
        return $this->allows($user, 'reorder');
    }
}
