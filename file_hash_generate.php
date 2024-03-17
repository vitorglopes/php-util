<?php
// Para gerar os hashes de um diretório: http://localhost/file_hash_generate.php?diretorio=/caminho/do/diretorio
// Função para gerar o hash SHA256 de um arquivo
function gerarHash($caminhoArquivo)
{
    if (file_exists($caminhoArquivo)) {
        $hash = hash_file('sha256', $caminhoArquivo);
        return $hash;
    } else {
        return "Arquivo não encontrado";
    }
}

// Função para gerar hashes de todos os arquivos em um diretório e subdiretórios
function gerarHashesDoDiretorio($diretorio)
{
    $hashes = array();

    // Verifica se o diretório existe
    if (is_dir($diretorio)) {
        // Abre o diretório
        if ($handle = opendir($diretorio)) {
            // Lê cada arquivo no diretório
            while (false !== ($entry = readdir($handle))) {
                $caminhoArquivo = $diretorio . '/' . $entry;

                // Ignora "." e ".."
                if ($entry != "." && $entry != "..") {
                    // Se for um diretório, chama a função recursivamente
                    if (is_dir($caminhoArquivo)) {
                        $subdiretorioHashes = gerarHashesDoDiretorio($caminhoArquivo);
                        $hashes = array_merge($hashes, $subdiretorioHashes);
                    } elseif (is_file($caminhoArquivo)) {
                        // Gera o hash e adiciona ao array
                        $hash = gerarHash($caminhoArquivo);
                        $hashes[$entry] = $hash;
                    }
                }
            }

            // Fecha o manipulador de diretório
            closedir($handle);
        }
    } else {
        return "Diretório não encontrado";
    }

    return $hashes;
}

// Verifica se o diretório foi fornecido via URL
if (isset($_GET['diretorio'])) {
    $diretorio = $_GET['diretorio'];

    // Gera os hashes dos arquivos no diretório e subdiretórios
    $hashes = gerarHashesDoDiretorio($diretorio);

    // Exibe o array de chave-valor como resposta
    echo json_encode($hashes, JSON_PRETTY_PRINT);
} else {
    echo "Parâmetro 'diretorio' ausente. Forneça o diretório via URL.";
}
