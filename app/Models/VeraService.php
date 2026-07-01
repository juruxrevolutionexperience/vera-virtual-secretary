<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VeraService extends Model
{
    protected $table = 'vera_services';

    protected $fillable = [
        'user_id',
        'name',
        'duration_minutes',
        'price',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'is_active'        => 'boolean',
        'sort_order'       => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Plantillas por tipo de negocio para el panel de configuración. */
    public static function templates(): array
    {
        return [
            'peluqueria' => [
                'label'    => 'Peluqueria',
                'services' => [
                    ['name' => 'Corte y lavado',  'duration_minutes' => 60, 'price' => null],
                    ['name' => 'Tinte completo',  'duration_minutes' => 90, 'price' => null],
                    ['name' => 'Mechas',           'duration_minutes' => 120, 'price' => null],
                    ['name' => 'Manicura',         'duration_minutes' => 45, 'price' => null],
                ],
            ],
            'peluqueria_canina' => [
                'label'    => 'Peluqueria canina',
                'services' => [
                    ['name' => 'Bano + corte (raza pequena)',  'duration_minutes' => 60,  'price' => null],
                    ['name' => 'Bano + corte (raza grande)',   'duration_minutes' => 90,  'price' => null],
                    ['name' => 'Bano sin corte',               'duration_minutes' => 45,  'price' => null],
                ],
            ],
            'fisioterapia' => [
                'label'    => 'Fisioterapia',
                'services' => [
                    ['name' => 'Primera consulta',  'duration_minutes' => 60, 'price' => null],
                    ['name' => 'Sesion seguimiento', 'duration_minutes' => 45, 'price' => null],
                    ['name' => 'Masaje deportivo',  'duration_minutes' => 60, 'price' => null],
                ],
            ],
            'consulta_medica' => [
                'label'    => 'Consulta medica',
                'services' => [
                    ['name' => 'Consulta general', 'duration_minutes' => 20, 'price' => null],
                    ['name' => 'Revision',          'duration_minutes' => 30, 'price' => null],
                    ['name' => 'Seguimiento',       'duration_minutes' => 15, 'price' => null],
                ],
            ],
        ];
    }
}
