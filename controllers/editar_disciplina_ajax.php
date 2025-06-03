<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario_nome']) || ($_SESSION['usuario_tipo'] !== 'coordenador' && $_SESSION['usuario_tipo'] !== 'admin')) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit();
}
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !empty($_POST['id_disciplina']) &&
    !empty($_POST['nome_disciplina']) &&
    isset($_POST['codigo_disciplina']) &&
    isset($_POST['descricao_disciplina']) &&
    isset($_POST['ativa_disciplina'])
) {
    $id = intval($_POST['id_disciplina']);
    $nome = trim($_POST['nome_disciplina']);
    $codigo = trim($_POST['codigo_disciplina']);
    $descricao = trim($_POST['descricao_disciplina']);
    $ativa = isset($_POST['ativa_disciplina']) && $_POST['ativa_disciplina'] == '1' ? 1 : 0;
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
    $stmt = $conn->prepare('UPDATE disciplinas SET nome = ?, codigo = ?, descricao = ?, ativa = ? WHERE id = ?');
    $stmt->bind_param('sssii', $nome, $codigo, $descricao, $ativa, $id);
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