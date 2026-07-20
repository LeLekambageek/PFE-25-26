<x-mail::message>
# Bienvenue, {{ $nom }}

Un compte vient d'être créé pour vous sur la plateforme. Voici vos identifiants de connexion :

- **Email** : {{ $email }}
- **Mot de passe temporaire** : {{ $motDePasseTemporaire }}

Pour votre sécurité, il vous sera demandé de changer ce mot de passe dès votre première connexion.

<x-mail::button :url="config('app.url')">
Se connecter
</x-mail::button>

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
