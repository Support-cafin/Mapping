<x-mail::message>
# Alert de sécurité

Plusieurs tentatives de connexion échouées ont été détectées pour le compte : **{{ $email }}** 

**Détails :**
- Nombre de tentatives : {{ $attempts }}
- Adresse IP : {{ $ip }}
- Heure : {{ $time }}
- Utilisateur concerné : {{ $user->name ?? 'Non trouvé' }}

**Actions recommandées :**
1. Vérifier si c'est une activité légitime
2. Contacter l'utilisateur si nécessaire
3. Considérer un blocage temporaire de l'IP si suspect

<x-mail::button :url="route('admin.login-attempts')">
Voir les logs de connexion
</x-mail::button>

Cordialement,<br>
Système de sécurité {{ config('app.name') }}
</x-mail::message>