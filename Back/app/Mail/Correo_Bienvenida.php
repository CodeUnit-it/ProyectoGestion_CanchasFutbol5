<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;  //clase base Mailable de Laravel para correos
use Illuminate\Mail\Mailables\Content; //contenido o vista del correo
use Illuminate\Mail\Mailables\Envelope; // Asuntos del correo

class Correo_Bienvenida extends Mailable
{

    public $user; // Hacemos el usuario público para que esté disponible en la vista blade

    /**
     * Crea una nueva instancia del mensaje.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Asunto del correo
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '¡Bienvenido a SomosFutbol!', // Asunto del correo
        );
    }

    /**
     * Mensaje como tal
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.bienvenida', // Apuntamos a la vista de Blade 'resources/views/emails/bienvenida.blade.php'
            with: [
                'nombreUsuario' => $this->user->name, // Pasamos el nombre a la vista
            ]
        );
    }
}