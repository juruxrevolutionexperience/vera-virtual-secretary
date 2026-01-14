<?php

namespace App\Console\Commands\Security;

use Illuminate\Console\Command;

class ProcessGDPRRequests extends Command
{
    protected $signature = 'security:process-gdpr';
    protected $description = 'Procesar solicitudes RGPD pendientes';

    public function handle(\App\Services\Security\GDPRComplianceService $gdpr)
    {
        $this->info('Procesando solicitudes de exportación...');
        $exports = \App\Models\DataExportRequest::where('status', 'pending')->get();
        foreach ($exports as $request) {
            try {
                $gdpr->processExport($request);
                $this->info("✓ Exportación {$request->id} completada");
            } catch (\Exception $e) {
                $this->error("✗ Error: {$e->getMessage()}");
            }
        }
        return 0;
    }
}
