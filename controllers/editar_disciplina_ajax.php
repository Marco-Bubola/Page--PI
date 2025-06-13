<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario_nome']) || !in_array($_SESSION['usuario_tipo'], ['coordenador', 'admin'])) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit();
}
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !empty($_POST['id']) &&
    !empty($_POST['nome']) &&
    isset($_POST['codigo']) &&
    isset($_POST['descricao'])
) {
    $id = intval($_POST['id']);
    $nome = trim($_POST['nome']);
    $codigo = trim($_POST['codigo']);
    $descricao = trim($_POST['descricao']);
    $status = 'ativa'; // Sempre ativa ao editar

    require_once '../config/conexao.php';
    $stmt = $conn->prepare('SELECT id FROM disciplinas WHERE nome = ? AND id != ?');
    $stmt->bind_param('si', $nome, $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'Disciplina já existe!']);
        exit();
    }
    $stmt->close();
    $stmt = $conn->prepare('UPDATE disciplinas SET nome = ?, codigo = ?, descricao = ?, status = ? WHERE id = ?');
    $stmt->bind_param('ssssi', $nome, $codigo, $descricao, $status, $id);
    if ($stmt->execute()) {
        $disciplina = $conn->query("SELECT * FROM disciplinas WHERE id = $id")->fetch_assoc();
        echo json_encode(['success' => true, 'disciplina' => $disciplina]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar no banco']);
    }
    exit();
} else {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit();
}