<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\VeraTokenUsage;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CostReport extends Command
{
    protected $signature   = 'vera:cost-report {mes? : Mes en formato YYYY-MM (por defecto: mes actual)}';
    protected $description = 'Informe de coste mensual por cliente: tokens LLM, coste en euros y mensajes por canal';

    private const USD_TO_EUR = 0.92;

    public function handle(): int
    {
        [$year, $month] = $this->resolveMonth();

        $monthLabel = Carbon::createFromDate($year, $month, 1)->locale('es')->isoFormat('MMMM YYYY');
        $this->info('');
        $this->info("  Vera - Informe de coste: {$monthLabel}");
        $this->line('  ' . str_repeat('-', 85));

        // Suma de tokens y coste pre-calculado por usuario
        $rows = VeraTokenUsage::query()
            ->select(
                'user_id',
                DB::raw('SUM(prompt_tokens)     AS inp'),
                DB::raw('SUM(completion_tokens) AS out'),
                DB::raw('SUM(total_tokens)      AS total'),
                DB::raw('SUM(cost)              AS cost_usd'),
                DB::raw('COUNT(*)               AS calls')
            )
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->groupBy('user_id')
            ->orderByDesc('cost_usd')
            ->get();

        if ($rows->isEmpty()) {
            $this->warn("  Sin registros de tokens para {$monthLabel}.");
            $this->info('');
            return self::SUCCESS;
        }

        $userIds = $rows->pluck('user_id')->unique();
        $users   = User::whereIn('id', $userIds)->pluck('email', 'id');

        // Mensajes por canal (de metadata JSON)
        $byChannel = VeraTokenUsage::query()
            ->select(
                'user_id',
                DB::raw('JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.channel")) AS ch'),
                DB::raw('COUNT(*) AS n')
            )
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->whereNotNull(DB::raw('JSON_EXTRACT(metadata, "$.channel")'))
            ->groupBy('user_id', DB::raw('JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.channel"))'))
            ->get()
            ->groupBy('user_id');

        $tableRows = [];
        $sumInp = $sumOut = $sumCostEur = $sumCalls = 0;

        foreach ($rows as $row) {
            $email = $users->get($row->user_id) ?? "(uid:{$row->user_id})";
            $inp   = (int) $row->inp;
            $out   = (int) $row->out;
            $calls = (int) $row->calls;
            $costEur = round((float) $row->cost_usd * self::USD_TO_EUR, 4);

            // Canal breakdown para este usuario
            $chEntries  = $byChannel->get($row->user_id, collect());
            $chMap      = $chEntries->pluck('n', 'ch')->toArray();
            $chStr      = collect($chMap)->map(fn($n, $ch) => "{$ch}:{$n}")->join(' / ') ?: '-';
            $waMsgs     = (int) ($chMap['whatsapp'] ?? 0);

            $sumInp     += $inp;
            $sumOut     += $out;
            $sumCostEur += $costEur;
            $sumCalls   += $calls;

            $tableRows[] = [
                mb_strimwidth($email, 0, 35, '..'),
                $calls,
                number_format($inp),
                number_format($out),
                number_format($costEur, 4) . ' €',
                $chStr,
                $waMsgs,
                $waMsgs > 0 ? 'gratis*' : '-',
                number_format($costEur, 4) . ' €',
            ];
        }

        $this->table(
            ['Cliente', 'Llamadas', 'Inp tokens', 'Out tokens', 'Coste LLM', 'Canales', 'WA msgs', 'WA coste', 'TOTAL'],
            $tableRows
        );

        $this->line('');
        $this->line(sprintf(
            '  TOTAL: %d clientes | %d llamadas | %s inp | %s out | <fg=green>%.4f €</>',
            count($tableRows),
            $sumCalls,
            number_format($sumInp),
            number_format($sumOut),
            $sumCostEur
        ));
        $this->line('');
        $this->line('  * WhatsApp respuestas dentro de ventana 24h cliente-iniciada = gratis.');
        $this->line('    Recordatorios van por email. Cuando pasen a WA sumar ~0.012 €/conversacion.');
        $this->line('');

        return self::SUCCESS;
    }

    private function resolveMonth(): array
    {
        $arg = $this->argument('mes');

        if ($arg) {
            if (!preg_match('/^\d{4}-\d{2}$/', $arg)) {
                $this->error('Formato invalido. Usa YYYY-MM, por ejemplo: vera:cost-report 2026-06');
                exit(1);
            }
            [$y, $m] = explode('-', $arg);
            return [(int) $y, (int) $m];
        }

        return [(int) now()->year, (int) now()->month];
    }
}
