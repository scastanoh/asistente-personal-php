<?php
$input = 'training_data.csv';
$tmp   = 'training_data.tmp';

$valid = ['saludar','charla_general','listar_tareas','añadir_tarea','completar_tarea','eliminar_tarea'];

$in  = fopen($input, 'r');
$out = fopen($tmp,   'w');

while (($row = fgetcsv($in)) !== false) {
    if (count($row) < 2) continue;
    $texto = trim($row[0]);
    $y     = strtolower(trim($row[1]));
    if ($texto === '' || $y === '' || $y === 'null' || !in_array($y, $valid, true)) continue;
    fputcsv($out, [$texto, $y]);
}
fclose($in); fclose($out);
rename($tmp, $input);
echo "CSV saneado.\n";
