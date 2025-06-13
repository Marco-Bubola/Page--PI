<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario_nome']) || !in_array($_SESSION['usuario_tipo'], ['coordenador', 'admin'])) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id']) && !empty($_POST['acao'])) {
    $id = intval($_POST['id']);
    $acao = $_POST['acao'];
    require_once '../config/conexao.php';
    $conn->begin_transaction();
    try {
        if ($acao === 'cancelar') {
            // Cancelar disciplina
            $stmt = $conn->prepare('UPDATE disciplinas SET status = ? WHERE id = ?');
            $novo_status = 'cancelada';
            $stmt->bind_param('si', $novo_status, $id);
            if (!$stmt->execute()) throw new Exception('Erro ao cancelar disciplina');
            $stmt->close();

            // Cancelar planos relacionados
            $stmt = $conn->prepare('UPDATE planos SET status = ? WHERE disciplina_id = ?');
            $plano_status = 'cancelado';
            $stmt->bind_param('si', $plano_status, $id);
            $stmt->execute();
            $stmt->close();

            // Cancelar capítulos relacionados
            $sql = "SELECT id FROM planos WHERE disciplina_id = $id";
            $res = $conn->query($sql);
            $planos_ids = [];
            while ($row = $res->fetch_assoc()) $planos_ids[] = $row['id'];
            if ($planos_ids) {
                $planos_ids_str = implode(',', $planos_ids);
                $conn->query("UPDATE capitulos SET status = 'cancelado' WHERE plano_id IN ($planos_ids_str)");
                // Cancelar tópicos relacionados
                $sql = "SELECT id FROM capitulos WHERE plano_id IN ($planos_ids_str)";
                $res = $conn->query($sql);
                $capitulos_ids = [];
                while ($row = $res->fetch_assoc()) $capitulos_ids[] = $row['id'];
                if ($capitulos_ids) {
                    $capitulos_ids_str = implode(',', $capitulos_ids);
                    $conn->query("UPDATE topicos SET status = 'cancelado' WHERE capitulo_id IN ($capitulos_ids_str)");
                }
            }
        } elseif ($acao === 'ativar') {
            // ATIVAR disciplina e dependências
            $stmt = $conn->prepare('UPDATE disciplinas SET status = ? WHERE id = ?');
            $novo_status = 'ativa';
            $stmt->bind_param('si', $novo_status, $id);
            if (!$stmt->execute()) throw new Exception('Erro ao ativar disciplina');
            $stmt->close();

            $stmt = $conn->prepare('UPDATE planos SET status = ? WHERE disciplina_id = ?');
            $plano_status = 'em_andamento';
            $stmt->bind_param('si', $plano_status, $id);
            $stmt->execute();
            $stmt->close();

            $sql = "SELECT id FROM planos WHERE disciplina_id = $id";
            $res = $conn->query($sql);
            $planos_ids = [];
            while ($row = $res->fetch_assoc()) $planos_ids[] = $row['id'];
            if ($planos_ids) {
                $planos_ids_str = implode(',', $planos_ids);
                $conn->query("UPDATE capitulos SET status = 'em_andamento' WHERE plano_id IN ($planos_ids_str)");
                $sql = "SELECT id FROM capitulos WHERE plano_id IN ($planos_ids_str)";
                $res = $conn->query($sql);
                $capitulos_ids = [];
                while ($row = $res->fetch_assoc()) $capitulos_ids[] = $row['id'];
                if ($capitulos_ids) {
                    $capitulos_ids_str = implode(',', $capitulos_ids);
                    $conn->query("UPDATE topicos SET status = 'em_andamento' WHERE capitulo_id IN ($capitulos_ids_str)");
                }
            }
        } else {
            throw new Exception('Ação inválida');
        }

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => 'Erro ao atualizar: ' . $e->getMessage()]);
    }
    exit();
} else {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit();
}
