<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

/**
 * Correo con el enlace para restablecer la contraseña.
 *
 * Reemplaza a la notificación que trae Laravel (que está en inglés) para que
 * el mensaje salga en español y con el nombre de la tienda.
 */
class RestablecerContrasena extends Notification
{
    use Queueable;

    public function __construct(public string $token)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutos = config('auth.passwords.users.expire', 60);

        $enlace = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Restablecer su contraseña · '.config('tienda.nombre'))
            ->greeting('Hola'.($notifiable->name ? ', '.$notifiable->name : '').':')
            ->line('Recibimos una solicitud para restablecer la contraseña de su cuenta.')
            ->action('Restablecer contraseña', $enlace)
            ->line("Este enlace vence en {$minutos} minutos.")
            ->line('Si usted no solicitó el cambio, puede ignorar este correo: su contraseña no se modificará.')
            ->salutation('Saludos, '.config('tienda.nombre'));
    }
}
