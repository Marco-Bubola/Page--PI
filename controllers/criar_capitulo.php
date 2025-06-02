<?php
session_start();
if (!isset($_SESSION['usuario_nome']) || ($_SESSION['usuario_tipo'] !== 'coordenador' && $_SESSION['usuario_tipo'] !== 'admin')) {
    header('Location: ../index.php');
    exit();
}
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !empty($_POST['plano_id']) &&
    !empty($_POST['titulo']) &&
    isset($_POST['ordem']) &&
    !empty($_POST['status'])
) {
    $plano_id = intval($_POST['plano_id']);
    $titulo = trim($_POST['titulo']);
    $ordem = intval($_POST['ordem']);
    $status = $_POST['status'];
    $redirect = 'planos.php';
    if (isset($_POST['redirect']) && preg_match('/^[a-zA-Z0-9_]+\.php\?id=\d+$/', $_POST['redirect'])) {
        $redirect = $_POST['redirect'];
    }
    require_once '../config/conexao.php';
    $stmt = $conn->prepare('INSERT INTO capitulos (plano_id, titulo, ordem, status) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('isis', $plano_id, $titulo, $ordem, $status);
    if ($stmt->execute()) {
        header('Location: ../views/' . $redirect . '&sucesso=capitulo_criado');
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