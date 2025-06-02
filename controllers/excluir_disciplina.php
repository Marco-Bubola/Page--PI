<?php
session_start();
if (!isset($_SESSION['usuario_nome']) || ($_SESSION['usuario_tipo'] !== 'coordenador' && $_SESSION['usuario_tipo'] !== 'admin')) {
    header('Location: ../index.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id_disciplina'])) {
    $id = intval($_POST['id_disciplina']);
    require_once '../config/conexao.php';
    $stmt = $conn->prepare('DELETE FROM disciplinas WHERE id = ?');
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        header('Location: ../views/disciplinas.php?sucesso=disciplina_excluida');
        exit();
    } else {
        header('Location: ../views/disciplinas.php?erro=erro_banco');
        exit();
    }
} else {
    header('Location: ../views/disciplinas.php?erro=dados_invalidos');
    exit();
} 