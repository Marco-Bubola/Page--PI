<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario_nome']) || ($_SESSION['usuario_tipo'] !== 'coordenador' && $_SESSION['usuario_tipo'] !== 'admin')) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit();
}
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !empty($_POST['capitulo_id']) &&
    !empty($_POST['titulo']) &&
    !empty($_POST['descricao'])
) {
    $capitulo_id = intval($_POST['capitulo_id']);
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $status = isset($_POST['status']) ? $_POST['status'] : 'em_andamento';
    $observacoes = isset($_POST['observacoes']) ? trim($_POST['observacoes']) : null;
    require_once '../config/conexao.php';
    // Calcular ordem automaticamente
    $ordem = 0;
    $res = $conn->prepare('SELECT MAX(ordem) as max_ordem FROM topicos WHERE capitulo_id = ?');
    $res->bind_param('i', $capitulo_id);
    $res->execute();
    $result = $res->get_result();
    if ($row = $result->fetch_assoc()) {
        $ordem = is_null($row['max_ordem']) ? 0 : $row['max_ordem'] + 1;
    }
    $res->close();
    $stmt = $conn->prepare('INSERT INTO topicos (capitulo_id, titulo, descricao, ordem, status, observacoes) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ississ', $capitulo_id, $titulo, $descricao, $ordem, $status, $observacoes);
    if ($stmt->execute()) {
        $id = $stmt->insert_id;
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