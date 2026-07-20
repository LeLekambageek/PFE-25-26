<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Envoi synchrone (pas de mise en file d'attente pour l'instant, le volume de
 * comptes créés simultanément ne le justifie pas encore ; à reconsidérer si ce
 * volume augmente significativement).
 */
class UserCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nom,
        public string $email,
        public string $motDePasseTemporaire,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Vos identifiants de connexion',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.users.credentials',
        );
    }
}
