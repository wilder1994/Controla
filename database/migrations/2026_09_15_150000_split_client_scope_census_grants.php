<?php

declare(strict_types=1);

use App\Support\Auth\GrantableModules;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $children = GrantableModules::censusAliasModules();
        $rows = DB::table('user_module_grants')->where('module', 'census')->get();

        foreach ($rows as $row) {
            foreach ($children as $module) {
                $exists = DB::table('user_module_grants')
                    ->where('user_id', $row->user_id)
                    ->where('scope', $row->scope)
                    ->where('scope_id', $row->scope_id)
                    ->where('module', $module)
                    ->exists();
                if ($exists) {
                    continue;
                }
                DB::table('user_module_grants')->insert([
                    'user_id' => $row->user_id,
                    'scope' => $row->scope,
                    'scope_id' => $row->scope_id,
                    'module' => $module,
                    'level' => $row->level,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->insertIfMissing($row, 'documents', $row->level === 'manage' ? 'view' : $row->level);
            $this->insertIfMissing($row, 'users', 'manage');
            $this->insertIfMissing($row, 'app_users', 'manage');
        }

        DB::table('user_module_grants')->where('module', 'census')->delete();
    }

    public function down(): void
    {
        // no-op: no se vuelve a fusionar el censo
    }

    private function insertIfMissing(object $row, string $module, string $level): void
    {
        $exists = DB::table('user_module_grants')
            ->where('user_id', $row->user_id)
            ->where('scope', $row->scope)
            ->where('scope_id', $row->scope_id)
            ->where('module', $module)
            ->exists();
        if ($exists) {
            return;
        }

        DB::table('user_module_grants')->insert([
            'user_id' => $row->user_id,
            'scope' => $row->scope,
            'scope_id' => $row->scope_id,
            'module' => $module,
            'level' => $level,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
