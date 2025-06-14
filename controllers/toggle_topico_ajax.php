<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario_nome']) || !in_array($_SESSION['usuario_tipo'], ['coordenador', 'admin', 'professor'])) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id_topico']) && isset($_POST['status'])) {
    $id_topico = intval($_POST['id_topico']);
    $novo_status = $_POST['status'];
    require_once '../config/conexao.php';
    $stmt = $conn->prepare('UPDATE topicos SET status = ?, data_atualizacao = NOW() WHERE id = ?');
    $stmt->bind_param('si', $novo_status, $id_topico);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao atualizar status do tópico']);
    }
    $stmt->close();
    exit();
} else {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit();
}
