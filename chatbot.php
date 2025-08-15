<?php
require_once 'vendor/autoload.php';

use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use Gemini;

$apiKey = 'AIzaSyCHvxajY158PgFehLD5F6O0k-_ht9wYdk0'; // Tu clave

$db_host = 'localhost';
$db_name = 'asistente_ia_db';
$db_user = 'asistente_user';
$db_pass = 'Sanjose4$';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión a la BD: " . $e->getMessage());
}

$modelFile = 'modelo_entrenado.phpml';
$modelData = unserialize(file_get_contents($modelFile));
$classifier = $modelData['classifier'];
$vectorizer = $modelData['vectorizer'];

function predecirIntencion($texto) {
    global $classifier, $vectorizer;
    $samples = [$texto];
    $vectorizer->transform($samples);
    return $classifier->predict($samples)[0];
}

function extraerEntidadTarea($texto) {
    $textoNormalizado = strtolower($texto);
    $palabrasClave = ['añade', 'añadir', 'apunta', 'anota', 'recuérdame', 'crea una tarea', 'nueva tarea', 'agrega', 'agregame', 'pon', 'necesito', 'tengo que'];
    foreach ($palabrasClave as $palabra) {
        $pos = strpos($textoNormalizado, $palabra);
        if ($pos !== false) {
            $entidad = trim(substr($textoNormalizado, $pos + strlen($palabra)));
            $relleno = ['que ', 'la tarea de ', 'la tarea ', 'un recordatorio para ', 'un pendiente: ', ': '];
            foreach ($relleno as $r) {
                if (substr($entidad, 0, strlen($r)) === $r) $entidad = substr($entidad, strlen($r));
            }
            return trim($entidad);
        }
    }
    return $texto;
}

// --- FUNCIÓN DE CONEXIÓN CON GEMINI (VERSIÓN FINAL) ---
function obtenerRespuestaGemini($pregunta) {
    global $apiKey;
    if ($apiKey === 'TU_API_KEY_DE_GEMINI_AQUI' || empty($apiKey)) {
        return "Error de configuración: La clave de la API de Gemini no ha sido establecida.";
    }
    try {
        $client = Gemini::client($apiKey);
        // ¡LÍNEA CORREGIDA! Usamos el nombre del modelo más reciente y recomendado.
        $result = $client->generativeModel('gemini-1.5-flash-latest')->generateContent($pregunta);
        return $result->text();
    } catch (\Exception $e) {
        error_log("Error de la API de Gemini: " . $e->getMessage());
        return "Lo siento, hubo un problema con la conexión a la IA de Gemini. Revisa el log de Apache.";
    }
}

$config = [];
DriverManager::loadDriver(\BotMan\Drivers\Web\WebDriver::class);
$botman = BotManFactory::create($config);

$botman->fallback(function (BotMan $bot) use ($pdo) {
    $textoUsuario = $bot->getMessage()->getText();
    $intencion = predecirIntencion($textoUsuario);
    
    switch ($intencion) {
        case 'charla_general':
            $respuesta_gemini = obtenerRespuestaGemini($textoUsuario);
            $bot->reply($respuesta_gemini);
            break;
        case 'saludar':
            $bot->reply('¡Hola! ¿Cómo puedo ayudarte?');
            break;
        case 'listar_tareas':
            $stmt = $pdo->query("SELECT id, descripcion FROM tareas WHERE completada = FALSE ORDER BY fecha_creacion ASC");
            $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if(count($tareas)>0){$respuesta="Estas son tus tareas pendientes:\n";foreach($tareas as $index=>$tarea){$respuesta .=($index+1).". ".$tarea['descripcion']."\n";}}else{$respuesta="¡Felicidades! No tienes ninguna tarea pendiente.";}$bot->reply($respuesta);
            break;
        case 'añadir_tarea':
            $tareaLimpia = extraerEntidadTarea($textoUsuario);$stmt=$pdo->prepare("INSERT INTO tareas (descripcion) VALUES (?)");$stmt->execute([ucfirst($tareaLimpia)]);$bot->reply('¡Anotado! He añadido la tarea: "'.ucfirst($tareaLimpia).'"');
            break;
        case 'completar_tarea':
        case 'eliminar_tarea':
            if(preg_match('/(\d+)/',$textoUsuario,$matches)){$numeroTarea=(int)$matches[1];$stmt=$pdo->query("SELECT id FROM tareas WHERE completada = FALSE ORDER BY fecha_creacion ASC");$ids=$stmt->fetchAll(PDO::FETCH_COLUMN);if(isset($ids[$numeroTarea-1])){$idReal=$ids[$numeroTarea-1];if($intencion==='completar_tarea'){$updateStmt=$pdo->prepare("UPDATE tareas SET completada = TRUE WHERE id = ?");$updateStmt->execute([$idReal]);$bot->reply('¡Excelente! He marcado la tarea '.$numeroTarea.' como completada.');}else{$deleteStmt=$pdo->prepare("DELETE FROM tareas WHERE id = ?");$deleteStmt->execute([$idReal]);$bot->reply('Hecho. He eliminado la tarea '.$numeroTarea.'.');}}else{$bot->reply('Lo siento, no encuentro la tarea número '.$numeroTarea.' en tu lista.');}}else{$bot->reply('Entendí la acción, pero necesito el número de la tarea.');}
            break;
        default:
            $bot->reply('Lo siento, no estoy seguro de qué quieres hacer. (Intención: '.$intencion.')');
            break;
    }
});

$botman->listen();
?>