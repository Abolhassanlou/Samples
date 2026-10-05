<?php

namespace Modules\Employee\Services;

use Carbon\Carbon;
use Modules\Employee\Contracts\ReservedTimeProvider;

/** Nothing is ever reserved — the fallback when no module binds a real provider. */
class NullReservedTimeProvider implements ReservedTimeProvider
{
    public function segments(int $workerId, Carbon $from, Carbon $to): array
    {
        return [];
    }
}
