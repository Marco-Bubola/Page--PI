<?php
session_start();
header('Content-Type: application/json');
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
    $inicio = !empty($_POST['inicio']) ? $_POST['inicio'] : null;
    $fim = !empty($_POST['fim']) ? $_POST['fim'] : null;
    $status = isset($_POST['status']) ? $_POST['status'] : 'ativa';
    $disciplinas = isset($_POST['disciplinas']) ? $_POST['disciplinas'] : [];
    $redirect = 'turmas.php';
    if (isset($_POST['redirect']) && preg_match('/^[a-zA-Z0-9_]+\.php$/', $_POST['redirect'])) {
        $redirect = $_POST['redirect'];
    }
    require_once '../config/conexao.php';
    $stmt = $conn->prepare('UPDATE turmas SET nome = ?, ano_letivo = ?, turno = ?, inicio = ?, fim = ?, status = ? WHERE id = ?');
    $stmt->bind_param('sissssi', $nome, $ano_letivo, $turno, $inicio, $fim, $status, $id);
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
        // Buscar nomes das disciplinas
        $nomes = [];
        if (!empty($disciplinas)) {
            $ids = implode(',', array_map('intval', $disciplinas));
            $res = $conn->query("SELECT nome FROM disciplinas WHERE id IN ($ids)");
            while ($row = $res->fetch_assoc()) $nomes[] = $row['nome'];
        }
        $turma = [
            'id' => $id,
            'nome' => $nome,
            'ano_letivo' => $ano_letivo,
            'turno' => $turno,
            'inicio' => $inicio,
            'fim' => $fim,
            'status' => $status,
            'disciplinas_nomes' => $nomes,
            'inicio_br' => $inicio ? date('d/m/Y', strtotime($inicio)) : '',
            'fim_br' => $fim ? date('d/m/Y', strtotime($fim)) : ''
        ];
        echo json_encode(['success' => true, 'turma' => $turma]);
        exit();
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar no banco']);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit();
}