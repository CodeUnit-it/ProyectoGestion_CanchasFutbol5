<?php

namespace App\Mail;

use App\Models\Reserva;
use Illuminate\Mail\Mailable; //clase base Mailable de Laravel para correos
use Illuminate\Mail\Mailables\Content; //contenido o vista del correo
use Illuminate\Mail\Mailables\Envelope; //cuerpo del mensaje


class Correo_Ticket_Reserva extends Mailable
{

    public $reserva; // Hacemos la reserva pública

    /**
     * Nueva instancia del mensaje.
     *
     * @param  \App\Models\Reserva  $reserva
     * @return void
     */
    public function __construct(Reserva $reserva)
    {
        $this->reserva = $reserva;
    }

    /**
     * Defie el asunto del mensaje.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmación de tu Reserva (Ticket)',
        );
    }

    /**
     * Define el contenido del mensaje.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.ticket_reserva', // Usamos la vista 'resources/views/emails/ticket_reserva.blade.php'
            // La variable $reserva ya está disponible públicamente en la vista
        );
    }
}
