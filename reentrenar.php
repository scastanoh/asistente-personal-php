<?php
echo "--- INICIANDO PROCESO DE RE-ENTRENAMIENTO ---\n";

// --- Conexión a la Base de Datos ---
$db_host = 'localhost';
$db_name = 'asistente_ia_db';
$db_user = 'asistente_user';
$db_pass = 'Sanjose4$';
$pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);

// 1. Obtener nuevos datos de entrenamiento del feedback
echo "Paso 1: Obteniendo nuevos datos de entrenamiento del log de feedback...\n";
$stmt = $pdo->query("SELECT texto_usuario, prediccion_modelo, es_correcto, intencion_correcta FROM feedback_log WHERE procesado = FALSE");
$nuevos_datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($nuevos_datos)) {
    echo "No hay nuevos datos de feedback para procesar. El modelo ya está actualizado.\n";
    exit;
}

echo "Se encontraron " . count($nuevos_datos) . " nuevos registros de feedback.\n";

// 2. Formatear y añadir los nuevos datos a training_data.csv
echo "Paso 2: Añadiendo nuevos datos a training_data.csv...\n";
$csv_file = fopen('training_data.csv', 'a'); // 'a' para añadir al final
$count_added = 0;
foreach ($nuevos_datos as $dato) {
    $texto = $dato['texto_usuario'];
    // Si el usuario marcó como incorrecto, usamos la intención que corrigió.
    $intencion = $dato['es_correcto'] ? $dato['prediccion_modelo'] : $dato['intencion_correcta'];
    
    if (!empty($intencion)) {
         // Formato CSV: "texto",intencion
        fputcsv($csv_file, [$texto, $intencion]);
        $count_added++;
    }
}
fclose($csv_file);
echo "Se añadieron " . $count_added . " ejemplos de alta calidad al archivo de entrenamiento.\n";

// 3. Ejecutar la lógica de entrenamiento del modelo (copiada de entrenar_modelo.php)
echo "Paso 3: Re-entrenando el modelo de IA...\n";
require_once 'vendor/autoload.php';
use Phpml\Dataset\CsvDataset;
use Phpml\FeatureExtraction\TokenCountVectorizer;
use Phpml\Tokenization\WordTokenizer;
use Phpml\Classification\NaiveBayes;

$dataset = new CsvDataset('training_data.csv', 1);
$samples = array_map(function($sample) { return $sample[0]; }, $dataset->getSamples());
$labels = $dataset->getTargets();
$vectorizer = new TokenCountVectorizer(new WordTokenizer());
$vectorizer->fit($samples);
$vectorizer->transform($samples);
$classifier = new NaiveBayes();
$classifier->train($samples, $labels);
$modelData = ['classifier' => $classifier, 'vectorizer' => $vectorizer];
file_put_contents('modelo_entrenado.phpml', serialize($modelData));
echo "¡Nuevo modelo de IA entrenado y guardado con éxito!\n";


// 4. Marcar los registros de feedback como procesados
echo "Paso 4: Marcando los registros de feedback como procesados...\n";
$pdo->query("UPDATE feedback_log SET procesado = TRUE WHERE procesado = FALSE");

echo "--- PROCESO DE RE-ENTRENAMIENTO COMPLETADO ---\n";
?>