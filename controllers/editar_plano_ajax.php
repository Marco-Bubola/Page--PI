<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario_nome']) || !in_array($_SESSION['usuario_tipo'], ['coordenador', 'admin', 'professor'])) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit();
}
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !empty($_POST['id_plano']) &&
    !empty($_POST['disciplina_id']) &&
    !empty($_POST['titulo']) &&
    !empty($_POST['status']) &&
    isset($_POST['turma_id']) &&
    is_numeric($_POST['turma_id'])
) {
    $id = intval($_POST['id_plano']);
    $disciplina_id = intval($_POST['disciplina_id']);
    $titulo = trim($_POST['titulo']);
    $descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';
    $status = $_POST['status'];
    $turma_id = intval($_POST['turma_id']);
    $data_inicio = !empty($_POST['data_inicio']) ? $_POST['data_inicio'] : null;
    $data_fim = !empty($_POST['data_fim']) ? $_POST['data_fim'] : null;
    $objetivo_geral = isset($_POST['objetivo_geral']) ? trim($_POST['objetivo_geral']) : '';
    require_once '../config/conexao.php';
    $stmt = $conn->prepare('SELECT id FROM planos WHERE disciplina_id = ? AND turma_id = ? AND titulo = ? AND id != ?');
    $stmt->bind_param('iisi', $disciplina_id, $turma_id, $titulo, $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'Já existe um plano com esse título para a disciplina!']);
        exit();
    }
    $stmt->close();
    $stmt = $conn->prepare('UPDATE planos SET turma_id = ?, disciplina_id = ?, titulo = ?, descricao = ?, status = ?, data_inicio = ?, data_fim = ?, objetivo_geral = ? WHERE id = ?');
    $stmt->bind_param('iissssssi', $turma_id, $disciplina_id, $titulo, $descricao, $status, $data_inicio, $data_fim, $objetivo_geral, $id);
    if ($stmt->execute()) {
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