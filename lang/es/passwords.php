<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Líneas de idioma para restablecimiento de contraseña
    |--------------------------------------------------------------------------
    */

    'reset' => 'Tu contraseña ha sido restablecida.',
    'sent' => 'Te hemos enviado por email el enlace para restablecer tu contraseña.',
    'throttled' => 'Por favor, espera antes de volver a intentarlo.',
    'token' => 'El token de restablecimiento de contraseña no es válido.',
    'user' => 'No encontramos ningún usuario con ese correo electrónico.',

    // Notificación por email (ResetPasswordNotification)
    'notification_subject' => 'Notificación de restablecimiento de contraseña',
    'notification_reason' => 'Recibes este correo porque hemos recibido una solicitud de restablecimiento de contraseña para tu cuenta.',
    'notification_action' => 'Restablecer contraseña',
    'notification_expire' => 'Este enlace caducará en :count minutos.',
    'notification_no_action' => 'Si no solicitaste restablecer la contraseña, no es necesario que hagas nada.',

];
