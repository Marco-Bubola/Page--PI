<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario_nome']) || !in_array($_SESSION['usuario_tipo'], ['coordenador', 'admin', 'professor'])) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit();
}
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !empty($_POST['plano_id']) &&
    !empty($_POST['titulo'])
) {
    $plano_id = intval($_POST['plano_id']);
    $titulo = trim($_POST['titulo']);
    $descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';
    $ordem = 0;
    $status = isset($_POST['status']) ? $_POST['status'] : 'em_andamento';
    $duracao = isset($_POST['duracao_estimativa']) ? intval($_POST['duracao_estimativa']) : null;
    require_once '../config/conexao.php';
    // Calcular ordem
    $res = $conn->prepare('SELECT MAX(ordem) as max_ordem FROM capitulos WHERE plano_id = ?');
    $res->bind_param('i', $plano_id);
    $res->execute();
    $result = $res->get_result();
    if ($row = $result->fetch_assoc()) {
        $ordem = is_null($row['max_ordem']) ? 0 : $row['max_ordem'] + 1;
    }
    $res->close();
    $stmt = $conn->prepare('INSERT INTO capitulos (plano_id, titulo, descricao, ordem, status, duracao_estimativa) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('issisi', $plano_id, $titulo, $descricao, $ordem, $status, $duracao);
    if ($stmt->execute()) {
        $id = $stmt->insert_id;
        $cap = $conn->query("SELECT * FROM capitulos WHERE id = $id")->fetch_assoc();
        echo json_encode(['success' => true, 'capitulo' => $cap]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar no banco']);
    }
    exit();
} else {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit();
}