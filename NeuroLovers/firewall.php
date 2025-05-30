<?php
// firewall.php — proteção básica para suas páginas

$ip = $_SERVER['REMOTE_ADDR'];
$tempoAtual = time();
$limite = 30; // Máximo de acessos por IP por minuto
$janela = 60; // Tempo da janela (segundos)
$logDir = __DIR__ . '/logs';
$arquivoLog = $logDir . '/firewall_log.json';

// Cria pasta de logs se não existir
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}

// Lê os dados de log
$logs = [];
if (file_exists($arquivoLog)) {
    $conteudo = file_get_contents($arquivoLog);
    $logs = json_decode($conteudo, true);
    if (!is_array($logs)) {
        $logs = []; // Repara se estiver corrompido
    }
}

// Atualiza contador
if (!isset($logs[$ip])) {
    $logs[$ip] = ['vezes' => 1, 'inicio' => $tempoAtual];
} else {
    if (($tempoAtual - $logs[$ip]['inicio']) <= $janela) {
        $logs[$ip]['vezes']++;
    } else {
        // Reinicia janela
        $logs[$ip] = ['vezes' => 1, 'inicio' => $tempoAtual];
    }
}

// Bloqueia se passar do limite
if ($logs[$ip]['vezes'] > $limite) {
    http_response_code(429);
    echo "<h1>Você foi bloqueado temporariamente.</h1>";
    exit;
}

// Salva log atualizado
file_put_contents($arquivoLog, json_encode($logs));

// Verifica parâmetros perigosos (XSS / SQL Injection)
function contemAtaque($entrada) {
    $padroes = [
        '/<script.*?>.*?<\/script>/is',   // scripts
        '/(UNION|SELECT|INSERT|DROP|DELETE|UPDATE|--)/i', // SQL
        '/["\'=<>]/', // caracteres perigosos
    ];

    foreach ($entrada as $valor) {
        if (is_array($valor)) {
            if (contemAtaque($valor)) return true;
        } else {
            foreach ($padroes as $regex) {
                if (preg_match($regex, $valor)) {
                    return true;
                }
            }
        }
    }
    return false;
}

if (
    contemAtaque($_GET) ||
    contemAtaque($_POST) ||
    contemAtaque($_COOKIE)
) {
    http_response_code(403);
    echo "<h1>Conteúdo malicioso detectado.</h1>";
    exit;
}
?>
