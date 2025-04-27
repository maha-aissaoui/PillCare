<?php
// cron.php - Instructions pour configurer le cron job pour les notifications

/*
INSTRUCTIONS POUR LA CONFIGURATION DU CRON JOB

Ce fichier explique comment configurer un cron job pour exécuter automatiquement 
le script send_notifications.php qui envoie les notifications programmées.

1. ACCÈS SSH À VOTRE SERVEUR
   - Connectez-vous à votre serveur via SSH

2. OUVRIR LE CRONTAB
   - Exécutez la commande: crontab -e

3. AJOUTER UNE ENTRÉE POUR LES NOTIFICATIONS
   - Ajoutez la ligne suivante pour exécuter le script toutes les 5 minutes:
   
   */5 * * * * php /chemin/complet/vers/send_notifications.php >> /chemin/complet/vers/logs/notifications.log 2>&1
   
   /*
   Remplacez "/chemin/complet/vers/" par le chemin réel vers votre application.
   
4. VÉRIFICATION DU CRON JOB
   - Pour voir les cron jobs configurés: crontab -l
   - Pour vérifier les journaux: cat /chemin/complet/vers/logs/notifications.log

5. DÉPANNAGE
   - Assurez-vous que le script PHP a les permissions d'exécution (chmod +x send_notifications.php)
   - Assurez-vous que le dossier logs existe et est accessible en écriture
   - Vérifiez que PHP peut être exécuté en ligne de commande

ALTERNATIVES POUR L'HÉBERGEMENT PARTAGÉ

Si vous n'avez pas d'accès SSH à votre serveur (hébergement partagé):

1. Utilisez le panneau de configuration de votre hébergeur (cPanel, Plesk, etc.) 
   pour configurer un cron job.

2. Utilisez un service de cron externe comme:
   - EasyCron (https://www.easycron.com/)
   - SetCronJob (https://www.setcronjob.com/)
   - Cron-Job.org (https://cron-job.org/)
   
   Configurez-les pour appeler l'URL: https://votre-domaine.com/send_notifications.php
   
   Note: Dans ce cas, modifiez send_notifications.php pour vérifier un jeton secret
   afin de s'assurer que le script n'est pas exécuté par des visiteurs non autorisés.

*/

// Ce fichier est à titre informatif uniquement et n'est pas exécuté directement
echo "Ce fichier contient des instructions pour configurer le cron job. Veuillez consulter les commentaires.";
?>