<?php
session_start();
if (!isset($_SESSION['usuario_nome']) || $_SESSION['usuario_tipo'] !== 'professor') {
    header('Location: index.php');
    exit();
}

$nome = $_SESSION['usuario_nome'];
$usuario_id = $_SESSION['usuario_id'];

include 'navbar.php';
include 'notificacao.php';
require_once '../config/conexao.php';

// --- BUSCA DE DADOS PARA O PROFESSOR ---

$turmas_professor = [];
$disciplinas_professor = [];
$disciplinas_nomes_professor = [];
$turma_disciplinas_professor = [];
$planos_turma_disciplina_professor = [];

// 1. Buscar apenas as turmas e disciplinas realmente vinculadas (usando turma_disciplinas)
$sql_turmas_disciplinas = "
    SELECT 
        t.id AS turma_id,
        t.nome AS turma_nome,
        t.ano_letivo,
        t.turno,
        d.id AS disciplina_id,
        d.nome AS disciplina_nome
    FROM 
        turmas t
    INNER JOIN 
        turma_disciplinas td ON td.turma_id = t.id
    INNER JOIN 
        disciplinas d ON td.disciplina_id = d.id
    ORDER BY 
        t.nome, d.nome
";
$stmt = $conn->prepare($sql_turmas_disciplinas);
// Não precisa de bind_param pois não há WHERE
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Turmas únicas
        if (!isset($turmas_professor[$row['turma_id']])) {
            $turmas_professor[$row['turma_id']] = [
                'id' => $row['turma_id'],
                'nome' => $row['turma_nome'],
                'ano_letivo' => $row['ano_letivo'],
                'turno' => $row['turno']
            ];
        }
        // Disciplinas únicas
        if (!isset($disciplinas_professor[$row['disciplina_id']])) {
            $disciplinas_professor[$row['disciplina_id']] = [
                'id' => $row['disciplina_id'],
                'nome' => $row['disciplina_nome']
            ];
            $disciplinas_nomes_professor[$row['disciplina_id']] = $row['disciplina_nome'];
        }
        // Vincular disciplinas a turmas
        if (!in_array($row['disciplina_id'], $turma_disciplinas_professor[$row['turma_id']] ?? [])) {
            $turma_disciplinas_professor[$row['turma_id']][] = $row['disciplina_id'];
        }
    }
}
$stmt->close();

// 2. Buscar planos de aula do professor para cada turma/disciplina
$sql_planos = "
    SELECT 
        p.id, p.titulo, p.status, p.turma_id, p.disciplina_id
    FROM 
        planos p
    ORDER BY 
        p.criado_em DESC
";
$stmt = $conn->prepare($sql_planos);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Armazena o plano mais recente para cada turma/disciplina
        if (!isset($planos_turma_disciplina_professor[$row['turma_id']][$row['disciplina_id']])) {
            $planos_turma_disciplina_professor[$row['turma_id']][$row['disciplina_id']] = [
                'id' => $row['id'],
                'titulo' => $row['titulo'],
                'status' => $row['status']
            ];
        }
    }
}
$stmt->close();

// 3. Buscar total de planos de aula criados pelo professor
$total_planos_professor = 0;
$res_planos = $conn->prepare('SELECT COUNT(*) AS total FROM planos WHERE criado_por = ?');
$res_planos->bind_param('i', $usuario_id);
$res_planos->execute();
$res_planos->bind_result($total_planos_professor);
$res_planos->fetch();
$res_planos->close();

// 4. Buscar últimos 5 planos de aula criados pelo professor
$ultimos_planos_professor = [];
$sql_ultimos_planos = "
    SELECT
        p.id, p.titulo, p.status, p.criado_em,
        d.nome AS disciplina_nome,
        t.nome AS turma_nome
    FROM
        planos p
    JOIN
        disciplinas d ON p.disciplina_id = d.id
    JOIN
        turmas t ON p.turma_id = t.id
    WHERE
        p.criado_por = ?
    ORDER BY
        p.criado_em DESC
    LIMIT 5
";
$stmt = $conn->prepare($sql_ultimos_planos);
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$result_ultimos_planos = $stmt->get_result();

if ($result_ultimos_planos && $result_ultimos_planos->num_rows > 0) {
    while ($row = $result_ultimos_planos->fetch_assoc()) {
        $ultimos_planos_professor[] = $row;
    }
}
$stmt->close();

// 5. Buscar todas as turmas ativas do banco para exibir para todos os professores
$turmas_ativas_professor = [];
$sql_todas_ativas = $conn->query("SELECT * FROM turmas WHERE status = 'ativa' ORDER BY ano_letivo DESC, nome");
if ($sql_todas_ativas && $sql_todas_ativas->num_rows > 0) {
    while ($row = $sql_todas_ativas->fetch_assoc()) {
        $turmas_ativas_professor[$row['id']] = $row;
    }
}
// Paginação dinâmica
$cards_por_pagina = 4;
$total_turmas_ativas = count($turmas_ativas_professor);
$total_paginas = ceil($total_turmas_ativas / $cards_por_pagina);
$pagina_atual = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($pagina_atual - 1) * $cards_por_pagina;
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
// Buscar planos existentes por turma e disciplina
$planos_turma_disciplina_professor = [];
$sql = "SELECT turma_id, disciplina_id, id, titulo, status FROM planos";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $planos_turma_disciplina_professor[$row['turma_id']][$row['disciplina_id']] = [
            'id' => $row['id'],
            'titulo' => $row['titulo'],
            'status' => $row['status']
        ];
    }
}

// --- FIM DA BUSCA DE DADOS ---
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Home Professor - PI Page</title>
    <link rel="icon" type="image/png" href="../assets/img/LOGO_PAGE.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="../assets/css/turmas.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; }
        .user { font-size: 1.5rem; color: #007bff; margin-top: 10px; }
        .card-home { border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,0.10); }
        .btn-nav { min-width: 220px; margin-bottom: 10px; font-size: 1.2rem; padding: 14px 0; }
        .section-title { font-size: 2rem; font-weight: 700; color: #222; margin-bottom: 1.2rem; display: flex; align-items: center; gap: 10px; }
        .section-desc { color: #666; font-size: 1.15rem; margin-bottom: 1.5rem; }
        .list-group-item { font-size: 1.15rem; padding: 1.1rem 1.2rem; border-radius: 10px; margin-bottom: 10px; }
        .badge { font-size: 1em; padding: 7px 12px; }
        .btn, .btn-sm { font-size: 1.1rem; padding: 10px 18px; border-radius: 8px; }
        .mb-4 { margin-bottom: 2.5rem!important; }
        .mb-3 { margin-bottom: 1.7rem!important; }
        .mb-2 { margin-bottom: 1.2rem!important; }
        .gap-2 { gap: 1.1rem!important; }
        .shadow-sm { box-shadow: 0 2px 10px rgba(0,0,0,0.07)!important; }
        .fs-2 { font-size: 2.7rem!important; }
        .fw-bold { font-weight: 800!important; }
        .text-muted { font-size: 1.1rem; }
        .card-turma {
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.13);
            background: linear-gradient(120deg, #f8fafc 80%, #e3f0ff 100%);
            border: none;
            transition: transform 0.13s, box-shadow 0.13s;
            position: relative;
        }
        .card-turma:hover {
            transform: translateY(-4px) scale(1.01);
            box-shadow: 0 8px 32px rgba(0,0,0,0.16);
        }
        .card-turma .card-title .bi-mortarboard-fill {
            font-size: 1.6rem;
            vertical-align: -0.2em;
            margin-right: 0.3em;
            color: #0d6efd;
            filter: drop-shadow(0 2px 6px #0d6efd33);
        }
        .card-turma .turma-actions .btn {
            border-radius: 8px;
            font-weight: 500;
            box-shadow: 0 1px 4px rgba(13,110,253,0.07);
        }
        .card-turma .turma-actions .btn-outline-primary {
            border: 1.5px solid #0d6efd;
        }
        .card-turma .turma-actions .btn-outline-primary:hover {
            background: #0d6efd;
            color: #fff;
        }
        .card-turma .badge {
            font-size: 1em;
            padding: 7px 13px;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .card-turma .badge.bg-info-subtle {
            background: #e3f0ff;
            color: #0d6efd;
            border: 1.5px solid #0d6efd33;
        }
        .card-turma .badge.bg-warning-subtle {
            background: #fffbe6;
            color: #ffc107;
            border: 1.5px solid #ffc10733;
        }
        .card-turma .badge.bg-light {
            background: #f8f9fa;
            color: #333;
            border: 1.5px solid #adb5bd33;
        }
        .card-turma .badge.bg-success {
            background: #e6f9ea;
            color: #198754;
            border: 1.5px solid #19875433;
        }
        .card-turma .badge.bg-secondary {
            background: #e9ecef;
            color: #6c757d;
            border: 1.5px solid #adb5bd33;
        }
        .card-turma .badge.bg-info.text-dark {
            background: #e3f0ff;
            color: #0d6efd;
            border: 1.5px solid #0d6efd33;
        }
        .card-turma .badge.bg-warning.text-dark {
            background: #fffbe6;
            color: #ffc107;
            border: 1.5px solid #ffc10733;
        }
        .card-turma .badge.bg-danger {
            background: #ffe6e9;
            color: #dc3545;
            border: 1.5px solid #dc354533;
        }
        .card-turma .badge.bg-light.text-dark {
            background: #f8f9fa;
            color: #333;
            border: 1.5px solid #adb5bd33;
        }
        .card-turma .disciplina-status {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 7px 10px;
            margin-bottom: 6px;
            font-size: 1.08em;
            box-shadow: 0 1px 4px rgba(13,110,253,0.04);
        }
        .card-turma .disciplina-status .bi-dot {
            color: #0d6efd;
            font-size: 1.2em;
        }
        .card-turma .disciplina-status .bi-check-circle-fill {
            color: #198754;
            font-size: 1.2em;
        }
        .card-turma .disciplina-status .bi-hourglass-split {
            color: #ffc107;
            font-size: 1.2em;
        }
        .card-turma .disciplina-status .bi-x-circle-fill {
            color: #dc3545;
            font-size: 1.2em;
        }
        .card-turma .disciplina-status .bi-journal-bookmark-fill {
            color: #0d6efd;
        }
        .card-turma .disciplina-status .bi-list-task {
            color: #0dcaf0;
        }
        .card-turma .disciplina-status .badge {
            margin-left: 0.3em;
            font-size: 0.98em;
            box-shadow: none;
        }
        .card-turma .turma-status-badge {
            font-size: 1em;
            font-weight: 600;
            border-radius: 8px;
            margin-left: 0.2em;
        }
        .card-turma .turma-label b {
            color: #0d6efd;
        }
        .card-turma .card-title {
            font-size: 1.18rem;
            font-weight: 700;
            color: #222;
        }
        .card-turma .card-body {
            padding: 1.2rem 1.3rem 1.1rem 1.3rem;
        }
        .card-turma .status-overlay {
            font-size: 1.2rem;
            padding: 0.5em 1.5em;
            border-radius: 1em;
            font-weight: bold;
            z-index: 2;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }
    </style>
</head>
<body>
<div class="container-fluid py-5">
    <div class="row">
        <div class="col-12">
            <div class="mb-4">
                <div class="row g-3">
                    <div class="col-6 col-md-4">
                        <div class="card text-center shadow-sm">
                            <div class="card-body">
                                <div class="fs-2 fw-bold text-primary"><i class="fa-solid fa-users me-2"></i><?php echo count($turmas_professor); ?></div>
                                <div class="text-muted">Minhas Turmas</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card text-center shadow-sm">
                            <div class="card-body">
                                <div class="fs-2 fw-bold text-success"><i class="fa-solid fa-book-open me-2"></i><?php echo count($disciplinas_professor); ?></div>
                                <div class="text-muted">Minhas Disciplinas</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card text-center shadow-sm">
                            <div class="card-body">
                                <div class="fs-2 fw-bold text-warning"><i class="fa-solid fa-clipboard-list me-2"></i><?php echo $total_planos_professor; ?></div>
                                <div class="text-muted">Meus Planos de Aula</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-home p-4 mb-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
                    <div>
                        <h2 class="mb-2 section-title"><i class="fa-solid fa-chalkboard-user"></i>Bem-vindo, Professor</h2>
                        <div class="user">Olá, <strong><?php echo htmlspecialchars($nome); ?></strong>!</div>
                    </div>
                   
                </div>
                <hr class="my-4">

                <div class="row g-4">
                    <div class="col-12 col-md-6">
                        <div class="section-title"><i class="fa-solid fa-users-viewfinder"></i>Minhas Turmas e Planos de Aula</div>
                        <div class="section-desc">Acompanhe as turmas em que você tem planos de aula e o status dos planos.</div>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <a href="turmas.php" class="btn btn-success btn-sm ms-2 d-flex align-items-center gap-2 px-3 py-2 shadow" style="font-weight:600; border-radius:10px; font-size:1.08rem;"><i class="fa-solid fa-users-viewfinder"></i> Ver Todas as Turmas</a>
                            <div id="turmas-pagination-nav">
                            <?php if (
                            isset(
                            $total_paginas
                            ) && $total_paginas > 1): ?>
                            <nav aria-label="Navegação de páginas" class="ms-auto">
                            <ul class="pagination justify-content-end mb-0">
                            <li class="page-item<?= $pagina_atual == 1 ? ' disabled' : '' ?>">
                            <a class="page-link" href="?page=1" title="Primeira">&laquo;</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <li class="page-item<?= $i == $pagina_atual ? ' active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"> <?= $i ?> </a>
                            </li>
                            <?php endfor; ?>
                            <li class="page-item<?= $pagina_atual == $total_paginas ? ' disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $total_paginas ?>" title="Última">&raquo;</a>
                            </li>
                            </ul>
                            </nav>
                            <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($turmas_ativas_professor)): ?>
                        <div id="turmas-cards-container">
                        <div class="row g-4">
                        <?php foreach ($turmas_pagina as $turma):
                                $cardClass = 'card card-turma h-100';
                                $statusOverlay = '';
                                // Overlay de status (não deve aparecer pois só mostra ativas, mas mantém para padrão)
                                if (isset($turma['status']) && $turma['status'] === 'cancelada') {
                                    $cardClass .= ' cancelada';
                                    $statusOverlay = '<div class="status-overlay">CANCELADA</div>';
                                } else if (isset($turma['status']) && $turma['status'] === 'concluída') {
                                    $cardClass .= ' concluida';
                                    $statusOverlay = '<div class="status-overlay"><i class="bi bi-check-circle-fill"></i> Concluída</div>';
                                }
                                // Para ações: se quiser limitar para professor, use $isProfessor = true;
                                $isProfessor = true;
                                // Buscar disciplinas e planos
                                $ids = isset($turma_disciplinas_professor[$turma['id']]) ? $turma_disciplinas_professor[$turma['id']] : [];
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
                            ?>
                            <div class="col-12 ">
                                <div class="<?= $cardClass ?>" id="turma-card-<?= $turma['id'] ?>">
                                    <?= $statusOverlay ?>
                                    <div class="card-body d-flex flex-column" style="position:relative;">
                                        <!-- Cabeçalho: Título + Ações -->
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <span class="fw-bold fs-5">
                                                <i class="fa-solid fa-graduation-cap text-primary me-1"></i>
                                                <?= htmlspecialchars($turma['nome']) ?>
                                                </span>
                                            </div>
                                            <div class="turma-actions">
                                              
                                                <a href="registro_aulas.php?turma_id=<?= $turma['id'] ?>" class="btn btn-primary btn-sm d-flex align-items-center gap-2 px-3 py-2 shadow" style="font-weight:600; border-radius:10px; font-size:1.08rem;" title="Registrar Aula">
                                                <i class="fa-solid fa-chalkboard-user"></i> Registrar Aula
                                                </a>
                                                
                                            </div>
                                        </div>
                                        <hr class="my-2">
                                        <!-- Dados principais -->
                                        <div class="mb-2">
                                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                                <span class="badge bg-info-subtle text-dark border border-info"><i class="fa-solid fa-calendar-days me-1"></i> Ano: <?= htmlspecialchars($turma['ano_letivo']) ?></span>
                                                <span class="badge bg-warning-subtle text-dark border border-warning"><i class="fa-solid fa-clock me-1"></i> Turno: <?= htmlspecialchars($turma['turno']) ?></span>
                                                <span class="badge bg-light text-dark border border-secondary"><i class="fa-solid fa-play me-1"></i> Início: <?= $turma['inicio'] ? date('d/m/Y', strtotime($turma['inicio'])) : '-' ?></span>
                                                <span class="badge bg-light text-dark border border-secondary"><i class="fa-solid fa-flag-checkered me-1"></i> Fim: <?= $turma['fim'] ? date('d/m/Y', strtotime($turma['fim'])) : '-' ?></span>
                                                <span class="badge <?php if ($turma['status']=='ativa') echo 'bg-success'; else if ($turma['status']=='cancelada') echo 'bg-secondary'; else if ($turma['status']=='concluída') echo 'bg-success'; ?> text-dark border border-<?= $turma['status']=='ativa'?'success':($turma['status']=='cancelada'?'secondary':'success') ?> turma-status-badge" id="turma-status-<?= $turma['id'] ?>">
                                                <i class="fa-solid fa-circle-info me-1"></i> <?= htmlspecialchars($turma['status']) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <hr class="my-2">
                                        <!-- Disciplinas e planos -->
                                        <div class="mb-2 turma-disc-list">
                                            <b><i class="fa-solid fa-book-open-reader me-1"></i> Disciplinas:</b>
                                            <ul class="list-unstyled ms-2 mb-0">
                                                <?php
                                                $ids = isset($turma_disciplinas_professor[$turma['id']]) ? $turma_disciplinas_professor[$turma['id']] : [];
                                                $temDisc = false;
                                                foreach ($disciplinas as $disc) {
                                                    if (in_array($disc['id'], $ids)) {
                                                        $temDisc = true;
                                                        $temPlano = isset($planosPorTurmaDisciplina[$turma['id']][$disc['id']]);
                                                        // Novo bloco estilizado para status do plano
                                                        echo '<li class="disciplina-status d-flex align-items-center gap-2" style="background:#f8f9fa; border-radius:8px; padding:7px 10px; margin-bottom:6px;">';
                                                        echo '<i class="fa-solid fa-book me-1 text-primary"></i> <span class="fw-semibold">' . htmlspecialchars($disc['nome']) . '</span>';
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
                                                                // Status visual
                                                                $icon = $plano_status === 'concluido'
                                                                    ? '<i class="bi bi-check-circle-fill text-success" title="Concluído"></i>'
                                                                    : '<i class="bi bi-hourglass-split text-warning" title="Em andamento"></i>';
                                                                $badge = $plano_status === 'concluido'
                                                                    ? '<span class="badge bg-success ms-1">Concluído</span>'
                                                                    : '<span class="badge bg-warning text-dark ms-1">Em andamento</span>';
                                                                echo " <span class='ms-2'>$icon  $badge</span>";
                                                                echo " <span class='ms-3'><i class='fa-solid fa-layer-group text-primary me-1'></i> <b>$capCount</b> capítulo(s)</span>";
                                                                echo " <span class='ms-2'><i class='fa-solid fa-list-ul text-info me-1'></i> <b>$topCount</b> tópico(s)</span>";
                                                            }
                                                        } else {
                                                            echo '<span class="text-danger ms-2"><i class="bi bi-x-circle-fill"></i> Sem plano</span>';
                                                        }
                                                        echo '</li>';
                                                    }
                                                }
                                                if (!$temDisc) echo '<li class="text-muted"><i class="bi bi-exclamation-circle"></i> Nenhuma</li>';
                                                ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                        <p class="text-muted"><i class="bi bi-exclamation-circle me-1"></i>Nenhuma turma ativa encontrada.</p>
                        <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                    <div class="mb-3 d-flex align-items-center justify-content-between">
                    <div class="section-title mb-0"><i class="fa-solid fa-chalkboard"></i>Histórico de Aulas Ministradas</div>
                    <a href="historico_aulas.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-chalkboard-user me-1"></i>Ver Todas as Aulas</a>
                    </div>
                    <div class="section-desc">Veja as aulas ministradas por todos os professores no sistema.</div>
                    <div class="card shadow-sm p-3 mb-3" style="background: linear-gradient(120deg, #f8fafc 80%, #e3f0ff 100%); border-radius: 18px;">
                    <div id="historico-aulas-pagination-nav"></div>
                    <ul class="list-group mb-2" id="historico-aulas-list">
                    <!-- Conteúdo AJAX -->
                    </ul>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Paginação dinâmica das turmas
function bindTurmasPagination() {
    document.querySelectorAll('#turmas-pagination-nav .pagination .page-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.getAttribute('href').replace('?page=', '');
            // Atualiza os cards
            fetch('../controllers/turmas_paginacao_ajax.php?page=' + page)
                .then(resp => resp.text())
                .then(html => {
                    const container = document.getElementById('turmas-cards-container');
                    if (container) {
                        container.innerHTML = html;
                    }
                });
            // Atualiza a paginação
            fetch('../controllers/turmas_pagination_nav_ajax.php?page=' + page)
                .then(resp => resp.text())
                .then(html => {
                    const nav = document.getElementById('turmas-pagination-nav');
                    if (nav) {
                        nav.innerHTML = html;
                        bindTurmasPagination(); // Rebind após atualização
                    }
                });
        });
    });
}
document.addEventListener('DOMContentLoaded', bindTurmasPagination);
</script>
<script>
// Paginação dinâmica das turmas
function bindTurmasPagination() {
    document.querySelectorAll('#turmas-pagination-nav .pagination .page-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.getAttribute('href').replace('?page=', '');
            // Atualiza os cards
            fetch('../controllers/turmas_paginacao_ajax.php?page=' + page)
                .then(resp => resp.text())
                .then(html => {
                    const container = document.getElementById('turmas-cards-container');
                    if (container) {
                        container.innerHTML = html;
                    }
                });
            // Atualiza a paginação
            fetch('../controllers/turmas_pagination_nav_ajax.php?page=' + page)
                .then(resp => resp.text())
                .then(html => {
                    const nav = document.getElementById('turmas-pagination-nav');
                    if (nav) {
                        nav.innerHTML = html;
                        bindTurmasPagination(); // Rebind após atualização
                    }
                });
        });
    });
}

// Paginação dinâmica do histórico de aulas
function bindHistoricoAulasPagination() {
    document.querySelectorAll('#historico-aulas-pagination-nav .pagination .page-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.getAttribute('href').replace('?page=', '');
            // Atualiza a lista
            fetch('../controllers/historico_aulas_ajax.php?page=' + page)
                .then(resp => resp.text())
                .then(html => {
                    const list = document.getElementById('historico-aulas-list');
                    if (list) {
                        list.innerHTML = html;
                    }
                });
            // Atualiza a paginação
            fetch('../controllers/historico_aulas_pagination_nav_ajax.php?page=' + page)
                .then(resp => resp.text())
                .then(html => {
                    const nav = document.getElementById('historico-aulas-pagination-nav');
                    if (nav) {
                        nav.innerHTML = html;
                        bindHistoricoAulasPagination();
                    }
                });
        });
    });
}
function loadHistoricoAulas(page) {
    fetch('../controllers/historico_aulas_ajax.php?page=' + page)
        .then(resp => resp.text())
        .then(html => {
            const list = document.getElementById('historico-aulas-list');
            if (list) {
                list.innerHTML = html;
            }
        });
    fetch('../controllers/historico_aulas_pagination_nav_ajax.php?page=' + page)
        .then(resp => resp.text())
        .then(html => {
            const nav = document.getElementById('historico-aulas-pagination-nav');
            if (nav) {
                nav.innerHTML = html;
                bindHistoricoAulasPagination();
            }
        });
}
document.addEventListener('DOMContentLoaded', function() {
    bindTurmasPagination();
    loadHistoricoAulas(1);
});
</script>
<?php include 'footer.php'; ?>
</body>
</html>
