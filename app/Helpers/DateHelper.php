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

if (!function_exists('userTimezone')) {
    /**
     * Obtiene el timezone del usuario autenticado
     *
     * @return string
     */
    function userTimezone()
    {
        return auth()->check() && auth()->user()->timezone
            ? auth()->user()->timezone
            : config('app.timezone', 'UTC');
    }
}

if (!function_exists('toUserTimezone')) {
    /**
     * Convierte una fecha a la zona horaria del usuario
     *
     * @param \Carbon\Carbon|string|null $date
     * @return \Carbon\Carbon|null
     */
    function toUserTimezone($date)
    {
        if (!$date) {
            return null;
        }

        $carbonDate = $date instanceof \Carbon\Carbon ? $date : \Carbon\Carbon::parse($date);

        return $carbonDate->setTimezone(userTimezone());
    }
}

if (!function_exists('formatDueDateBadge')) {
    /**
     * Formatea una fecha de vencimiento para mostrar en badge
     * Ejemplo: "25 Ene 14:00", "26 Ene 09:00"
     *
     * @param \Carbon\Carbon|string|null $date
     * @return string
     */
    function formatDueDateBadge($date)
    {
        if (!$date) {
            return '';
        }

        $date = toUserTimezone($date);

        $monthsShort = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
        ];

        $day = $date->day;
        $month = $monthsShort[$date->month];
        $time = $date->format('H:i');

        return "{$day} {$month} {$time}";
    }
}

if (!function_exists('formatDueDateFull')) {
    /**
     * Formatea una fecha de vencimiento completa para tooltips
     * Ejemplo: "25 Enero 2026 14:00"
     *
     * @param \Carbon\Carbon|string|null $date
     * @return string
     */
    function formatDueDateFull($date)
    {
        if (!$date) {
            return '';
        }

        $date = toUserTimezone($date);

        $monthsFull = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $day = $date->day;
        $month = $monthsFull[$date->month];
        $year = $date->year;
        $time = $date->format('H:i');

        return "{$day} {$month} {$year} {$time}";
    }
}
