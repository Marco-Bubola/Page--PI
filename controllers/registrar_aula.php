<?php
header('Content-Type: application/json');
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit;
}
require_once '../config/conexao.php';

$professor_id = $_SESSION['usuario_id'];
$disciplina_id = isset($_POST['disciplina_id']) ? intval($_POST['disciplina_id']) : null;
$turma_id = isset($_POST['turma_id']) ? intval($_POST['turma_id']) : null;
$data = isset($_POST['data']) ? $_POST['data'] : null;
$comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : null;
$topicos = isset($_POST['topicos']) && is_array($_POST['topicos']) ? $_POST['topicos'] : [];
$topicos_personalizados = isset($_POST['topicos_personalizados']) && is_array($_POST['topicos_personalizados']) ? $_POST['topicos_personalizados'] : [];

if (!$professor_id || !$disciplina_id || !$turma_id || !$data || empty($topicos)) {
    echo json_encode(['success' => false, 'error' => 'Dados obrigatórios ausentes']);
    exit;
}

$conn->begin_transaction();
try {
    // 1. Inserir aula
    $stmt = $conn->prepare("INSERT INTO aulas (professor_id, disciplina_id, turma_id, data, comentario) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('iiiss', $professor_id, $disciplina_id, $turma_id, $data, $comentario);
    $stmt->execute();
    $aula_id = $stmt->insert_id;
    $stmt->close();

    // 2. Inserir tópicos ministrados
    $stmt = $conn->prepare("INSERT INTO topicos_ministrados (aula_id, topico_id) VALUES (?, ?)");
    foreach ($topicos as $topico_id) {
        $tid = intval($topico_id);
        $stmt->bind_param('ii', $aula_id, $tid);
        $stmt->execute();
    }
    $stmt->close();

    // 2b. Inserir tópicos personalizados
    if (!empty($topicos_personalizados)) {
        $stmt = $conn->prepare("INSERT INTO topicos_personalizados (aula_id, descricao) VALUES (?, ?)");
        foreach ($topicos_personalizados as $desc) {
            $desc = trim($desc);
            if ($desc !== '') {
                $stmt->bind_param('is', $aula_id, $desc);
                $stmt->execute();
            }
        }
        $stmt->close();
    }

    // 3. Atualizar status dos tópicos para 'concluido'
    $in_topicos = implode(',', array_map('intval', $topicos));
    $conn->query("UPDATE topicos SET status = 'concluido' WHERE id IN ($in_topicos)");

    // 4. Atualizar capítulos: se todos tópicos do capítulo estão concluídos, marcar capítulo como concluído
    $sql = "SELECT capitulo_id FROM topicos WHERE id IN ($in_topicos)";
    $result = $conn->query($sql);
    $capitulos_afetados = [];
    while ($row = $result->fetch_assoc()) {
        $capitulos_afetados[] = intval($row['capitulo_id']);
    }
    $capitulos_afetados = array_unique($capitulos_afetados);

    foreach ($capitulos_afetados as $cap_id) {
        // Verifica se todos tópicos do capítulo estão concluídos
        $sql = "SELECT COUNT(*) AS total, SUM(status='concluido') AS concluidos FROM topicos WHERE capitulo_id = $cap_id";
        $res = $conn->query($sql);
        $row = $res->fetch_assoc();
        if ($row && $row['total'] > 0 && $row['total'] == $row['concluidos']) {
            $conn->query("UPDATE capitulos SET status = 'concluido' WHERE id = $cap_id");
        }
    }

    // 5. Atualizar plano: se todos capítulos do plano estão concluídos, marcar plano como concluído
    // Descobrir plano_id dos capítulos afetados
    if (!empty($capitulos_afetados)) {
        $cap_ids_str = implode(',', $capitulos_afetados);
        $sql = "SELECT DISTINCT plano_id FROM capitulos WHERE id IN ($cap_ids_str)";
        $result = $conn->query($sql);
        $planos_afetados = [];
        while ($row = $result->fetch_assoc()) {
            $planos_afetados[] = intval($row['plano_id']);
        }
        foreach ($planos_afetados as $plano_id) {
            $sql = "SELECT COUNT(*) AS total, SUM(status='concluido') AS concluidos FROM capitulos WHERE plano_id = $plano_id";
            $res = $conn->query($sql);
            $row = $res->fetch_assoc();
            if ($row && $row['total'] > 0 && $row['total'] == $row['concluidos']) {
                $conn->query("UPDATE planos SET status = 'concluido' WHERE id = $plano_id");
            }
        }
    }

    $conn->commit();

    // --- NOVO: Verificar se todos os planos/capítulos/tópicos da turma estão concluídos ---
    // 1. Buscar todos os planos da turma
    $sql = "SELECT id FROM planos WHERE turma_id = $turma_id";
    $result = $conn->query($sql);
    $todos_planos = [];
    while ($row = $result->fetch_assoc()) {
        $todos_planos[] = intval($row['id']);
    }

    $turma_concluida = false;
    if (!empty($todos_planos)) {
        $planos_ids_str = implode(',', $todos_planos);

        // 2. Verifica se todos os planos estão concluídos
        $sql = "SELECT COUNT(*) AS total, SUM(status='concluido') AS concluidos FROM planos WHERE id IN ($planos_ids_str)";
        $res = $conn->query($sql);
        $row = $res->fetch_assoc();
        if ($row && $row['total'] > 0 && $row['total'] == $row['concluidos']) {
            // 3. Verifica se todos os capítulos dos planos estão concluídos
            $sql = "SELECT id FROM capitulos WHERE plano_id IN ($planos_ids_str)";
            $res = $conn->query($sql);
            $todos_capitulos = [];
            while ($r = $res->fetch_assoc()) $todos_capitulos[] = intval($r['id']);
            if (!empty($todos_capitulos)) {
                $capitulos_ids_str = implode(',', $todos_capitulos);
                $sql = "SELECT COUNT(*) AS total, SUM(status='concluido') AS concluidos FROM capitulos WHERE id IN ($capitulos_ids_str)";
                $res2 = $conn->query($sql);
                $row2 = $res2->fetch_assoc();
                if ($row2 && $row2['total'] > 0 && $row2['total'] == $row2['concluidos']) {
                    // 4. Verifica se todos os tópicos dos capítulos estão concluídos
                    $sql = "SELECT COUNT(*) AS total, SUM(status='concluido') AS concluidos FROM topicos WHERE capitulo_id IN ($capitulos_ids_str)";
                    $res3 = $conn->query($sql);
                    $row3 = $res3->fetch_assoc();
                    if ($row3 && $row3['total'] > 0 && $row3['total'] == $row3['concluidos']) {
                        // 5. Atualiza status da turma para concluída
                        $conn->query("UPDATE turmas SET status = 'concluída' WHERE id = $turma_id");
                        $turma_concluida = true;
                    }
                }
            }
        }
    }

    echo json_encode(['success' => true, 'turma_concluida' => $turma_concluida]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'Erro ao registrar aula: ' . $e->getMessage()]);
}
