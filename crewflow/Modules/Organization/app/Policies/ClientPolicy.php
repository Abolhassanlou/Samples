<?php

namespace Modules\Organization\Policies;

use Modules\Core\Models\User;
use Modules\Organization\Models\Client;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Client $client): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('clients.manage');
    }

    public function update(User $user, Client $client): bool
    {
        return $user->can('clients.manage');
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->can('clients.manage');
    }
}
