<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreClientSupervisorPostRequest;
use App\Models\Client;
use App\Models\SupervisorPost;
use App\Services\Company\ManageSupervisorPostService;
use App\Support\Platform\ActingCompanyResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class ClientSupervisorPostController extends Controller
{
    public function __construct(
        private readonly ManageSupervisorPostService $posts,
    ) {}

    public function store(StoreClientSupervisorPostRequest $request, Client $client): RedirectResponse
    {
        $this->assertCompany($request, $client);
        abort_unless($client->has_supervision, 403);

        $this->posts->create($client, [
            'installation_id' => (int) $request->validated('installation_id'),
            'name' => $request->validated('name'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->backToClient($client, 'Puesto creado.');
    }

    public function update(StoreClientSupervisorPostRequest $request, Client $client, SupervisorPost $post): RedirectResponse
    {
        $this->assertPost($request, $client, $post);

        $this->posts->update($post, [
            'installation_id' => (int) $request->validated('installation_id'),
            'name' => $request->validated('name'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return $this->backToClient($client, 'Puesto actualizado.');
    }

    public function destroy(Request $request, Client $client, SupervisorPost $post): RedirectResponse
    {
        $this->assertPost($request, $client, $post);
        $this->authorize('update', $client);

        try {
            $this->posts->delete($post);
        } catch (ValidationException $e) {
            return redirect()
                ->route('company.clients.show', [$client, 'vista' => 'supervision'])
                ->with('error', $e->validator->errors()->first() ?: 'No se pudo eliminar.');
        }

        return $this->backToClient($client, 'Puesto eliminado.');
    }

    private function assertPost(Request $request, Client $client, SupervisorPost $post): void
    {
        $this->assertCompany($request, $client);
        abort_unless($client->has_supervision, 403);
        abort_unless((int) $post->client_id === (int) $client->id, 404);
    }

    private function assertCompany(Request $request, Client $client): void
    {
        if ($request->user()?->hasRole('super-admin')) {
            return;
        }

        abort_unless(
            (int) $request->user()?->security_company_id === (int) $client->security_company_id
            || app(ActingCompanyResolver::class)->id($request->user()) === (int) $client->security_company_id,
            403
        );
    }

    private function backToClient(Client $client, string $message): RedirectResponse
    {
        return redirect()
            ->route('company.clients.show', [$client, 'vista' => 'supervision'])
            ->with('success', $message);
    }
}
