<?php
session_start();
if (!isset($_SESSION['usuario_nome']) || ($_SESSION['usuario_tipo'] !== 'coordenador' && $_SESSION['usuario_tipo'] !== 'admin')) {
    header('Location: ../index.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id_topico'])) {
    $id = intval($_POST['id_topico']);
    $redirect = 'planos.php';
    if (isset($_POST['redirect']) && preg_match('/^[a-zA-Z0-9_]+\.php\?id=\d+$/', $_POST['redirect'])) {
        $redirect = $_POST['redirect'];
    }
    require_once '../config/conexao.php';
    $stmt = $conn->prepare('DELETE FROM topicos WHERE id = ?');
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        header('Location: ../views/' . $redirect . '&sucesso=topico_excluido');
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