<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario_nome']) || !in_array($_SESSION['usuario_tipo'], ['coordenador', 'admin'])) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit();
}
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !empty($_POST['nome']) &&
    isset($_POST['codigo']) &&
    isset($_POST['descricao'])
) {
    $nome = trim($_POST['nome']);
    $codigo = trim($_POST['codigo']);
    $descricao = trim($_POST['descricao']);
    $status = 'ativa'; // Sempre ativa ao criar

    require_once '../config/conexao.php';
    $stmt = $conn->prepare('SELECT id FROM disciplinas WHERE nome = ?');
    $stmt->bind_param('s', $nome);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'Disciplina já existe!']);
        exit();
    }
    $stmt->close();
    $stmt = $conn->prepare('INSERT INTO disciplinas (nome, codigo, descricao, status) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('ssss', $nome, $codigo, $descricao, $status);
    if ($stmt->execute()) {
        $id = $stmt->insert_id;
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