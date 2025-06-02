<?php
session_start();
if (!isset($_SESSION['usuario_nome']) || ($_SESSION['usuario_tipo'] !== 'coordenador' && $_SESSION['usuario_tipo'] !== 'admin')) {
    header('Location: ../index.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id_turma'])) {
    $id = intval($_POST['id_turma']);
    $redirect = 'turmas.php';
    if (isset($_POST['redirect']) && preg_match('/^[a-zA-Z0-9_]+\.php$/', $_POST['redirect'])) {
        $redirect = $_POST['redirect'];
    }
    require_once '../config/conexao.php';
    // Verificar se há vínculos em turma_disciplinas
    $stmt = $conn->prepare('SELECT id FROM turma_disciplinas WHERE turma_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        header('Location: ../views/' . $redirect . '?erro=turma_vinculada');
        exit();
    }
    $stmt->close();
    $stmt = $conn->prepare('DELETE FROM turmas WHERE id = ?');
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        header('Location: ../views/' . $redirect . '?sucesso=turma_excluida');
        exit();
    } else {
        header('Location: ../views/' . $redirect . '?erro=erro_banco');
        exit();
    }
} else {
    $redirect = 'turmas.php';
    if (isset($_POST['redirect']) && preg_match('/^[a-zA-Z0-9_]+\.php$/', $_POST['redirect'])) {
        $redirect = $_POST['redirect'];
    }
    header('Location: ../views/' . $redirect . '?erro=dados_invalidos');
    exit();
} 