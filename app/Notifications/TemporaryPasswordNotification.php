<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

/**
 * Notificación privada con la contraseña temporal de una cuenta creada por el administrador.
 */
class TemporaryPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $password) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(Lang::get('Tu cuenta de conductor'))
            ->greeting(Lang::get('Hola :name', ['name' => $notifiable->first_name]))
            ->line(Lang::get('Tu cuenta fue creada por un administrador.'))
            ->line(Lang::get('Tu contraseña temporal es: :password', ['password' => $this->password]))
            ->line(Lang::get('Debes cambiarla la primera vez que inicies sesión.'))
            ->action(Lang::get('Iniciar sesión'), url(route('login', absolute: false)));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return ['password' => $this->password];
    }
}
