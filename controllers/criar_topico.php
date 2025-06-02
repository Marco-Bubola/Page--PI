<?php
session_start();
if (!isset($_SESSION['usuario_nome']) || ($_SESSION['usuario_tipo'] !== 'coordenador' && $_SESSION['usuario_tipo'] !== 'admin')) {
    header('Location: ../index.php');
    exit();
}
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !empty($_POST['capitulo_id']) &&
    !empty($_POST['descricao']) &&
    isset($_POST['ordem'])
) {
    $capitulo_id = intval($_POST['capitulo_id']);
    $descricao = trim($_POST['descricao']);
    $ordem = intval($_POST['ordem']);
    $redirect = 'planos.php';
    if (isset($_POST['redirect']) && preg_match('/^[a-zA-Z0-9_]+\.php\?id=\d+$/', $_POST['redirect'])) {
        $redirect = $_POST['redirect'];
    }
    require_once '../config/conexao.php';
    $stmt = $conn->prepare('INSERT INTO topicos (capitulo_id, descricao, ordem) VALUES (?, ?, ?)');
    $stmt->bind_param('isi', $capitulo_id, $descricao, $ordem);
    if ($stmt->execute()) {
        header('Location: ../views/' . $redirect . '&sucesso=topico_criado');
        exit();
    } else {
        header('Location: ../views/' . $redirect . '&erro=erro_banco');
        exit();
    }
} else {
    $redirect = 'planos.php';
    if (isset($_POST['redirect']) && preg_match('/^[a-zA-Z0-9_]+\.php\?id=\d+$/', $_POST['redirect'])) {
        $redirect = $_POST['redirect'];
    }
    header('Location: ../views/' . $redirect . '&erro=dados_invalidos');
    exit();
} 