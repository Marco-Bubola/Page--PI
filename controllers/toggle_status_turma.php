<?php
require_once '../config/conexao.php';

if (!isset($_POST['id_turma'])) {
    echo json_encode(['success' => false, 'error' => 'ID não informado']);
    exit;
}

$id = intval($_POST['id_turma']);
$sql = "SELECT status FROM turmas WHERE id = $id";
$res = $conn->query($sql);
if (!$res || $res->num_rows == 0) {
    echo json_encode(['success' => false, 'error' => 'Turma não encontrada']);
    exit;
}
$row = $res->fetch_assoc();
$novo_status = ($row['status'] === 'ativa') ? 'cancelada' : 'ativa';
$upd = $conn->query("UPDATE turmas SET status = '$novo_status' WHERE id = $id");
if ($upd) {
    echo json_encode(['success' => true, 'novo_status' => $novo_status]);
} else {
    echo json_encode(['success' => false, 'error' => 'Erro ao atualizar']);
}
