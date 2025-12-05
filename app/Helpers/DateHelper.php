<?php

if (!function_exists('formatEmailDate')) {
    /**
     * Formatea una fecha al estilo Gmail en español.
     *
     * Reglas:
     * - Si es < 24 horas: "2h", "hace 30m", etc.
     * - Si es hoy: "5 Diciembre 14:30"
     * - Si es este año: "5 Diciembre"
     * - Si es año anterior: "5 Diciembre 2024"
     *
     * @param \Carbon\Carbon $date
     * @return string
     */
    function formatEmailDate($date)
    {
        if (!$date) {
            return '';
        }

        $now = now();

        // Meses en español
        $monthsSpanish = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];

        // Si es menos de 24 horas, usar diffForHumans
        if ($date->diffInHours($now) < 24) {
            return $date->diffForHumans();
        }

        // Obtener día y mes en español
        $day = $date->day;
        $month = $monthsSpanish[$date->month];
        $year = $date->year;
        $currentYear = $now->year;

        // Si es el mismo día (hoy)
        if ($date->isToday()) {
            $hour = $date->format('H:i');
            return "{$day} {$month} {$hour}";
        }

        // Si es el mismo año
        if ($year === $currentYear) {
            return "{$day} {$month}";
        }

        // Si es año anterior
        return "{$day} {$month} {$year}";
    }
}
