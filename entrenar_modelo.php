<?php
require_once 'vendor/autoload.php';

use Phpml\Dataset\CsvDataset;
use Phpml\FeatureExtraction\TokenCountVectorizer;
use Phpml\Tokenization\WordTokenizer;
use Phpml\Classification\NaiveBayes;

echo "Iniciando el entrenamiento del modelo (versión robusta)...\n";

// 1. Cargar los datos
$dataset = new CsvDataset('training_data.csv', 1);

// 2. Limpiar los datos para ignorar líneas vacías o mal formateadas
$samples = [];
$labels = [];
foreach ($dataset->getSamples() as $index => $sample) {
    // Nos aseguramos de que la muestra no esté vacía y tenga texto
    if (isset($sample[0]) && !empty($sample[0])) {
        $samples[] = $sample[0];
        $labels[] = $dataset->getTargets()[$index];
    }
}

if (empty($samples)) {
    die("Error: No se encontraron datos de entrenamiento válidos en training_data.csv. Revisa el archivo.\n");
}

echo "Se han cargado " . count($samples) . " ejemplos de entrenamiento válidos.\n";

// 3. Convertir las frases en un formato numérico
$vectorizer = new TokenCountVectorizer(new WordTokenizer());
$vectorizer->fit($samples);
$vectorizer->transform($samples);

// 4. Crear y entrenar el clasificador
$classifier = new NaiveBayes();
$classifier->train($samples, $labels);

// 5. Guardar el nuevo modelo y el vectorizer
$modelData = [
    'classifier' => $classifier,
    'vectorizer' => $vectorizer
];

$modelFile = 'modelo_entrenado.phpml';
file_put_contents($modelFile, serialize($modelData));

echo "¡Entrenamiento completado! Modelo robusto guardado en '" . $modelFile . "'.\n";
?>