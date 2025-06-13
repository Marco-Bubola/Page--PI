<?php
function log_debug($msg) {
    file_put_contents(__DIR__ . '/log_excluir_plano.txt', date('Y-m-d H:i:s') . " - " . $msg . PHP_EOL, FILE_APPEND);
}

log_debug('Início do script');

// Garante que não há saída antes do header
if (headers_sent()) {
    log_debug('Headers já enviados');
    echo json_encode(['success' => false, 'error' => 'Headers já enviados']);
    exit();
}

// Garante que é uma requisição AJAX
if (
    !isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest'
) {
    log_debug('Requisição não é AJAX');
    echo json_encode(['success' => false, 'error' => 'Requisição inválida']);
    exit();
}

session_start();
header('Content-Type: application/json');

// Desabilita exibição de erros para evitar HTML no output
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

try {
    log_debug('Sessão iniciada: ' . print_r($_SESSION, true));
    if (!isset($_SESSION['usuario_nome']) || !in_array($_SESSION['usuario_tipo'], ['coordenador', 'admin', 'professor'])) {
        log_debug('Acesso negado');
        echo json_encode(['success' => false, 'error' => 'Acesso negado']);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id_plano'])) {
        $id = intval($_POST['id_plano']);
        log_debug('ID do plano recebido: ' . $id);
        require_once '../config/conexao.php';

        if ($conn->connect_error) {
            log_debug('Erro de conexão: ' . $conn->connect_error);
            echo json_encode(['success' => false, 'error' => 'Erro de conexão com o banco de dados']);
            exit();
        }

        // Exclui tópicos ligados aos capítulos do plano
        $sqlTopicos = "DELETE t FROM topicos t
            INNER JOIN capitulos c ON t.capitulo_id = c.id
            WHERE c.plano_id = ?";
        $stmtTopicos = $conn->prepare($sqlTopicos);
        if ($stmtTopicos) {
            $stmtTopicos->bind_param('i', $id);
            $stmtTopicos->execute();
            log_debug('Tópicos excluídos: ' . $stmtTopicos->affected_rows);
            $stmtTopicos->close();
        } else {
            log_debug('Erro ao preparar exclusão de tópicos: ' . $conn->error);
        }

        // Exclui capítulos ligados ao plano
        $sqlCapitulos = "DELETE FROM capitulos WHERE plano_id = ?";
        $stmtCapitulos = $conn->prepare($sqlCapitulos);
        if ($stmtCapitulos) {
            $stmtCapitulos->bind_param('i', $id);
            $stmtCapitulos->execute();
            log_debug('Capítulos excluídos: ' . $stmtCapitulos->affected_rows);
            $stmtCapitulos->close();
        } else {
            log_debug('Erro ao preparar exclusão de capítulos: ' . $conn->error);
        }

        // Exclui o plano
        $stmt = $conn->prepare('DELETE FROM planos WHERE id = ?');
        if (!$stmt) {
            log_debug('Erro ao preparar exclusão do plano: ' . $conn->error);
            echo json_encode(['success' => false, 'error' => 'Erro ao preparar statement']);
            exit();
        }
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            log_debug('Plano excluído com sucesso');
            echo json_encode(['success' => true]);
            exit();
        } else {
            log_debug('Erro ao excluir plano: ' . $stmt->error);
            echo json_encode(['success' => false, 'error' => 'Erro ao excluir do banco']);
            exit();
        }
    } else {
        log_debug('Dados inválidos: ' . print_r($_POST, true));
        echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
        exit();
    }
} catch (Throwable $e) {
    log_debug('Exceção capturada: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro inesperado: ' . $e->getMessage()]);
    exit();
}