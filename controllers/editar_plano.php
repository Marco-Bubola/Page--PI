<?php
session_start();
if (!isset($_SESSION['usuario_nome']) || ($_SESSION['usuario_tipo'] !== 'coordenador' && $_SESSION['usuario_tipo'] !== 'admin')) {
    header('Location: ../index.php');
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
    $redirect = 'planos.php';
    if (isset($_POST['redirect']) && preg_match('/^[a-zA-Z0-9_]+\.php(\?turma_id=\d+)?$/', $_POST['redirect'])) {
        $redirect = $_POST['redirect'];
    }
    require_once '../config/conexao.php';
    // Prevenir duplicidade de título por disciplina e turma (exceto o próprio plano)
    $stmt = $conn->prepare('SELECT id FROM planos WHERE disciplina_id = ? AND turma_id = ? AND titulo = ? AND id != ?');
    $stmt->bind_param('iisi', $disciplina_id, $turma_id, $titulo, $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        header('Location: ../views/' . $redirect . '?erro=plano_existente');
        exit();
    }
    $stmt->close();
    $stmt = $conn->prepare('UPDATE planos SET turma_id = ?, disciplina_id = ?, titulo = ?, descricao = ?, status = ? WHERE id = ?');
    $stmt->bind_param('iisssi', $turma_id, $disciplina_id, $titulo, $descricao, $status, $id);
    if ($stmt->execute()) {
        header('Location: ../views/' . $redirect . '?sucesso=plano_editado');
        exit();
    } else {
        header('Location: ../views/' . $redirect . '?erro=erro_banco');
        exit();
    }
} else {
    $redirect = 'planos.php';
    if (isset($_POST['redirect']) && preg_match('/^[a-zA-Z0-9_]+\.php(\?turma_id=\d+)?$/', $_POST['redirect'])) {
        $redirect = $_POST['redirect'];
    }
    header('Location: ../views/' . $redirect . '?erro=dados_invalidos');
    exit();
} 