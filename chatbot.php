<?php
require_once 'vendor/autoload.php';

use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use Carbon\Carbon;

// --- CONFIGURACIÓN GLOBAL ---
date_default_timezone_set('America/Bogota');
Carbon::setLocale('es');

// --- CONEXIÓN A LA BASE DE DATOS ---
$pdo = new PDO("mysql:host=localhost;dbname=asistente_ia_db;charset=utf8mb4", 'asistente_user', 'Sanjose4$');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// --- CARGA DEL MODELO DE IA PARA INTENCIONES ---
$modelFile = 'modelo_entrenado.phpml';
if (!file_exists($modelFile)) {
    die("Error: El archivo del modelo 'modelo_entrenado.phpml' no existe. Por favor, ejecuta 'php entrenar_modelo.php'.");
}
$modelData = unserialize(file_get_contents($modelFile));
$classifier = $modelData['classifier'];
$vectorizer = $modelData['vectorizer'];
if (!$classifier || !$vectorizer) {
    die("Error: el modelo de intenciones no se cargó correctamente.");
}


// --- FUNCIONES GLOBALES DE AYUDA ---

function predecirIntencion($texto) {
    global $classifier, $vectorizer;
    $samples = [$texto];
    $vectorizer->transform($samples);
    return $classifier->predict($samples)[0];
}

function _extraerEntidadesConIA($texto) {
    $escaped_text = escapeshellarg($texto);
    $python_path = __DIR__ . '/venv/bin/python3';
    $script_path = __DIR__ . '/ner_extractor.py';
    $command = $python_path . ' ' . $script_path . ' ' . $escaped_text;
    $json_output = shell_exec($command);
    $entidades_ia = json_decode($json_output, true);
    $tarea = null;
    $fecha = null;
    $hora = null;
    $fragmentos_tiempo_ia = [];
    if (is_array($entidades_ia)) {
        foreach ($entidades_ia as $entidad) {
            if ($entidad['type'] === 'TAREA') {
                $tarea = $entidad['text'];
            }
            if ($entidad['type'] === 'FECHA' || $entidad['type'] === 'HORA' || $entidad['type'] === 'TIEMPO' || $entidad['type'] === 'DATE' || $entidad['type'] === 'TIME') {
                $fragmentos_tiempo_ia[] = $entidad['text'];
            }
        }
    }
    $texto_tiempo_completo = implode(' ', $fragmentos_tiempo_ia);
    if (!empty($texto_tiempo_completo)) {
        try {
            $carbonDate = Carbon::parse($texto_tiempo_completo);
            $fecha = $carbonDate->toDateString();
            if ($carbonDate->hour != 0 || $carbonDate->minute != 0) {
                $hora = $carbonDate->toTimeString('minutes');
            }
        } catch (\Exception $e) {
        }
    }
    if ($tarea === null) {
        $tarea = $texto;
        $palabrasClave = ['anótalo por favor', 'recuérdame que', 'recuérdame', 'apunta que', 'apunta', 'anota que', 'anota', 'añade que', 'añade', 'crea una tarea para', 'tengo que', 'debo'];
        if (!empty($fragmentos_tiempo_ia)) {
            foreach ($fragmentos_tiempo_ia as $fragmento) {
                $tarea = trim(str_ireplace($fragmento, '', $tarea));
            }
        }
        foreach ($palabrasClave as $palabra) {
            $tarea = trim(str_ireplace($palabra, '', $tarea));
        }
    }
    return ['tarea_predicha' => ucfirst(trim($tarea)), 'fecha_predicha' => $fecha, 'hora_predicha' => $hora];
}

// --- LÓGICA PRINCIPAL DEL BOT ---
$config = [];
DriverManager::loadDriver(\BotMan\Drivers\Web\WebDriver::class);
$botman = BotManFactory::create($config);

$botman->fallback(function (BotMan $bot) use ($pdo) {
    $textoUsuario = $bot->getMessage()->getText();
    $payload = $bot->getMessage()->getPayload();
    
    // Verificamos si es un comando interno enviado por nuestro JavaScript
    $esComandoInterno = isset($payload['is_internal']) && $payload['is_internal'];
    
    $intencion = $esComandoInterno ? $payload['internal_intent'] : predecirIntencion($textoUsuario);
    $datos_adicionales = ['intencion' => $intencion];

    switch ($intencion) {
        case 'añadir_tarea':
            $entidades = _extraerEntidadesConIA($textoUsuario);
            $tarea = $entidades['tarea_predicha'];
            $fecha = $entidades['fecha_predicha'];
            $hora = $entidades['hora_predicha'];
            $fechaFormateada = "Sin fecha definida";
            if ($fecha) {
                $tiempoCompleto = trim($fecha . ' ' . $hora);
                $fechaFormateada = Carbon::parse($tiempoCompleto)->isoFormat('dddd D [de] MMMM [a las] H:mm');
            }
            $respuesta = "He entendido lo siguiente:\n\n📝 **Tarea:** \"" . ucfirst($tarea) . "\"\n🗓️ **Fecha:** " . $fechaFormateada;
            
            // Adjuntamos los datos extraídos para que el frontend cree los botones
            $datos_adicionales['extracted_data'] = [
                'descripcion' => $tarea,
                'fecha' => $fecha,
                'hora' => $hora
            ];
            $bot->reply($respuesta, $datos_adicionales);
            break;

        case 'guardar_tarea_corregida':
            // Leemos los datos JSON que nos envía el frontend desde el payload
            $datosGuardar = json_decode($payload['payload_data'], true);
            $descripcion = $datosGuardar['descripcion'];
            $fecha = null;
            if ($datosGuardar['fecha'] && $datosGuardar['hora']) {
                 $fecha = Carbon::createFromFormat('Y-m-d H:i', $datosGuardar['fecha'] . ' ' . $datosGuardar['hora'])->toDateTimeString();
            } elseif ($datosGuardar['fecha']) {
                 $fecha = Carbon::parse($datosGuardar['fecha'])->toDateTimeString();
            }
            
            $sql = "INSERT INTO tareas (descripcion, fecha_vencimiento) VALUES (?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([ucfirst($descripcion), $fecha]);
            
            // Usamos una intención "final" para que el frontend no muestre más botones
            $bot->reply('✅ ¡Entendido! He guardado la tarea.', ['intencion' => 'final']);
            break;
            
        case 'listar_tareas':
            $sql = "SELECT descripcion, fecha_vencimiento FROM tareas WHERE completada = FALSE ORDER BY CASE WHEN fecha_vencimiento IS NULL THEN 1 ELSE 0 END, fecha_vencimiento ASC, id ASC";
            $stmt = $pdo->query($sql);
            $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($tareas) > 0) {
                $respuesta = "Estas son tus tareas pendientes:\n";
                foreach ($tareas as $index => $tarea) {
                    $respuesta .= "\n" . ($index + 1) . ". " . $tarea['descripcion'];
                    if ($tarea['fecha_vencimiento']) {
                        $fechaVencimiento = Carbon::parse($tarea['fecha_vencimiento']);
                        // ¡LÓGICA PROACTIVA!
                        if ($fechaVencimiento->isPast()) {
                            $respuesta .= " **(🚨 ¡Venció " . $fechaVencimiento->diffForHumans() . "!)**";
                        } elseif ($fechaVencimiento->isToday()) {
                            $respuesta .= " **(⚠️ Vence hoy)**";
                        } else {
                            $respuesta .= " (Vence: " . $fechaVencimiento->diffForHumans() . ")";
                        }
                    }
                }
            } else {
                $respuesta = "¡Felicidades! No tienes ninguna tarea pendiente.";
            }
            $bot->reply($respuesta, $datos_adicionales);
            break;

        case 'completar_tarea':
        case 'eliminar_tarea':
            // Buscamos un número en la frase del usuario (ej: "completa la 2")
            if (preg_match('/(\d+)/', $textoUsuario, $matches)) {
                $numeroTarea = (int)$matches[1];
                
                // Obtenemos la lista de tareas PENDIENTES en el orden en que se muestran
                $stmt = $pdo->query("SELECT id FROM tareas WHERE completada = FALSE ORDER BY CASE WHEN fecha_vencimiento IS NULL THEN 1 ELSE 0 END, fecha_vencimiento ASC, id ASC");
                $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

                // Verificamos si el número que dio el usuario existe en la lista
                if (isset($ids[$numeroTarea - 1])) {
                    $idReal = $ids[$numeroTarea - 1]; // Mapeamos el número de lista al ID real de la BD
                    
                    if ($intencion === 'completar_tarea') {
                        $updateStmt = $pdo->prepare("UPDATE tareas SET completada = TRUE WHERE id = ?");
                        $updateStmt->execute([$idReal]);
                        $textoResp = '✅ ¡Excelente! He marcado la tarea ' . $numeroTarea . ' como completada.';
                    } else { // eliminar_tarea
                        $deleteStmt = $pdo->prepare("DELETE FROM tareas WHERE id = ?");
                        $deleteStmt->execute([$idReal]);
                        $textoResp = '🗑️ Hecho. He eliminado la tarea ' . $numeroTarea . '.';
                    }
                } else {
                    $textoResp = 'Lo siento, no encuentro la tarea número ' . $numeroTarea . ' en tu lista de pendientes.';
                }
            } else {
                $textoResp = 'Entendí la acción, pero necesito que me digas el número de la tarea (ej: "borra la 2").';
            }
            $bot->reply($textoResp, $datos_adicionales);
            break;
        case 'saludar':
            $textoResp = '¡Hola! ¿Cómo puedo ayudarte?';
            $bot->reply($textoResp, $datos_adicionales);
            break;
        
        default:
            $textoResp = 'Lo siento, no estoy seguro de qué quieres hacer.';
            $bot->reply($textoResp, $datos_adicionales);
            break;
    }
});

$botman->listen();
?>