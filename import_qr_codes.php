<?php
require __DIR__ . '/vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

// Connexion MySQL (à adapter selon votre config)
$pdo = new PDO('mysql:host=localhost;dbname=ticket_system;charset=utf8', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function sanitize_filename($filename) {
    return preg_replace('/[\\\\\\/\\:\\*\\?\\"<>\\|]/', '_', $filename);
}

$csv_file = 'data.csv';
if (!file_exists($csv_file)) {
    die("Fichier CSV non trouvé.");
}

$lines = file($csv_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$output_dir = 'qr_codes';
if (!is_dir($output_dir)) {
    mkdir($output_dir, 0777, true);
}

$writer = new PngWriter();

foreach ($lines as $line) {
    $text = trim($line);
    $qr = QrCode::create($text)->setSize(300)->setMargin(10);
    $result = $writer->write($qr);

    $filename = sanitize_filename($text) . '.png';
    $filepath = "$output_dir/$filename";
    $result->saveToFile($filepath);

    // Insertion dans la base
    $stmt = $pdo->prepare("INSERT INTO qr_codes (label, file_path) VALUES (?, ?)");
    $stmt->execute([$text, $filepath]);

    echo "QR code enregistré : $text<br>";
}
?>
