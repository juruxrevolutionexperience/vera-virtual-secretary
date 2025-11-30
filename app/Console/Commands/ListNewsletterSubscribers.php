<?php

namespace App\Console\Commands;

use App\Models\NewsletterSubscription;
use Illuminate\Console\Command;

class ListNewsletterSubscribers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'newsletter:list {--active : Solo suscriptores activos} {--export : Exportar a CSV}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listar todos los suscriptores del newsletter';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = NewsletterSubscription::query();

        // Filtrar solo activos si se especifica
        if ($this->option('active')) {
            $query->active();
        }

        $subscribers = $query->orderBy('created_at', 'desc')->get();

        if ($subscribers->isEmpty()) {
            $this->info('No hay suscriptores en el newsletter.');
            return 0;
        }

        // Exportar a CSV si se solicita
        if ($this->option('export')) {
            return $this->exportToCsv($subscribers);
        }

        // Mostrar tabla en consola
        $this->info('Total de suscriptores: ' . $subscribers->count());
        $this->newLine();

        $this->table(
            ['ID', 'Email', 'Estado', 'Fecha Suscripción', 'IP'],
            $subscribers->map(function ($subscriber) {
                return [
                    $subscriber->id,
                    $subscriber->email,
                    $subscriber->status === 'active' ? '✓ Activo' : '✗ Inactivo',
                    $subscriber->subscribed_at->format('d/m/Y H:i'),
                    $subscriber->ip_address ?? 'N/A',
                ];
            })->toArray()
        );

        // Estadísticas
        $active = $subscribers->where('status', 'active')->count();
        $unsubscribed = $subscribers->where('status', 'unsubscribed')->count();

        $this->newLine();
        $this->info("📊 Estadísticas:");
        $this->line("   Activos: {$active}");
        $this->line("   Dados de baja: {$unsubscribed}");

        return 0;
    }

    /**
     * Exportar suscriptores a CSV
     */
    private function exportToCsv($subscribers)
    {
        $filename = 'newsletter_subscribers_' . now()->format('Y-m-d_His') . '.csv';
        $filepath = storage_path('app/' . $filename);

        $file = fopen($filepath, 'w');

        // Header
        fputcsv($file, ['ID', 'Email', 'Estado', 'Fecha Suscripción', 'Fecha Baja', 'IP', 'User Agent']);

        // Datos
        foreach ($subscribers as $subscriber) {
            fputcsv($file, [
                $subscriber->id,
                $subscriber->email,
                $subscriber->status,
                $subscriber->subscribed_at->format('Y-m-d H:i:s'),
                $subscriber->unsubscribed_at ? $subscriber->unsubscribed_at->format('Y-m-d H:i:s') : '',
                $subscriber->ip_address ?? '',
                $subscriber->user_agent ?? '',
            ]);
        }

        fclose($file);

        $this->info("✓ Exportado exitosamente a: {$filepath}");
        $this->info("Total de registros: " . $subscribers->count());

        return 0;
    }
}
