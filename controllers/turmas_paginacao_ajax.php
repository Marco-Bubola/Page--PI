<?php
session_start();
if (!isset($_SESSION['usuario_nome']) || $_SESSION['usuario_tipo'] !== 'professor') {
    http_response_code(403);
    exit('Acesso negado');
}
require_once '../config/conexao.php';

$pagina = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$cards_por_pagina = 4;

// Buscar turmas ativas
$turmas_ativas_professor = [];
$sql_todas_ativas = $conn->query("SELECT * FROM turmas WHERE status = 'ativa' ORDER BY ano_letivo DESC, nome");
if ($sql_todas_ativas && $sql_todas_ativas->num_rows > 0) {
    while ($row = $sql_todas_ativas->fetch_assoc()) {
        $turmas_ativas_professor[$row['id']] = $row;
    }
}
$total_turmas_ativas = count($turmas_ativas_professor);
$total_paginas = ceil($total_turmas_ativas / $cards_por_pagina);
$offset = ($pagina - 1) * $cards_por_pagina;
$turmas_pagina = array_slice($turmas_ativas_professor, $offset, $cards_por_pagina, true);

// Buscar disciplinas vinculadas para cada turma
$turma_disciplinas_professor = [];
$disciplinas_nomes_professor = [];
$sql = 'SELECT td.turma_id, d.id as disciplina_id, d.nome as disciplina_nome FROM turma_disciplinas td JOIN disciplinas d ON td.disciplina_id = d.id';
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $turma_disciplinas_professor[$row['turma_id']][] = $row['disciplina_id'];
        $disciplinas_nomes_professor[$row['disciplina_id']] = $row['disciplina_nome'];
    }
}
// Buscar todas as disciplinas
$disciplinas = [];
$sql = 'SELECT * FROM disciplinas ORDER BY nome';
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $disciplinas[] = $row;
    }
}
// Buscar planos existentes por turma e disciplina
$planosPorTurmaDisciplina = [];
$sql = "SELECT turma_id, disciplina_id FROM planos";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $planosPorTurmaDisciplina[$row['turma_id']][$row['disciplina_id']] = true;
    }
}

// Gera o bloco completo dos cards (SEM PAGINAÇÃO)
$html = '<div class="row g-4">';
foreach ($turmas_pagina as $turma) {
    $cardClass = 'card card-turma h-100';
    $statusOverlay = '';
    if (isset($turma['status']) && $turma['status'] === 'cancelada') {
        $cardClass .= ' cancelada';
        $statusOverlay = '<div class="status-overlay">CANCELADA</div>';
    } else if (isset($turma['status']) && $turma['status'] === 'concluída') {
        $cardClass .= ' concluida';
        $statusOverlay = '<div class="status-overlay"><i class="fa-solid fa-check-circle"></i> Concluída</div>';
    }
    $isProfessor = true;
    $ids = isset($turma_disciplinas_professor[$turma['id']]) ? $turma_disciplinas_professor[$turma['id']] : [];
    $html .= '<div class="col-12">';
    $html .= '<div class="' . $cardClass . '" id="turma-card-' . $turma['id'] . '">';
    $html .= $statusOverlay;
    $html .= '<div class="card-body d-flex flex-column" style="position:relative;">';
    // Cabeçalho
    $html .= '<div class="d-flex justify-content-between align-items-center mb-2">';
    $html .= '<div><span class="fw-bold fs-5"><i class="fa-solid fa-graduation-cap text-primary me-1"></i>' . htmlspecialchars($turma['nome']) . '</span></div>';
    $html .= '<div class="turma-actions">';
    if ($isProfessor && $turma['status'] === 'ativa') {
        $html .= '<a href="../views/registro_aulas.php?turma_id=' . $turma['id'] . '" class="btn btn-primary btn-sm d-flex align-items-center gap-2 px-3 py-2 shadow" style="font-weight:600; border-radius:10px; font-size:1.08rem;" title="Registrar Aula"><i class="fa-solid fa-chalkboard-user"></i> Registrar Aula</a>';
    }
    $html .= '</div></div>';
    // Dados principais
    $html .= '<hr class="my-2">';
    $html .= '<div class="mb-2"><div class="d-flex flex-wrap gap-2 align-items-center">';
    $html .= '<span class="badge bg-info-subtle text-dark border border-info"><i class="fa-solid fa-calendar-days me-1"></i> Ano: ' . htmlspecialchars($turma['ano_letivo']) . '</span>';
    $html .= '<span class="badge bg-warning-subtle text-dark border border-warning"><i class="fa-solid fa-clock me-1"></i> Turno: ' . htmlspecialchars($turma['turno']) . '</span>';
    $html .= '<span class="badge bg-light text-dark border border-secondary"><i class="fa-solid fa-play me-1"></i> Início: ' . ($turma['inicio'] ? date('d/m/Y', strtotime($turma['inicio'])) : '-') . '</span>';
    $html .= '<span class="badge bg-light text-dark border border-secondary"><i class="fa-solid fa-flag-checkered me-1"></i> Fim: ' . ($turma['fim'] ? date('d/m/Y', strtotime($turma['fim'])) : '-') . '</span>';
    $html .= '<span class="badge ' . ($turma['status']=='ativa'?'bg-success':($turma['status']=='cancelada'?'bg-secondary':'bg-success')) . ' text-dark border border-' . ($turma['status']=='ativa'?'success':($turma['status']=='cancelada'?'secondary':'success')) . ' turma-status-badge" id="turma-status-' . $turma['id'] . '"><i class="fa-solid fa-circle-info me-1"></i> ' . htmlspecialchars($turma['status']) . '</span>';
    $html .= '</div></div>';
    $html .= '<hr class="my-2">';
    // Disciplinas e planos
    $html .= '<div class="mb-2 turma-disc-list">';
    $html .= '<b><i class="fa-solid fa-book-open-reader me-1"></i> Disciplinas:</b>';
    $html .= '<ul class="list-unstyled ms-2 mb-0">';
    $temDisc = false;
    foreach ($disciplinas as $disc) {
        if (in_array($disc['id'], $ids)) {
            $temDisc = true;
            $temPlano = isset($planosPorTurmaDisciplina[$turma['id']][$disc['id']]);
            $html .= '<li class="disciplina-status d-flex align-items-center gap-2" style="background:#f8f9fa; border-radius:8px; padding:7px 10px; margin-bottom:6px;">';
            $html .= '<i class="fa-solid fa-book me-1 text-primary"></i> <span class="fw-semibold">' . htmlspecialchars($disc['nome']) . '</span>';
            if ($temPlano) {
                // Buscar quantidade de capítulos e tópicos
                $plano_id = null;
                $sqlPlano = "SELECT id, status FROM planos WHERE turma_id = {$turma['id']} AND disciplina_id = {$disc['id']} LIMIT 1";
                $resPlano = $conn->query($sqlPlano);
                if ($rowPlano = $resPlano->fetch_assoc()) {
                    $plano_id = $rowPlano['id'];
                    $plano_status = $rowPlano['status'];
                    // Capítulos
                    $sqlCap = "SELECT id FROM capitulos WHERE plano_id = $plano_id";
                    $resCap = $conn->query($sqlCap);
                    $capCount = $resCap ? $resCap->num_rows : 0;
                    $topCount = 0;
                    if ($capCount > 0) {
                        $capIds = [];
                        while ($rowCap = $resCap->fetch_assoc()) $capIds[] = $rowCap['id'];
                        $capIdsStr = implode(',', $capIds);
                        $sqlTop = "SELECT COUNT(*) as total FROM topicos WHERE capitulo_id IN ($capIdsStr)";
                        $resTop = $conn->query($sqlTop);
                        $topCount = $resTop ? intval($resTop->fetch_assoc()['total']) : 0;
                    }
                    $icon = $plano_status === 'concluido'
                        ? '<i class="fa-solid fa-check-circle text-success" title="Concluído"></i>'
                        : '<i class="fa-solid fa-hourglass-half text-warning" title="Em andamento"></i>';
                    $badge = $plano_status === 'concluido'
                        ? '<span class="badge bg-success ms-1">Concluído</span>'
                        : '<span class="badge bg-warning text-dark ms-1">Em andamento</span>';
                    $html .= " <span class='ms-2'>$icon  $badge</span>";
                    $html .= " <span class='ms-3'><i class='fa-solid fa-layer-group text-primary me-1'></i> <b>$capCount</b> capítulo(s)</span>";
                    $html .= " <span class='ms-2'><i class='fa-solid fa-list-ul text-info me-1'></i> <b>$topCount</b> tópico(s)</span>";
                }
            } else {
                $html .= '<span class="text-danger ms-2"><i class="fa-solid fa-xmark-circle"></i> Sem plano</span>';
            }
            $html .= '</li>';
        }
    }
    if (!$temDisc) $html .= '<li class="text-muted"><i class="fa-solid fa-circle-exclamation"></i> Nenhuma</li>';
    $html .= '</ul></div>';
    $html .= '</div></div>';
    $html .= '</div>';
}
$html .= '</div>';
echo $html;
