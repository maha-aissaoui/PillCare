<?php
// send_notifications.php - Script pour envoyer les notifications programmées
// Ce script est destiné à être exécuté par un cron job toutes les 5-10 minutes

// Charger la configuration
require_once 'db_connect.php';

// Fonction pour envoyer un email
function sendEmail($to, $subject, $message) {
    // Configuration des en-têtes de l'email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: PillCare <noreply@pillcare.com>' . "\r\n";
    
    // Envoyer l'email
    return mail($to, $subject, $message, $headers);
}

// Fonction pour envoyer un SMS (simulation)
function sendSMS($phone, $message) {
    // Ici vous intégreriez un service d'envoi de SMS comme Twilio, Nexmo, etc.
    // Pour l'instant, nous simulons que l'envoi a réussi
    
    // Exemple d'intégration Twilio (nécessite l'installation de la bibliothèque Twilio)
    /*
    require_once 'vendor/autoload.php';
    use Twilio\Rest\Client;
    
    $sid = 'VOTRE_TWILIO_SID';
    $token = 'VOTRE_TWILIO_TOKEN';
    $twilioNumber = 'VOTRE_NUMERO_TWILIO';
    
    try {
        $client = new Client($sid, $token);
        $result = $client->messages->create(
            $phone,
            [
                'from' => $twilioNumber,
                'body' => $message
            ]
        );
        return $result->sid ? true : false;
    } catch (Exception $e) {
        error_log('Erreur Twilio: ' . $e->getMessage());
        return false;
    }
    */
    
    // Simulation de succès pour cet exemple
    error_log("SMS envoyé à $phone: $message");
    return true;
}

// Récupérer les notifications à envoyer
$currentTime = date('Y-m-d H:i:s');
$stmt = $conn->prepare("SELECT n.*, e.title, e.date, e.time, e.description 
                       FROM notifications n 
                       JOIN events e ON n.event_id = e.id 
                       WHERE n.status = 'pending' AND n.send_at <= ?");
$stmt->bind_param("s", $currentTime);
$stmt->execute();
$notifications = $stmt->get_result();

// Parcourir les notifications
$count = 0;
while ($notification = $notifications->fetch_assoc()) {
    $success = false;
    $event_title = $notification['title'];
    $event_date = date('d/m/Y', strtotime($notification['date']));
    $event_time = $notification['time'] ? date('H:i', strtotime($notification['time'])) : 'heure non précisée';
    $recipient = $notification['recipient'];
    
    // Préparer le message
    if ($notification['type'] === 'email') {
        $subject = "Rappel: $event_title le $event_date";
        $message = "<html><body>";
        $message .= "<h2>Rappel de rendez-vous</h2>";
        $message .= "<p>Vous avez un rendez-vous: <strong>$event_title</strong></p>";
        $message .= "<p>Date: <strong>$event_date</strong> à <strong>$event_time</strong></p>";
        
        if ($notification['description']) {
            $message .= "<p>Description: " . htmlspecialchars($notification['description']) . "</p>";
        }
        
        $message .= "<p>Ce message est un rappel automatique de PillCare.</p>";
        $message .= "</body></html>";
        
        $success = sendEmail($recipient, $subject, $message);
    } 
    elseif ($notification['type'] === 'sms') {
        $message = "Rappel: $event_title le $event_date à $event_time";
        $success = sendSMS($recipient, $message);
    }
    
    // Mettre à jour le statut de la notification
    $status = $success ? 'sent' : 'failed';
    $update = $conn->prepare("UPDATE notifications SET status = ?, sent_at = ? WHERE id = ?");
    $now = date('Y-m-d H:i:s');
    $update->bind_param("ssi", $status, $now, $notification['id']);
    $update->execute();
    $update->close();
    
    if ($success) {
        $count++;
    }
}

$stmt->close();
$conn->close();

echo "Notifications envoyées: $count\n";
?>