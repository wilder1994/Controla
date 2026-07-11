<?php

declare(strict_types=1);

namespace Tests\Feature\Structure;

use App\Models\Client;
use App\Models\Structure;
use App\Models\User;
use App\Models\VisitorPreAuthorization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

final class AuthorizationImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_excel_with_fifty_rows_succeeds(): void
    {
        $this->seed();

        $client = Client::query()->where('slug', 'palmas-del-ingenio')->first();
        $admin = User::query()->where('email', 'admin@palmasdelingenio.test')->first();
        $structure = Structure::withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->where('code', 'TORRE-A-001')
            ->first();

        $this->assertNotNull($client);
        $this->assertNotNull($admin);
        $this->assertNotNull($structure);

        $lines = ['visitante,estructura,fecha'];
        for ($i = 1; $i <= 50; $i++) {
            $lines[] = "Visitante Import {$i},TORRE-A-001,".now()->addDays($i)->format('Y-m-d');
        }

        $file = UploadedFile::fake()->createWithContent(
            'autorizaciones.csv',
            implode("\n", $lines),
        );

        $response = $this->actingAs($admin)
            ->withSession(['tenancy.active_client_id' => $client->id])
            ->post(route('client.authorizations.import.store'), [
                'file' => $file,
            ]);

        $response->assertRedirect(route('client.authorizations.index'));
        $response->assertSessionHas('success');

        $imported = VisitorPreAuthorization::withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->where('visitor_name', 'like', 'Visitante Import %')
            ->count();

        $this->assertSame(50, $imported);
    }
}
