<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class LegacyRouteAuditReport extends Command
{
    protected $signature = 'legacy:route-audit-report
                            {--path= : Caminho do arquivo de log. Default: storage/logs/laravel.log}
                            {--days=7 : Janela em dias para exibir no cabecalho}';

    protected $description = 'Resume eventos legacy_route_hit registrados no log do Laravel';

    public function handle()
    {
        $path = $this->option('path') ?: storage_path('logs/laravel.log');
        $days = (int) $this->option('days');

        if (!is_file($path)) {
            $this->error('Arquivo de log nao encontrado: ' . $path);
            return 1;
        }

        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            $this->error('Nao foi possivel ler o arquivo de log: ' . $path);
            return 1;
        }

        $counts = [];
        $statusCounts = [];
        $total = 0;

        foreach ($lines as $line) {
            if (strpos($line, 'legacy_route_hit') === false) {
                continue;
            }

            $total++;
            $routeName = $this->extractValue($line, '"route_name":"', '"');
            $statusCode = $this->extractValue($line, '"status_code":', ',');

            if ($routeName === null) {
                $routeName = 'desconhecida';
            }

            $counts[$routeName] = ($counts[$routeName] ?? 0) + 1;

            if ($statusCode !== null) {
                $statusCode = trim($statusCode, "\" \t\n\r\0\x0B}");
                $statusKey = $routeName . ' [' . $statusCode . ']';
                $statusCounts[$statusKey] = ($statusCounts[$statusKey] ?? 0) + 1;
            }
        }

        $this->info('Relatorio de hits legacy');
        $this->line('Arquivo: ' . $path);
        $this->line('Janela declarada: ultimos ' . $days . ' dia(s)');
        $this->line('Eventos encontrados: ' . $total);

        if ($total === 0) {
            $this->info('Nenhum evento legacy_route_hit encontrado.');
            return 0;
        }

        arsort($counts);
        arsort($statusCounts);

        $this->newLine();
        $this->table(
            ['Rota legacy', 'Hits'],
            collect($counts)->map(function ($hits, $route) {
                return [$route, $hits];
            })->values()->all()
        );

        $this->newLine();
        $this->table(
            ['Rota + status', 'Hits'],
            collect($statusCounts)->map(function ($hits, $route) {
                return [$route, $hits];
            })->values()->all()
        );

        return 0;
    }

    protected function extractValue(string $line, string $startToken, string $endToken)
    {
        $start = strpos($line, $startToken);
        if ($start === false) {
            return null;
        }

        $start += strlen($startToken);
        $end = strpos($line, $endToken, $start);

        if ($end === false) {
            return substr($line, $start);
        }

        return substr($line, $start, $end - $start);
    }
}
