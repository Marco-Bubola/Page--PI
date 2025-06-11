<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario_nome']) || !in_array($_SESSION['usuario_tipo'], ['coordenador', 'admin', 'professor'])) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit();
}
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !empty($_POST['id_topico'])
) {
    $id = intval($_POST['id_topico']);
    require_once '../config/conexao.php';
    $stmt = $conn->prepare('DELETE FROM topicos WHERE id = ?');
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao excluir do banco']);
    }
    exit();
} else {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit();
}