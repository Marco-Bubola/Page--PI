<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario_nome']) || !in_array($_SESSION['usuario_tipo'], ['coordenador', 'admin', 'professor'])) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit();
}
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !empty($_POST['id_capitulo']) &&
    !empty($_POST['titulo'])
) {
    $id = intval($_POST['id_capitulo']);
    $titulo = trim($_POST['titulo']);
    $descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';
    $status = isset($_POST['status']) ? $_POST['status'] : 'em_andamento';
    $duracao = isset($_POST['duracao_estimativa']) ? intval($_POST['duracao_estimativa']) : null;
    require_once '../config/conexao.php';
    $stmt = $conn->prepare('UPDATE capitulos SET titulo = ?, descricao = ?, status = ?, duracao_estimativa = ? WHERE id = ?');
    $stmt->bind_param('sssii', $titulo, $descricao, $status, $duracao, $id);
    if ($stmt->execute()) {
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