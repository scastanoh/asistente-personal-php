<?php
require_once 'vendor/autoload.php';

$model = unserialize(file_get_contents('modelo_entrenado.phpml'));
$classifier = $model['classifier'];
$vectorizer = $model['vectorizer'];

function predict($txt, $c, $v) {
    $s = [mb_strtolower(trim($txt), 'UTF-8')];
    $v->transform($s);
    $p = $c->predict($s);
    echo str_pad($txt, 20) . " -> " . $p[0] . PHP_EOL;
}

predict("hola", $classifier, $vectorizer);
predict("holaaaa", $classifier, $vectorizer);
predict("añade comprar leche", $classifier, $vectorizer);
predict("lista mis tareas", $classifier, $vectorizer);
