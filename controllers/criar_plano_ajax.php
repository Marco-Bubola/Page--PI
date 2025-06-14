<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario_nome']) || !in_array($_SESSION['usuario_tipo'], ['coordenador', 'admin', 'professor'])) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit();
}
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !empty($_POST['disciplina_id']) &&
    !empty($_POST['titulo']) &&
    isset($_POST['turma_id']) &&
    is_numeric($_POST['turma_id'])
) {
    $disciplina_id = intval($_POST['disciplina_id']);
    $titulo = trim($_POST['titulo']);
    $descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';
    $criado_por = $_SESSION['usuario_id'];
    $turma_id = intval($_POST['turma_id']);
    $status = isset($_POST['status']) ? $_POST['status'] : 'em_andamento';
    $data_inicio = !empty($_POST['data_inicio']) ? $_POST['data_inicio'] : null;
    $data_fim = !empty($_POST['data_fim']) ? $_POST['data_fim'] : null;

    require_once '../config/conexao.php';
    $stmt = $conn->prepare('SELECT id FROM planos WHERE disciplina_id = ? AND turma_id = ? AND titulo = ?');
    $stmt->bind_param('iis', $disciplina_id, $turma_id, $titulo);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'Já existe um plano com esse título para a disciplina!']);
        exit();
    }
    $stmt->close();
    $stmt = $conn->prepare('INSERT INTO planos (turma_id, disciplina_id, titulo, descricao, status, criado_por, data_inicio, data_fim) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('iisssiss', $turma_id, $disciplina_id, $titulo, $descricao, $status, $criado_por, $data_inicio, $data_fim);
    if ($stmt->execute()) {
        // Atualizar status da turma para 'ativa' se estiver 'concluída'
        $updateTurma = $conn->prepare("UPDATE turmas SET status = 'ativa' WHERE id = ? AND status = 'concluída'");
        $updateTurma->bind_param('i', $turma_id);
        $updateTurma->execute();
        $updateTurma->close();
        $id = $stmt->insert_id;
        $plano = $conn->query("SELECT p.*, d.nome AS disciplina_nome FROM planos p JOIN disciplinas d ON p.disciplina_id = d.id WHERE p.id = $id")->fetch_assoc();
        echo json_encode(['success' => true, 'plano' => $plano]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar no banco']);
    }
    exit();
} else {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit();
}