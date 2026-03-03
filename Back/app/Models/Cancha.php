<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cancha extends Model
{
    use HasFactory;

    protected $fillable = [

        'nombre',
        'tipo',
        'precio_hora',
        'cant_jugadores',
    ];

    // Relación: una cancha tiene muchas reservas
    public function reservas()
    {
        return $this->hasMany(Reserva::class);
    }

}
