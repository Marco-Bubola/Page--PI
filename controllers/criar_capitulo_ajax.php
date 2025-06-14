<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario_nome']) || !in_array($_SESSION['usuario_tipo'], ['coordenador', 'admin', 'professor'])) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit();
}
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !empty($_POST['plano_id']) &&
    !empty($_POST['titulo'])
) {
    $plano_id = intval($_POST['plano_id']);
    $titulo = trim($_POST['titulo']);
    $descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';
    $ordem = 0;
    $status = isset($_POST['status']) ? $_POST['status'] : 'em_andamento';
    require_once '../config/conexao.php';
    // Calcular ordem
    $res = $conn->prepare('SELECT MAX(ordem) as max_ordem FROM capitulos WHERE plano_id = ?');
    $res->bind_param('i', $plano_id);
    $res->execute();
    $result = $res->get_result();
    if ($row = $result->fetch_assoc()) {
        $ordem = is_null($row['max_ordem']) ? 0 : $row['max_ordem'] + 1;
    }
    $res->close();
    $stmt = $conn->prepare('INSERT INTO capitulos (plano_id, titulo, descricao, ordem, status) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('issis', $plano_id, $titulo, $descricao, $ordem, $status);
    if ($stmt->execute()) {
        $id = $stmt->insert_id;
        $cap = $conn->query("SELECT * FROM capitulos WHERE id = $id")->fetch_assoc();
        // Atualizar status do plano para 'em_andamento'
        $updatePlano = $conn->prepare("UPDATE planos SET status = 'em_andamento' WHERE id = ? AND status != 'em_andamento'");
        $updatePlano->bind_param('i', $plano_id);
        $updatePlano->execute();
        $updatePlano->close();
        // Atualizar status da turma para 'ativa' se estiver 'concluída'
        $getTurma = $conn->prepare("SELECT turma_id FROM planos WHERE id = ?");
        $getTurma->bind_param('i', $plano_id);
        $getTurma->execute();
        $getTurma->bind_result($turma_id);
        if ($getTurma->fetch()) {
            $getTurma->close();
            $updateTurma = $conn->prepare("UPDATE turmas SET status = 'ativa' WHERE id = ? AND status = 'concluída'");
            $updateTurma->bind_param('i', $turma_id);
            $updateTurma->execute();
            $updateTurma->close();
        } else {
            $getTurma->close();
        }
        echo json_encode(['success' => true, 'capitulo' => $cap]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar no banco']);
    }
    exit();
} else {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit();
}