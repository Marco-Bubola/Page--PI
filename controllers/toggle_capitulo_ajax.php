<?php
ob_start(); // Garante que nenhum output seja enviado antes do header
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario_nome']) || !in_array($_SESSION['usuario_tipo'], ['coordenador', 'admin', 'professor'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id_capitulo']) && isset($_POST['status'])) {
    $id_capitulo = intval($_POST['id_capitulo']);
    $novo_status = $_POST['status'];
    require_once '../config/conexao.php';
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare('UPDATE capitulos SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $novo_status, $id_capitulo);
        if (!$stmt->execute()) throw new Exception('Erro ao atualizar status do capítulo');
        $stmt->close();
        // Se cancelar o capítulo, cancela todos os tópicos dele
        if ($novo_status === 'cancelado') {
            $stmt2 = $conn->prepare('UPDATE topicos SET status = ? WHERE capitulo_id = ?');
            $statusTopico = 'cancelado';
            $stmt2->bind_param('si', $statusTopico, $id_capitulo);
            if (!$stmt2->execute()) throw new Exception('Erro ao cancelar tópicos do capítulo');
            $stmt2->close();
        }
        // Se ativar o capítulo, ativa todos os tópicos dele
        else if ($novo_status === 'em_andamento') {
            $stmt2 = $conn->prepare('UPDATE topicos SET status = ? WHERE capitulo_id = ?');
            $statusTopico = 'em_andamento';
            $stmt2->bind_param('si', $statusTopico, $id_capitulo);
            if (!$stmt2->execute()) throw new Exception('Erro ao ativar tópicos do capítulo');
            $stmt2->close();
        }
        $conn->commit();
        ob_end_clean();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
} else {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit();
}
