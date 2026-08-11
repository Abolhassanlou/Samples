<?php

namespace Modules\Client\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Client\Http\Requests\ClientRequest;
use Modules\Client\Http\Resources\ClientResource;
use Modules\Client\Models\Client;

/**
 * Authorization for every mutating action here is handled entirely at
 * the route level (permission:clients.manage in routes/api.php).
 */
class ClientController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->success(ClientResource::collection(Client::orderBy('name')->get()));
    }

    public function store(ClientRequest $request)
    {
        $client = Client::create($request->validated());

        return $this->success(new ClientResource($client), 'Client created', 201);
    }

    public function update(ClientRequest $request, Client $client)
    {
        $client->update($request->validated());

        return $this->success(new ClientResource($client), 'Client updated');
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return $this->success(null, 'Client deleted');
    }
}
