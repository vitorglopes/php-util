<?php

$svgDirectory = 'public/icons/';
$outputCssFile = 'public/css/icons.css';

$relativePath = '../icons/';

if (!is_dir($svgDirectory)) {
    die("Diretório não encontrado.");
}

$cssFile = fopen($outputCssFile, 'w');

foreach (scandir($svgDirectory) as $file) {

    if (pathinfo($file, PATHINFO_EXTENSION) === 'svg') {

        $className = pathinfo($file, PATHINFO_FILENAME);

        $cssClass = <<<CSS
        .icon-$className {
            background-image: url('$relativePath$file');
        }

        CSS;

        fwrite($cssFile, $cssClass);
    }
}


fclose($cssFile);
echo "Arquivo CSS gerado com sucesso!";
