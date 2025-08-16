<?php
$data = json_decode(file_get_contents('php://input'), true);

$db_host = 'localhost';
$db_name = 'asistente_ia_db';
$db_user = 'asistente_user';
$db_pass = 'Sanjose4$';

if (empty($data['texto_original'])) { /* ... */ }

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // ¡Consulta actualizada!
    $sql = "INSERT INTO ner_training_data (texto_original, tarea_correcta, fecha_texto, hora_texto) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['texto_original'],
        $data['tarea_correcta'] ?: null,
        $data['fecha_texto'] ?: null,
        $data['hora_texto'] ?: null
    ]);
    http_response_code(200);
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) { /* ... */ }
?>