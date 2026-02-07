<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateOrganizationUserSnapshots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'organizations:update-snapshots';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualizar snapshots de nombre y email en la tabla organization_user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Actualizando snapshots de usuarios en organizaciones...');

        $updated = 0;
        $skipped = 0;

        // Obtener todos los registros de organization_user que no tienen snapshot
        $pivotRecords = DB::table('organization_user')
            ->whereNull('user_name')
            ->orWhereNull('user_email')
            ->get();

        $this->info("📊 Encontrados {$pivotRecords->count()} registros sin snapshot");

        foreach ($pivotRecords as $pivot) {
            // Obtener el usuario
            $user = User::find($pivot->user_id);

            if (!$user) {
                $this->warn("⚠️  Usuario ID {$pivot->user_id} no encontrado - Se mantendrá sin snapshot");
                $skipped++;
                continue;
            }

            // Actualizar snapshot
            DB::table('organization_user')
                ->where('organization_id', $pivot->organization_id)
                ->where('user_id', $pivot->user_id)
                ->update([
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                ]);

            $updated++;
        }

        $this->info("✅ Actualización completada:");
        $this->info("   - Actualizados: {$updated}");
        $this->info("   - Omitidos (usuario eliminado): {$skipped}");

        return Command::SUCCESS;
    }
}
