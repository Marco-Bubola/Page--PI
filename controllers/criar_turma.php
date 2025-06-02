<?php
session_start();
if (!isset($_SESSION['usuario_nome']) || ($_SESSION['usuario_tipo'] !== 'coordenador' && $_SESSION['usuario_tipo'] !== 'admin')) {
    header('Location: ../index.php');
    exit();
}
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !empty($_POST['nome']) &&
    !empty($_POST['ano_letivo']) &&
    !empty($_POST['turno'])
) {
    $nome = trim($_POST['nome']);
    $ano_letivo = intval($_POST['ano_letivo']);
    $turno = $_POST['turno'];
    $disciplinas = isset($_POST['disciplinas']) ? $_POST['disciplinas'] : [];
    $redirect = 'turmas.php';
    if (isset($_POST['redirect']) && preg_match('/^[a-zA-Z0-9_]+\.php$/', $_POST['redirect'])) {
        $redirect = $_POST['redirect'];
    }
    require_once '../config/conexao.php';
    $stmt = $conn->prepare('INSERT INTO turmas (nome, ano_letivo, turno) VALUES (?, ?, ?)');
    $stmt->bind_param('sis', $nome, $ano_letivo, $turno);
    if ($stmt->execute()) {
        $turma_id = $conn->insert_id;
        // Inserir vínculos em turma_disciplinas
        if (!empty($disciplinas)) {
            $stmtDisc = $conn->prepare('INSERT INTO turma_disciplinas (turma_id, disciplina_id) VALUES (?, ?)');
            foreach ($disciplinas as $disc_id) {
                $disc_id = intval($disc_id);
                $stmtDisc->bind_param('ii', $turma_id, $disc_id);
                $stmtDisc->execute();
            }
            $stmtDisc->close();
        }
        header('Location: ../views/' . $redirect . '?sucesso=turma_criada');
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