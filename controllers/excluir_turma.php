<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario_nome']) || ($_SESSION['usuario_tipo'] !== 'coordenador' && $_SESSION['usuario_tipo'] !== 'admin')) {
    header('Location: ../index.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id_turma'])) {
    $id = intval($_POST['id_turma']);
    require_once '../config/conexao.php';
    // Remover vínculos em turma_disciplinas antes de excluir a turma
    $stmt = $conn->prepare('DELETE FROM turma_disciplinas WHERE turma_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    // Agora excluir a turma
    $stmt = $conn->prepare('DELETE FROM turmas WHERE id = ?');
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $id]);
        exit();
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao excluir do banco']);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit();
}