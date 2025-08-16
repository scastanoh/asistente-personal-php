<?php
require_once 'vendor/autoload.php';
use Carbon\Carbon;

header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');

$data = json_decode(file_get_contents('php://input'), true);
$texto = $data['texto_original'] ?? '';

if (empty($texto)) { echo json_encode(['error' => 'No se proporcionó texto.']); exit; }

// PASO 1: IA para la TAREA
$escaped_text = escapeshellarg($texto);
$python_path = __DIR__ . '/venv/bin/python3';
$script_path = __DIR__ . '/ner_extractor.py';
$command = $python_path . ' ' . $script_path . ' ' . $escaped_text;
$json_output = shell_exec($command);
$entidades_ia = json_decode($json_output, true);
$tarea = null;
if (is_array($entidades_ia)) {
    foreach ($entidades_ia as $entidad) {
        if ($entidad['type'] === 'TAREA') {
            $tarea = $entidad['text'];
            break;
        }
    }
}

// PASO 2: Reglas para FECHA/HORA
$fecha = null;
$hora = null;
$fragmento_encontrado = null;
$super_patron = '/'.
    '(\b(para el|el próximo|el|del)?\s*(lunes|martes|miércoles|jueves|viernes|sábado|domingo)\b)'.
    '|(\b(el\s*)?\d{1,2}\s*de\s*\w+\b)'.
    '|(\b(mañana|hoy|esta noche)\b)'.
    '|(\b(el\s*)?\d{1,2}[\/-]\d{1,2}([\/-]\d{2,4})?\b)'.
    '|(\b(en\s+\d+\s+(días|semanas|meses))\b)'.
    '|(\b(a las\s*)?\d{1,2}(:\d{2})?(\s*(am|pm|de la tarde|de la mañana|del mediodía))?\b)'.
    '/i';

if (preg_match_all($super_patron, $texto, $matches)) {
    $fragmento_encontrado = trim(implode(' ', $matches[0]));
    if (!empty($fragmento_encontrado)) {
        try {
            Carbon::setLocale('es');
            $carbonDate = Carbon::parse($fragmento_encontrado, 'America/Bogota');
            $fecha = $carbonDate->toDateString();
            if ($carbonDate->hour != 0 || $carbonDate->minute != 0 || preg_match('/(am|pm|tarde|noche|mañana|mediodía|:\d{2})/i', $fragmento_encontrado)) {
                $hora = $carbonDate->toTimeString('minutes');
            }
        } catch (\Exception $e) {}
    }
}

// PASO 3: Respaldo y Devolución
if ($tarea === null) {
    $tarea = $texto;
    if ($fragmento_encontrado) {
        $tarea = trim(str_ireplace($fragmento_encontrado, '', $tarea));
    }
}
echo json_encode([
    'tarea_predicha' => ucfirst(trim($tarea)),
    'fecha_predicha' => $fecha,
    'hora_predicha' => $hora
]);
?>