<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario_nome']) || !in_array($_SESSION['usuario_tipo'], ['coordenador', 'admin'])) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
    $id = intval($_POST['id']);
    require_once '../config/conexao.php';
    $conn->begin_transaction();
    try {
        // Buscar planos da disciplina
        $sql = "SELECT id FROM planos WHERE disciplina_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $planos_ids = [];
        while ($row = $res->fetch_assoc()) $planos_ids[] = $row['id'];
        $stmt->close();

        if ($planos_ids) {
            $planos_ids_str = implode(',', $planos_ids);
            // Buscar capítulos desses planos
            $sql = "SELECT id FROM capitulos WHERE plano_id IN ($planos_ids_str)";
            $res = $conn->query($sql);
            $capitulos_ids = [];
            while ($row = $res->fetch_assoc()) $capitulos_ids[] = $row['id'];
            if ($capitulos_ids) {
                $capitulos_ids_str = implode(',', $capitulos_ids);
                // Buscar tópicos desses capítulos
                $sql = "SELECT id FROM topicos WHERE capitulo_id IN ($capitulos_ids_str)";
                $res = $conn->query($sql);
                $topicos_ids = [];
                while ($row = $res->fetch_assoc()) $topicos_ids[] = $row['id'];
                if ($topicos_ids) {
                    $topicos_ids_str = implode(',', $topicos_ids);
                    // Excluir topicos_ministrados desses tópicos
                    $conn->query("DELETE FROM topicos_ministrados WHERE topico_id IN ($topicos_ids_str)");
                    // Excluir tópicos
                    $conn->query("DELETE FROM topicos WHERE id IN ($topicos_ids_str)");
                }
                // Excluir capítulos
                $conn->query("DELETE FROM capitulos WHERE id IN ($capitulos_ids_str)");
            }
            // Excluir planos
            $conn->query("DELETE FROM planos WHERE id IN ($planos_ids_str)");
        }
        // Excluir vínculos com turmas
        $conn->query("DELETE FROM turma_disciplinas WHERE disciplina_id = $id");

        // Excluir aulas relacionadas à disciplina e seus topicos_personalizados
        $aulas_ids = [];
        $res = $conn->query("SELECT id FROM aulas WHERE disciplina_id = $id");
        while ($row = $res->fetch_assoc()) $aulas_ids[] = $row['id'];
        if ($aulas_ids) {
            $aulas_ids_str = implode(',', $aulas_ids);
            // Excluir topicos_personalizados dessas aulas
            $conn->query("DELETE FROM topicos_personalizados WHERE aula_id IN ($aulas_ids_str)");
            // Excluir topicos_ministrados dessas aulas (caso haja FK aula_id)
            $conn->query("DELETE FROM topicos_ministrados WHERE aula_id IN ($aulas_ids_str)");
            // Excluir aulas
            $conn->query("DELETE FROM aulas WHERE id IN ($aulas_ids_str)");
        }

        // Excluir disciplina
        $stmt = $conn->prepare('DELETE FROM disciplinas WHERE id = ?');
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) throw new Exception('Erro ao excluir disciplina');
        $stmt->close();

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => 'Erro ao excluir: ' . $e->getMessage()]);
    }
    exit();
} else {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit();
}