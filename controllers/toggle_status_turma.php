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

// Iniciar transação
$conn->begin_transaction();

try {
    // Atualizar status da turma
    $upd = $conn->query("UPDATE turmas SET status = '$novo_status' WHERE id = $id");
    if (!$upd) {
        throw new Exception("Erro ao atualizar status da turma: " . $conn->error);
    }
    
    // Buscar todos os planos da turma
    $sql = "SELECT id, status FROM planos WHERE turma_id = $id";
    $res = $conn->query($sql);
    if (!$res) {
        throw new Exception("Erro ao buscar planos: " . $conn->error);
    }
    
    $planos_ids = [];
    $planos_antes = [];
    while ($row = $res->fetch_assoc()) {
        $planos_ids[] = $row['id'];
        $planos_antes[] = $row;
    }
    
    if (!empty($planos_ids)) {
        $planos_ids_str = implode(',', $planos_ids);
        
        // Atualizar status dos planos primeiro
        $status_plano = ($novo_status === 'cancelada') ? 'cancelado' : 'em_andamento';
        
        // Primeiro, vamos verificar se o status é válido
        $sql_check_status = "SELECT COUNT(*) as total FROM planos WHERE id IN ($planos_ids_str) AND status IN ('em_andamento', 'concluido', 'cancelado')";
        $res_check_status = $conn->query($sql_check_status);
        if (!$res_check_status) {
            throw new Exception("Erro ao verificar status válido: " . $conn->error);
        }
        $row_check = $res_check_status->fetch_assoc();
        if ($row_check['total'] != count($planos_ids)) {
            throw new Exception("Alguns planos têm status inválido");
        }
        
        // Agora vamos atualizar o status
        $sql_update_planos = "UPDATE planos SET status = ? WHERE id IN ($planos_ids_str)";
        $stmt = $conn->prepare($sql_update_planos);
        if (!$stmt) {
            throw new Exception("Erro ao preparar statement: " . $conn->error);
        }
        
        $stmt->bind_param('s', $status_plano);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao executar update dos planos: " . $stmt->error);
        }
        
        // Verificar se a atualização foi bem sucedida
        $sql_check = "SELECT id, status FROM planos WHERE id IN ($planos_ids_str)";
        $res_check = $conn->query($sql_check);
        if (!$res_check) {
            throw new Exception("Erro ao verificar status dos planos: " . $conn->error);
        }
        
        $planos_depois = [];
        while ($row = $res_check->fetch_assoc()) {
            $planos_depois[] = $row;
        }
        
        // Buscar todos os capítulos dos planos
        $sql = "SELECT id FROM capitulos WHERE plano_id IN ($planos_ids_str)";
        $res = $conn->query($sql);
        if (!$res) {
            throw new Exception("Erro ao buscar capítulos: " . $conn->error);
        }
        
        $capitulos_ids = [];
        while ($row = $res->fetch_assoc()) {
            $capitulos_ids[] = $row['id'];
        }
        
        if (!empty($capitulos_ids)) {
            $capitulos_ids_str = implode(',', $capitulos_ids);
            
            if ($novo_status === 'cancelada') {
                // Atualizar status dos tópicos para cancelado
                $upd_topicos = $conn->query("UPDATE topicos SET status = 'cancelado' WHERE capitulo_id IN ($capitulos_ids_str)");
                if (!$upd_topicos) {
                    throw new Exception("Erro ao atualizar status dos tópicos: " . $conn->error);
                }
                
                // Atualizar status dos capítulos para cancelado
                $upd_capitulos = $conn->query("UPDATE capitulos SET status = 'cancelado' WHERE id IN ($capitulos_ids_str)");
                if (!$upd_capitulos) {
                    throw new Exception("Erro ao atualizar status dos capítulos: " . $conn->error);
                }
            } else {
                // Atualizar status dos tópicos para em_andamento
                $upd_topicos = $conn->query("UPDATE topicos SET status = 'em_andamento' WHERE capitulo_id IN ($capitulos_ids_str)");
                if (!$upd_topicos) {
                    throw new Exception("Erro ao atualizar status dos tópicos: " . $conn->error);
                }
                
                // Atualizar status dos capítulos para em_andamento
                $upd_capitulos = $conn->query("UPDATE capitulos SET status = 'em_andamento' WHERE id IN ($capitulos_ids_str)");
                if (!$upd_capitulos) {
                    throw new Exception("Erro ao atualizar status dos capítulos: " . $conn->error);
                }
            }
        }
    }
    
    $conn->commit();
    echo json_encode([
        'success' => true, 
        'novo_status' => $novo_status,
        'planos_antes' => $planos_antes,
        'planos_depois' => $planos_depois ?? [],
        'status_plano' => $status_plano
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'Erro ao atualizar: ' . $e->getMessage()]);
}
