<?php

namespace Modules\Organization\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Organization\Http\Requests\ClientRequest;
use Modules\Organization\Http\Resources\ClientResource;
use Modules\Organization\Models\Client;

class ClientController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $this->authorize('viewAny', Client::class);

        return $this->success(ClientResource::collection(Client::orderBy('name')->get()));
    }

    public function store(ClientRequest $request)
    {
        $this->authorize('create', Client::class);

        $client = Client::create($request->validated());

        return $this->success(new ClientResource($client), 'Client created', 201);
    }

    public function update(ClientRequest $request, Client $client)
    {
        $this->authorize('update', $client);

        $client->update($request->validated());

        return $this->success(new ClientResource($client), 'Client updated');
    }

    public function destroy(Client $client)
    {
        $this->authorize('delete', $client);

        $client->delete();

        return $this->success(null, 'Client deleted');
    }
}
