<?php

declare(strict_types=1);

namespace Tests\Feature\Company;

use App\Models\Client;
use App\Models\SupervisorPost;
use App\Models\SupervisorPostModality;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SupervisorPostModalityCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_can_add_custom_hours_and_assign_to_post(): void
    {
        $this->seedWithPilot();
        $user = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $companyId = (int) $user->security_company_id;

        $this->actingAs($user)
            ->post(route('company.supervision-post-modalities.store'), [
                'hours' => 9,
                'name' => 'Pedido cliente',
                'is_active' => 1,
            ])
            ->assertRedirect(route('company.supervision-post-modalities.index'));

        $this->assertDatabaseHas('supervisor_post_modalities', [
            'security_company_id' => $companyId,
            'hours' => 9,
            'name' => 'Pedido cliente',
        ]);

        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();
        $installation = $client->installations()->firstOrFail();

        $this->actingAs($user)->post(route('company.clients.posts.store', $client), [
            'installation_id' => $installation->id,
            'name' => 'Puesto 9h',
            'modality' => 9,
            'vista' => 'sitio',
        ])->assertRedirect(route('company.clients.show', [$client, 'vista' => 'sitio']));

        $post = SupervisorPost::query()
            ->where('client_id', $client->id)
            ->where('name', 'Puesto 9h')
            ->firstOrFail();
        $this->assertSame(9, $post->modality);
        $this->assertStringContainsString('9 h', $post->modalityLabel());
    }

    public function test_settings_lists_seeded_modalities(): void
    {
        $this->seedWithPilot();
        $user = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();

        $this->actingAs($user)
            ->get(route('company.supervision-post-modalities.index'))
            ->assertOk()
            ->assertSee('8 h')
            ->assertSee('12 h')
            ->assertSee('24 h');

        $this->assertSame(
            3,
            SupervisorPostModality::query()
                ->where('security_company_id', $user->security_company_id)
                ->count()
        );
    }
}
