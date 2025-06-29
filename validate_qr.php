<?php
header('Content-Type: application/json');

try {
    // 1. Connexion à la base de données
    $db = new PDO('mysql:host=localhost;dbname=ticket_system', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Récupération du code depuis la requête POST
    $code = $_POST['code'] ?? '';

    if (empty($code)) {
        throw new Exception("Aucun code fourni");
    }

    // 3. Rechercher le QR code dans la base
    $stmt = $db->prepare("SELECT * FROM qr_codes WHERE label = ?");
    $stmt->execute([$code]);

    if ($stmt->rowCount() === 0) {
        echo json_encode([
            'valid' => false,
            'message' => 'Code QR non reconnu'
        ]);
        exit;
    }

    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    // 4. Vérifier le nombre d'utilisations
    if ($ticket['scan_count'] >= 2) {
        echo json_encode([
            'valid' => false,
            'message' => '❌ Entrée non autorisée - Ticket déjà utilisé 2 fois',
            'scan_count' => $ticket['scan_count'],
            'last_scan_time' => $ticket['last_scan_time']
        ]);
        exit;
    }

    // 5. Si scan autorisé : incrémenter le compteur et mettre à jour l'heure
    $newCount = $ticket['scan_count'] + 1;
    $update = $db->prepare("UPDATE qr_codes SET scan_count = ?, last_scan_time = NOW() WHERE id = ?");
    $update->execute([$newCount, $ticket['id']]);

    echo json_encode([
        'valid' => true,
        'message' => '✅ Entrée autorisée',
        'file_name' => basename($ticket['file_path']),
        'label' => $ticket['label'],
        'scan_count' => $newCount,
        'last_scan_time' => date('d/m/Y H:i:s')
    ]);

} catch (Exception $e) {
    echo json_encode([
        'valid' => false,
        'message' => 'Erreur technique : ' . $e->getMessage()
    ]);
}
?>
