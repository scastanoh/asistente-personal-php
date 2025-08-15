<?php
// Recibe los datos JSON enviados desde el JavaScript
$data = json_decode(file_get_contents('php://input'), true);

// --- Configuración de la Base de Datos ---
$db_host = 'localhost';
$db_name = 'asistente_ia_db';
$db_user = 'asistente_user';
$db_pass = 'Sanjose4$';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "INSERT INTO feedback_log (texto_usuario, prediccion_modelo, es_correcto, intencion_correcta) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    // El valor booleano se convierte a 1 o 0 para la base de datos
    $es_correcto_int = $data['es_correcto'] ? 1 : 0;
    
    $stmt->execute([
        $data['texto_usuario'],
        $data['prediccion_modelo'],
        $es_correcto_int,
        $data['intencion_correcta']
    ]);

    // Responde con un éxito
    http_response_code(200);
    echo json_encode(['status' => 'success']);

} catch (PDOException $e) {
    // Responde con un error
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>