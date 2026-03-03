@component('mail::message')
# 🎟️ ¡Reserva Confirmada!

Hola **{{ $reserva->user->name }}**,  
¡tu reserva fue confirmada exitosamente! ⚽  

A continuación, los detalles de tu ticket de reserva:

---

@component('mail::panel')
**Cancha:** {{ $reserva->cancha->nombre }}  
**Fecha:** {{ date('d/m/Y', strtotime($reserva->fecha)) }}  
**Hora:** {{ date('H:i', strtotime($reserva->hora_inicio)) }}
@endcomponent


📍 Por favor, llegar con **10 minutos de anticipación**.  
Te esperamos en el complejo para disfrutar del partido.

---

¡Gracias por confiar en nosotros!  
**El equipo de SomosFutbol**
@endcomponent
