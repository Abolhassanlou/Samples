<?php

namespace Modules\Transaction\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Transaction\Http\Requests\TransactionRequest;
use Modules\Transaction\Http\Resources\TransactionResource;
use Modules\Transaction\Models\Transaction;

/**
 * Authorization for every action here is handled entirely at the route
 * level (permission:clients.manage in routes/api.php) — reusing that
 * permission rather than introducing a new one, since this is
 * client-related financial data and the same people (Company Admin by
 * default) who manage clients are the natural owners of billing them.
 */
class TransactionController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->success(TransactionResource::collection(Transaction::orderByDesc('created_at')->get()));
    }

    /**
     * Manual creation — most transactions are actually created
     * automatically (see WorkLogObserver), but a dispatcher/admin can
     * also bill a client directly for something not tied to a single
     * completed shift.
     */
    public function store(TransactionRequest $request)
    {
        $transaction = Transaction::create([
            ...$request->validated(),
            'status' => 'pending',
            'created_by' => $request->user()->id,
        ]);

        return $this->success(new TransactionResource($transaction), 'Transaction created', 201);
    }

    public function markPaid(Transaction $transaction)
    {
        $transaction->update(['status' => 'paid', 'paid_at' => now()]);

        return $this->success(new TransactionResource($transaction), 'Transaction marked as paid');
    }
}
