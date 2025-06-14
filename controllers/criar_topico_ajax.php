<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario_nome']) || !in_array($_SESSION['usuario_tipo'], ['coordenador', 'admin', 'professor'])) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit();
}
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !empty($_POST['capitulo_id']) &&
    !empty($_POST['titulo']) &&
    !empty($_POST['descricao'])
) {
    $capitulo_id = intval($_POST['capitulo_id']);
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $status = isset($_POST['status']) ? $_POST['status'] : 'em_andamento';
    $observacoes = isset($_POST['observacoes']) ? trim($_POST['observacoes']) : null;
    require_once '../config/conexao.php';
    // Calcular ordem automaticamente
    $ordem = 0;
    $res = $conn->prepare('SELECT MAX(ordem) as max_ordem FROM topicos WHERE capitulo_id = ?');
    $res->bind_param('i', $capitulo_id);
    $res->execute();
    $result = $res->get_result();
    if ($row = $result->fetch_assoc()) {
        $ordem = is_null($row['max_ordem']) ? 0 : $row['max_ordem'] + 1;
    }
    $res->close();
    $stmt = $conn->prepare('INSERT INTO topicos (capitulo_id, titulo, descricao, ordem, status, observacoes) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ississ', $capitulo_id, $titulo, $descricao, $ordem, $status, $observacoes);
    if ($stmt->execute()) {
        $id = $stmt->insert_id;
        $top = $conn->query("SELECT * FROM topicos WHERE id = $id")->fetch_assoc();
        // Atualizar status do capítulo para 'em_andamento' se estiver 'concluido'
        $updateCapitulo = $conn->prepare("UPDATE capitulos SET status = 'em_andamento' WHERE id = ? AND status = 'concluido'");
        $updateCapitulo->bind_param('i', $capitulo_id);
        $updateCapitulo->execute();
        $updateCapitulo->close();
        // Buscar plano_id do capítulo
        $getPlano = $conn->prepare("SELECT plano_id FROM capitulos WHERE id = ?");
        $getPlano->bind_param('i', $capitulo_id);
        $getPlano->execute();
        $getPlano->bind_result($plano_id);
        if ($getPlano->fetch()) {
            $getPlano->close();
            // Atualizar status do plano para 'em_andamento' se estiver 'concluido'
            $updatePlano = $conn->prepare("UPDATE planos SET status = 'em_andamento' WHERE id = ? AND status = 'concluido'");
            $updatePlano->bind_param('i', $plano_id);
            $updatePlano->execute();
            $updatePlano->close();
            // Buscar turma_id do plano
            $getTurma = $conn->prepare("SELECT turma_id FROM planos WHERE id = ?");
            $getTurma->bind_param('i', $plano_id);
            $getTurma->execute();
            $getTurma->bind_result($turma_id);
            if ($getTurma->fetch()) {
                $getTurma->close();
                // Atualizar status da turma para 'ativa' se estiver 'concluída'
                $updateTurma = $conn->prepare("UPDATE turmas SET status = 'ativa' WHERE id = ? AND status = 'concluída'");
                $updateTurma->bind_param('i', $turma_id);
                $updateTurma->execute();
                $updateTurma->close();
            } else {
                $getTurma->close();
            }
        } else {
            $getPlano->close();
        }
        echo json_encode(['success' => true, 'topico' => $top]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar no banco']);
    }
    exit();
} else {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit();
}