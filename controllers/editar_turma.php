<?php
session_start();
if (!isset($_SESSION['usuario_nome']) || ($_SESSION['usuario_tipo'] !== 'coordenador' && $_SESSION['usuario_tipo'] !== 'admin')) {
    header('Location: ../index.php');
    exit();
}
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !empty($_POST['id_turma']) &&
    !empty($_POST['nome']) &&
    !empty($_POST['ano_letivo']) &&
    !empty($_POST['turno'])
) {
    $id = intval($_POST['id_turma']);
    $nome = trim($_POST['nome']);
    $ano_letivo = intval($_POST['ano_letivo']);
    $turno = $_POST['turno'];
    $disciplinas = isset($_POST['disciplinas']) ? $_POST['disciplinas'] : [];
    $redirect = 'turmas.php';
    if (isset($_POST['redirect']) && preg_match('/^[a-zA-Z0-9_]+\.php$/', $_POST['redirect'])) {
        $redirect = $_POST['redirect'];
    }
    require_once '../config/conexao.php';
    $stmt = $conn->prepare('UPDATE turmas SET nome = ?, ano_letivo = ?, turno = ? WHERE id = ?');
    $stmt->bind_param('sisi', $nome, $ano_letivo, $turno, $id);
    if ($stmt->execute()) {
        // Remover vínculos antigos
        $conn->query('DELETE FROM turma_disciplinas WHERE turma_id = ' . $id);
        // Inserir novos vínculos
        if (!empty($disciplinas)) {
            $stmtDisc = $conn->prepare('INSERT INTO turma_disciplinas (turma_id, disciplina_id) VALUES (?, ?)');
            foreach ($disciplinas as $disc_id) {
                $disc_id = intval($disc_id);
                $stmtDisc->bind_param('ii', $id, $disc_id);
                $stmtDisc->execute();
            }
            $stmtDisc->close();
        }
        header('Location: ../views/' . $redirect . '?sucesso=turma_editada');
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