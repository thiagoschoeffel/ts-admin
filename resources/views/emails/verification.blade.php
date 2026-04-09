@component('mail::message')
# Confirme seu e-mail

Olá {{ $user->name }},

Obrigado por se registrar no {{ config('app.name') }}. Por favor, confirme seu endereço de e-mail clicando no botão abaixo.

@component('mail::button', ['url' => $verificationUrl])
Confirmar e-mail
@endcomponent

Se você não criou uma conta, ignore este e-mail.

Obrigado,
{{ config('app.name') }}
@endcomponent
