<?php
session_start();
if (!isset($_SESSION['usuario_nome']) || $_SESSION['usuario_tipo'] !== 'professor') {
    http_response_code(403);
    exit('Acesso negado');
}
require_once '../config/conexao.php';

$pagina = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$aulas_por_pagina = 5;
$offset = ($pagina - 1) * $aulas_por_pagina;
$grid = isset($_GET['grid']) && $_GET['grid'] == '1';

// Buscar total de aulas
$res_total = $conn->query("SELECT COUNT(*) as total FROM aulas");
$total_aulas = $res_total ? intval($res_total->fetch_assoc()['total']) : 0;
$total_paginas = ceil($total_aulas / $aulas_por_pagina);

// Buscar aulas ministradas (todos os professores)
$sql = "
    SELECT a.id, a.data, d.nome AS disciplina_nome, t.nome AS turma_nome, a.comentario, u.nome AS professor_nome
    FROM aulas a
    JOIN disciplinas d ON a.disciplina_id = d.id
    JOIN turmas t ON a.turma_id = t.id
    JOIN usuarios u ON a.professor_id = u.id
    ORDER BY a.data DESC, a.id DESC
    LIMIT $aulas_por_pagina OFFSET $offset
";
$res = $conn->query($sql);
$aulas = [];
$aula_ids = [];
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $aulas[] = $row;
        $aula_ids[] = $row['id'];
    }
}
// Buscar tópicos ministrados e personalizados para essas aulas
$topicos_aula = [];
$topicos_personalizados_aula = [];
if ($aula_ids) {
    $in_aulas = implode(',', array_map('intval', $aula_ids));
    // Tópicos planejados
    $sql = "SELECT tm.aula_id, t.titulo FROM topicos_ministrados tm JOIN topicos t ON tm.topico_id = t.id WHERE tm.aula_id IN ($in_aulas)";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $topicos_aula[$row['aula_id']][] = $row['titulo'];
    }
    // Tópicos personalizados
    $sql = "SELECT aula_id, descricao FROM topicos_personalizados WHERE aula_id IN ($in_aulas)";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $topicos_personalizados_aula[$row['aula_id']][] = $row['descricao'];
    }
}
// Montar HTML estilizado
$html = '';
if ($aulas) {
    foreach ($aulas as $aula) {
        $html .= $grid ? '<div class="col-12 col-md-6">' : '';
        $html .= '<div class="list-group-item aula-item mb-4 shadow-sm rounded-4 border-0 p-4" style="background: #f8fafc;">';
        $html .= '<div class="d-flex flex-wrap align-items-center mb-3 gap-2">';
        $html .= '<span class="badge bg-primary fs-6 px-3 py-2"><i class="fa-solid fa-calendar-day me-1"></i>' . date('d/m/Y', strtotime($aula['data'])) . '</span>';
        $html .= '<span class="badge bg-info text-dark fs-6 px-3 py-2"><i class="fa-solid fa-users me-1"></i>Turma: ' . htmlspecialchars($aula['turma_nome']) . '</span>';
        $html .= '<span class="badge bg-success fs-6 px-3 py-2"><i class="fa-solid fa-book me-1"></i>Disciplina: ' . htmlspecialchars($aula['disciplina_nome']) . '</span>';
        $html .= '<span class="badge bg-secondary fs-6 px-3 py-2"><i class="fa-solid fa-chalkboard-user me-1"></i>Prof: ' . htmlspecialchars($aula['professor_nome']) . '</span>';
        $html .= '</div>';
        if (!empty($topicos_aula[$aula['id']])) {
            $html .= '<div class="mb-2"><div class="fw-bold text-primary mb-1" style="font-size:1.08em;"><i class="fa-solid fa-list-check me-1"></i>Tópicos ministrados:</div><div class="d-flex flex-wrap gap-2">';
            foreach ($topicos_aula[$aula['id']] as $topico) {
                $html .= '<span class="badge bg-primary text-white px-3 py-2 rounded-pill fs-6"><i class="fa-solid fa-check me-1"></i>' . htmlspecialchars($topico) . '</span>';
            }
            $html .= '</div></div>';
        }
        if (!empty($topicos_personalizados_aula[$aula['id']])) {
            $html .= '<div class="mb-2"><div class="fw-bold text-warning mb-1" style="font-size:1.08em;"><i class="fa-solid fa-pen-nib me-1"></i>Tópicos personalizados:</div><div class="d-flex flex-wrap gap-2">';
            foreach ($topicos_personalizados_aula[$aula['id']] as $topico) {
                $html .= '<span class="badge bg-warning text-dark px-3 py-2 rounded-pill fs-6"><i class="fa-solid fa-star me-1"></i>' . htmlspecialchars($topico) . '</span>';
            }
            $html .= '</div></div>';
        }
        if (!empty($aula['comentario'])) {
            $html .= '<div class="mt-2"><div class="fw-bold text-info mb-1" style="font-size:1.08em;"><i class="fa-solid fa-comment-dots me-1"></i>Observações:</div>';
            $html .= '<div class="alert alert-info mb-0 py-2 px-3 rounded-3" style="font-size:1.08em;">' . nl2br(htmlspecialchars($aula['comentario'])) . '</div></div>';
        }
        $html .= '</div>';
        $html .= $grid ? '</div>' : '';
    }
} else {
    $html .= $grid ? '<div class="col-12"><div class="list-group-item text-muted"><i class="fa-solid fa-circle-exclamation me-1"></i>Nenhuma aula registrada.</div></div>' : '<li class="list-group-item text-muted"><i class="fa-solid fa-circle-exclamation me-1"></i>Nenhuma aula registrada.</li>';
}
echo $html;
