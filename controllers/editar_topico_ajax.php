<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario_nome']) || !in_array($_SESSION['usuario_tipo'], ['coordenador', 'admin', 'professor'])) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit();
}
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !empty($_POST['id_topico']) &&
    !empty($_POST['capitulo_id']) &&
    !empty($_POST['titulo']) &&
    !empty($_POST['descricao'])
) {
    $id = intval($_POST['id_topico']);
    $capitulo_id = intval($_POST['capitulo_id']);
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $status = isset($_POST['status']) ? $_POST['status'] : 'em_andamento';
    $observacoes = isset($_POST['observacoes']) ? trim($_POST['observacoes']) : null;
    require_once '../config/conexao.php';
    $stmt = $conn->prepare('UPDATE topicos SET capitulo_id = ?, titulo = ?, descricao = ?, status = ?, observacoes = ? WHERE id = ?');
    $stmt->bind_param('issssi', $capitulo_id, $titulo, $descricao, $status, $observacoes, $id);
    if ($stmt->execute()) {
        $top = $conn->query("SELECT * FROM topicos WHERE id = $id")->fetch_assoc();
        echo json_encode(['success' => true, 'topico' => $top]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar no banco']);
    }
    exit();
} else {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit();
}