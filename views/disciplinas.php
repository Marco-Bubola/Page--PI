<?php
session_start();
if (!isset($_SESSION['usuario_nome']) || !in_array($_SESSION['usuario_nome'], ['coordenador', 'admin', 'professor'])) {
    header('Location: index.php');
    exit();
}
include 'navbar.php';
include 'notificacao.php';
require_once '../config/conexao.php';

// --- Pesquisa e Paginação ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 8;
$where = '';
$params = [];
$types = '';

if ($search !== '') {
    $where = "WHERE nome LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}

// --- ORDEM SQL ---
$ordem_sql = "ORDER BY nome ASC";
if (isset($_GET['ordem']) && $_GET['ordem'] !== '') {
    if ($_GET['ordem'] == 'recentes') $ordem_sql = "ORDER BY id DESC";
    elseif ($_GET['ordem'] == 'antigas') $ordem_sql = "ORDER BY id ASC";
    elseif ($_GET['ordem'] == 'az') $ordem_sql = "ORDER BY nome ASC";
    elseif ($_GET['ordem'] == 'za') $ordem_sql = "ORDER BY nome DESC";
}

// --- FILTRO DE STATUS ---
if (isset($_GET['status_filtro']) && in_array($_GET['status_filtro'], ['ativa','cancelada'])) {
    $where .= ($where ? " AND " : "WHERE ") . "status = ?";
    $params[] = $_GET['status_filtro'];
    $types .= 's';
}

// Contar total de disciplinas para paginação
$sql_count = "SELECT COUNT(*) as total FROM disciplinas " . ($where ? $where : '');
$stmt_count = $conn->prepare($sql_count);
if ($where) $stmt_count->bind_param($types, ...$params);
$stmt_count->execute();
$res_count = $stmt_count->get_result();
$total_disciplinas = $res_count->fetch_assoc()['total'];
$stmt_count->close();

$total_pages = ceil($total_disciplinas / $per_page);
$offset = ($page - 1) * $per_page;

// --- QUANTIDADE POR PÁGINA ---
$per_page_options = [4, 8, 8, 12, 24, 48];
if (isset($_GET['per_page']) && in_array(intval($_GET['per_page']), $per_page_options)) {
    $per_page = intval($_GET['per_page']);
    $total_pages = ceil($total_disciplinas / $per_page);
    $offset = ($page - 1) * $per_page;
}

// Buscar disciplinas com filtro e paginação
$sql = "SELECT * FROM disciplinas " . ($where ? $where : '') . " $ordem_sql LIMIT $per_page OFFSET $offset";
$stmt = $conn->prepare($sql);
if ($where) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$disciplinas = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $disciplinas[] = $row;
    }
}
$stmt->close();

$isProfessor = (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'professor');

// --- Buscar contagem de turmas vinculadas por disciplina e status ---
$turmasPorDisciplina = [];
$sqlTurmas = "
    SELECT td.disciplina_id, t.status, COUNT(*) as total
    FROM turma_disciplinas td
    JOIN turmas t ON t.id = td.turma_id
    GROUP BY td.disciplina_id, t.status
";
$resTurmas = $conn->query($sqlTurmas);
if ($resTurmas && $resTurmas->num_rows > 0) {
    while ($row = $resTurmas->fetch_assoc()) {
        $did = $row['disciplina_id'];
        $status = $row['status'];
        $turmasPorDisciplina[$did][$status] = intval($row['total']);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Disciplinas - PI Page</title>
    <link rel="stylesheet" href="../assets/css/css_base_page.css">
    <link rel="icon" type="image/png" href="../assets/img/LOGO_PAGE.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/turmas.css" rel="stylesheet">
</head>

<body>
    <style>
    /* Garante que o modal customizado sempre fique acima de qualquer dropdown do Bootstrap */
    #modalDisciplina,
    #modalExcluirDisciplina,
    #modalCancelarAtivarDisciplina {
        z-index: 2000 !important;
    }
    .dropdown-menu {
        z-index: 1050;
    }
    body.modal-aberta {
        overflow: hidden;
        pointer-events: none;
    }
    body.modal-aberta .modal-custom-aberta {
        pointer-events: auto;
    }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <div class="container-fluid py-4">
        <!-- Cabeçalho igual ao de turmas -->
        <div class="row">
            <div class="col-12">
                <div class="bg-white rounded shadow-sm p-4 mb-3 border border-3 border-primary position-relative">
                    <div class="row align-items-end g-2 mb-2">
                        <div class="col-lg-6 col-md-6 col-12">
                            <div class="d-flex align-items-center gap-3 h-100">
                                <span class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                                    style="width:56px;height:56px;font-size:2.2rem;box-shadow:0 2px 8px #0d6efd33;">
                                    <i class="bi bi-book-fill"></i>
                                </span>
                                <div>
                                    <h2 class="mb-0 fw-bold text-primary">Disciplinas</h2>
                                    <div class="text-muted" style="font-size:1.08em;">
                                        <i class="bi bi-info-circle"></i>
                                        <?php if ($isProfessor): ?>
                                            Visualize as disciplinas disponíveis no sistema.
                                        <?php else: ?>
                                            Gerencie e visualize todas as disciplinas cadastradas no sistema.
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-5 col-12">
                            <form class="d-flex align-items-end" id="filtrosForm" method="get" action="">
                                <div class="input-group flex-nowrap" style="max-width:360px;">
                                    <span class="input-group-text bg-white border-end-0" style="border-radius:8px 0 0 8px;">
                                        <i class="bi bi-search text-primary"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0 border-end-0" name="search"
                                        placeholder="Pesquisar disciplina..." value="<?= htmlspecialchars($search) ?>"
                                        style="border-radius:0; box-shadow:none;">
                                    <?php if ($search !== ''): ?>
                                    <button type="submit" class="btn btn-outline-secondary border-start-0 border-end-0"
                                        style="border-radius:0;" tabindex="-1"
                                        onclick="this.form.search.value=''; this.form.submit(); return false;"
                                        title="Limpar pesquisa">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button class="btn btn-primary" type="submit" style="border-radius:0 8px 8px 0;">
                                        <i class="bi bi-arrow-right-circle"></i>
                                    </button>
                                </div>
                                <div class="dropdown ms-2 d-flex align-items-center gap-2" style="z-index:1050;">
                                    <button class="btn btn-gradient-primary dropdown-toggle fw-bold shadow-sm px-3 py-2"
                                        type="button" id="dropdownFiltros" data-bs-toggle="dropdown" aria-expanded="false"
                                        style="border-radius: 12px; background: linear-gradient(90deg,#0d6efd 60%,#4f8cff 100%); color: #fff; border: none;" data-bs-boundary="viewport">
                                        <i class="bi bi-funnel-fill me-1"></i> Filtros
                                    </button>
                                    <!-- Botão de dicas -->
                                    <button type="button" class="btn btn-gradient-dicas shadow-sm px-3 py-2 d-flex align-items-center gap-2 fw-bold" id="btnDicasDisciplinas" title="Dicas da página" style="border-radius: 14px; font-size:1.13em; box-shadow: 0 2px 8px #0d6efd33;">
                                        <i class="bi bi-lightbulb-fill" style="font-size:1.35em;"></i>
                                        Dicas
                                    </button>
                                    <div class="dropdown-menu p-4 shadow-lg border-0"
                                        style="min-width: 520px; border-radius: 18px; background: #f8faff;"
                                        onclick="event.stopPropagation();">
                                        <div class="mb-3">
                                            <label class="form-label mb-2 fw-semibold text-primary">
                                                <i class="bi bi-eye me-1"></i>Mostrar:
                                            </label>
                                            <div class="btn-group w-100" role="group" aria-label="Mostrar por página">
                                                <?php
                                                    $per_page_options = [4, 8, 12, 24, 48];
                                                    $per_page_sel = isset($_GET['per_page']) ? intval($_GET['per_page']) : $per_page;
                                                ?>
                                                <?php foreach ($per_page_options as $opt): ?>
                                                <input type="radio" class="btn-check" name="per_page"
                                                    id="per_page_<?= $opt ?>" value="<?= $opt ?>" autocomplete="off"
                                                    <?= $per_page_sel==$opt?'checked':'' ?>>
                                                <label class="btn btn-outline-primary" for="per_page_<?= $opt ?>"
                                                    style="min-width:44px;">
                                                    <i class="bi bi-list-ol"></i> <?= $opt ?>
                                                </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label mb-2 fw-semibold text-primary">
                                                <i class="bi bi-sort-alpha-down me-1"></i>Ordenar:
                                            </label>
                                            <div class="btn-group w-100" role="group" aria-label="Ordenar">
                                                <?php
                                                    $ordem = isset($_GET['ordem']) ? $_GET['ordem'] : 'az';
                                                    $ordem_opts = [
                                                        'recentes' => ['icon' => 'bi-clock-history', 'label' => 'Mais recentes'],
                                                        'antigas' => ['icon' => 'bi-clock', 'label' => 'Mais antigas'],
                                                        'az' => ['icon' => 'bi-sort-alpha-down', 'label' => 'A-Z'],
                                                        'za' => ['icon' => 'bi-sort-alpha-up', 'label' => 'Z-A']
                                                    ];
                                                ?>
                                                <?php foreach ($ordem_opts as $key => $info): ?>
                                                <input type="radio" class="btn-check" name="ordem" id="ordem_<?= $key ?>"
                                                    value="<?= $key ?>" autocomplete="off" <?= $ordem==$key?'checked':'' ?>>
                                                <label class="btn btn-outline-primary" for="ordem_<?= $key ?>"
                                                    style="min-width:90px;">
                                                    <i class="bi <?= $info['icon'] ?>"></i> <?= $info['label'] ?>
                                                </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label mb-2 fw-semibold text-primary">
                                                <i class="bi bi-activity me-1"></i>Status:
                                            </label>
                                            <div class="btn-group w-100" role="group" aria-label="Status">
                                                <?php
                                                    $status_filtro = isset($_GET['status_filtro']) ? $_GET['status_filtro'] : '';
                                                    $status_opts = [
                                                        '' => ['icon' => 'bi-ui-checks', 'label' => 'Todos'],
                                                        'ativa' => ['icon' => 'bi-check-circle-fill text-success', 'label' => 'Ativas'],
                                                        'cancelada' => ['icon' => 'bi-x-circle-fill text-danger', 'label' => 'cancelada']
                                                    ];
                                                ?>
                                                <?php foreach ($status_opts as $key => $info): ?>
                                                <input type="radio" class="btn-check" name="status_filtro"
                                                    id="status_<?= $key ?: 'todos' ?>" value="<?= $key ?>"
                                                    autocomplete="off" <?= $status_filtro===$key?'checked':'' ?>>
                                                <label class="btn btn-outline-primary" for="status_<?= $key ?: 'todos' ?>"
                                                    style="min-width:90px;">
                                                    <i class="bi <?= $info['icon'] ?>"></i> <?= $info['label'] ?>
                                                </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-gradient-primary w-100 mt-2 fw-bold"
                                            style="border-radius: 8px; background: linear-gradient(90deg,#0d6efd 60%,#4f8cff 100%); color: #fff; border: none;">
                                            <i class="bi bi-funnel"></i> Salvar Filtros
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <?php if (!$isProfessor): ?>
                        <div class="col-lg-2 col-md-12 col-12 d-flex justify-content-lg-end justify-content-start mt-2 mt-lg-0">
                            <button class="btn btn-success d-flex align-items-center gap-2 shadow-sm px-3 py-2"
                                style="font-size:1em;" onclick="abrirModalDisciplina()">
                                <i class="bi bi-plus-circle"></i> Nova Disciplina
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="row g-2 mt-2 mb-1">
                        <div class="col-auto">
                            <span class="badge bg-primary-subtle text-primary border border-primary d-flex align-items-center gap-1"
                                style="font-size:1.08em;">
                                <i class="bi bi-collection"></i> Total: <b><?= $total_disciplinas ?></b>
                            </span>
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-success-subtle text-success border border-success d-flex align-items-center gap-1"
                                style="font-size:1.08em;">
                                <i class="bi bi-check-circle-fill"></i> Ativas: <b><?= count(array_filter($disciplinas, fn($d) => $d['status']=='ativa')) ?></b>
                            </span>
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary d-flex align-items-center gap-1"
                                style="font-size:1.08em;">
                                <i class="bi bi-x-circle-fill"></i> Canceladas: <b><?= count(array_filter($disciplinas, fn($d) => $d['status']=='cancelada')) ?></b>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Cards de disciplinas -->
        <div class="row">
            <div class="col-12">
                <div class="row g-4">
                    <?php if (empty($disciplinas)): ?>
                    <div class="col-12">
                        <div class="alert alert-warning text-center">Nenhuma disciplina encontrada.</div>
                    </div>
                    <?php endif; ?>
                    <?php foreach ($disciplinas as $disc): ?>
                    <?php
                        $cardClass = 'card card-turma h-100';
                        $statusOverlay = '';
                        if ($disc['status'] === 'cancelada') {
                            $cardClass .= ' cancelada';
                            $statusOverlay = '<div class="status-overlay">CANCELADA</div>';
                        } else if ($disc['status'] === 'concluída') {
                            $cardClass .= ' concluida';
                            $statusOverlay = '<div class="status-overlay"><i class="bi bi-check-circle-fill"></i> Concluída</div>';
                        }
                        // Contagem de turmas vinculadas
                        $vinc = isset($turmasPorDisciplina[$disc['id']]) ? $turmasPorDisciplina[$disc['id']] : [];
                        $totalTurmas = array_sum($vinc);
                        $ativas = isset($vinc['ativa']) ? $vinc['ativa'] : 0;
                        $concluidas = isset($vinc['concluída']) ? $vinc['concluída'] : 0;
                        $canceladas = isset($vinc['cancelada']) ? $vinc['cancelada'] : 0;
                    ?>
                    <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                        <div class="<?= $cardClass ?>" id="disciplina-card-<?= $disc['id'] ?>">
                            <?= $statusOverlay ?>
                            <div class="card-body d-flex flex-column gap-2" style="position:relative;">
                                <div class="d-flex justify-content-between align-items-center mb-2 gap-2 flex-wrap">
                                    <span class="fw-bold fs-4 d-flex align-items-center gap-2">
                                        <i class="bi bi-book-fill text-primary"></i>
                                        <?= htmlspecialchars($disc['nome']) ?>
                                    </span>
                                    <?php if (!$isProfessor): ?>
                                    <div class="turma-actions d-flex gap-1">
                                        <button class="btn btn-primary btn-sm" title="Editar"
                                            onclick="abrirModalEditarDisciplina(
                                                <?= $disc['id'] ?>, 
                                                '<?= htmlspecialchars(addslashes($disc['nome'])) ?>', 
                                                '<?= htmlspecialchars(addslashes($disc['codigo'] ?? '')) ?>', 
                                                `<?= htmlspecialchars(addslashes($disc['descricao'] ?? '')) ?>`, 
                                                '<?= $disc['status'] ?>'
                                            )">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm" title="Excluir"
                                            onclick="abrirModalExcluirDisciplina(<?= $disc['id'] ?>, '<?= htmlspecialchars(addslashes($disc['nome'])) ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <button class="btn p-0 m-0 border-0 bg-transparent turma-toggle-btn"
                                            style="font-size:2.1rem; line-height:1; box-shadow:none;"
                                            title="<?= $disc['status']=='ativa'?'Cancelar':'Ativar' ?> disciplina"
                                            onclick="abrirModalToggleDisciplina(<?= $disc['id'] ?>, '<?= htmlspecialchars(addslashes($disc['nome'])) ?>', '<?= $disc['status'] ?>', this)">
                                            <i class="bi <?= $disc['status']=='ativa'?'bi-toggle-off':'bi-toggle-on' ?>"></i>
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                 <?php if (!empty($disc['descricao'])): ?>
                                <div class="mb-2 text-muted" style="font-size:1.12em;">
                                    <i class="bi bi-info-circle"></i> <?= nl2br(htmlspecialchars($disc['descricao'])) ?>
                                </div>
                                <?php endif; ?>
                               
                                <div class="mb-2 d-flex flex-row flex-wrap align-items-center gap-2 justify-content-center" style="font-size:1.22em;font-weight:600;">
                                       <?php if (!empty($disc['codigo'])): ?>
                                    <span class="badge bg-warning-subtle text-dark border border-warning" style="font-size:1.08em;"><i class="bi bi-qr-code"></i> <?= htmlspecialchars($disc['codigo']) ?></span>
                                    <?php endif; ?>
                                    <span class="badge <?= $disc['status']=='ativa'?'bg-success':'bg-secondary' ?> text-dark border border-<?= $disc['status']=='ativa'?'success':'secondary' ?> turma-status-badge" id="disciplina-status-<?= $disc['id'] ?>" style="font-size:1.08em;">
                                        <i class="bi bi-activity"></i> <?= htmlspecialchars($disc['status']) ?>
                                    </span>
                                <span class="badge bg-primary-subtle text-primary border border-primary d-flex align-items-center gap-1 px-3 py-2" title="Total de turmas vinculadas">
                                        <i class="bi bi-collection"></i> <?= $totalTurmas ?> turma(s)
                                    </span>
                                    <span class="badge bg-success-subtle text-success border border-success d-flex align-items-center gap-1 px-3 py-2" title="Turmas ativas">
                                        <i class="bi bi-check-circle-fill"></i> <?= $ativas ?> ativas
                                    </span>
                                    <span class="badge bg-info-subtle text-info border border-info d-flex align-items-center gap-1 px-3 py-2" title="Turmas concluídas">
                                        <i class="bi bi-flag"></i> <?= $concluidas ?> concluídas
                                    </span>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary d-flex align-items-center gap-1 px-3 py-2" title="Turmas canceladas">
                                        <i class="bi bi-x-circle-fill"></i> <?= $canceladas ?> canceladas
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <!-- PAGINAÇÃO -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Navegação de páginas">
                    <ul class="pagination justify-content-center mt-4">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page-1])) ?>"
                                tabindex="-1" aria-label="Anterior">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php
                            $max_links = 2;
                            $start = max(1, $page - $max_links);
                            $end = min($total_pages, $page + $max_links);
                            if ($start > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?'.http_build_query(array_merge($_GET, ['page'=>1])).'">1</a></li>';
                                if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            for ($i = $start; $i <= $end; $i++):
                        ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link"
                                href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                        </li>
                        <?php endfor;
                            if ($end < $total_pages) {
                                if ($end < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                echo '<li class="page-item"><a class="page-link" href="?'.http_build_query(array_merge($_GET, ['page'=>$total_pages])).'">'.$total_pages.'</a></li>';
                            }
                        ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page+1])) ?>"
                                aria-label="Próxima">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Modal de Dicas de Funcionamento -->
    <div id="modalDicasDisciplinas" style="display:none;position:fixed;z-index:2100;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.45);">
        <div class="modal-dicas-content" style="max-width:760px;min-width:420px;width:98vw;">
            <div class="modal-dicas-header">
                <div class="modal-dicas-icone">
                    <i class="bi bi-lightbulb-fill"></i>
                </div>
                <h4 class="mb-0 text-white">Dicas de Funcionamento</h4>
                <span onclick="fecharModalDicasDisciplinas()" class="modal-dicas-close">&times;</span>
            </div>
            <div class="modal-dicas-body">
                <!-- Stepper -->
                <div id="stepperDicas" class="mb-4">
                    <div class="d-flex justify-content-center align-items-center gap-2 mb-3">
                        <span class="step-circle" id="stepCircle1"><i class="bi"></i></span>
                        <span class="step-line"></span>
                        <span class="step-circle" id="stepCircle2"><i class="bi"></i></span>
                        <span class="step-line"></span>
                        <span class="step-circle" id="stepCircle3"><i class="bi"></i></span>
                        <span class="step-line"></span>
                        <span class="step-circle" id="stepCircle4"><i class="bi"></i></span>
                        <span class="step-line"></span>
                        <span class="step-circle" id="stepCircle5"><i class="bi"></i></span>
                        <span class="step-line"></span>
                        <span class="step-circle" id="stepCircle6"><i class="bi"></i></span>
                    </div>
                </div>
                <div id="stepContentDicas">
                    <!-- Conteúdo dos steps será preenchido via JS -->
                </div>
            </div>
            <div class="modal-dicas-footer">
                <button class="btn btn-outline-primary" id="btnStepAnterior" style="display:none;"><i class="bi bi-arrow-left"></i> Anterior</button>
                <button class="btn btn-outline-primary ms-3" id="btnStepProximo">Próximo <i class="bi bi-arrow-right"></i></button>
            </div>
        </div>
        <style>
        .modal-dicas-content {
            background: #fff;
            border-radius: 22px;
            max-width: 540px;
            width: 96vw;
            min-width: 340px;
            min-height: 660px;
            max-height: 900px;
            margin: 60px auto;
            position: relative;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .modal-dicas-header {
            background: linear-gradient(90deg,#0d6efd 60%,#4f8cff 100%);
            padding: 28px 36px 20px 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 18px;
            position: relative;
            text-align: center;
        }
        .modal-dicas-icone {
            background: #fff;
            color: #0d6efd;
            border-radius: 50%;
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            box-shadow: 0 2px 8px #0d6efd33;
        }
        .modal-dicas-header h4 {
            color: #fff;
            font-weight: bold;
            font-size: 1.55em;
            margin-bottom: 0;
            flex: 1 1 auto;
            text-align: center;
        }
        .modal-dicas-close {
            position: absolute;
            top: 18px;
            right: 28px;
            font-size: 32px;
            cursor: pointer;
            color: #fff;
            opacity: 0.8;
            transition: opacity 0.2s;
        }
        .modal-dicas-close:hover {
            opacity: 1;
        }
        .modal-dicas-body {
            padding: 38px 32px 28px 32px;
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }
        .modal-dicas-footer {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 18px;
            padding: 22px 0 18px 0;
            background: #f8faff;
            border-top: 1.5px solid #e3e9f7;
            border-radius: 0 0 22px 22px;
            min-height: 70px;
        }
        .modal-dicas-footer .btn {
            min-width: 120px;
            font-size: 1.08em;
            font-weight: 500;
        }
        #modalDicasDisciplinas .step-circle {
            width: 32px; height: 32px; border-radius: 50%; background: #e3e9f7; color: #0d6efd; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.15em; border: 2px solid #b6c6e6;
            transition: background 0.2s, color 0.2s;
        }
        #modalDicasDisciplinas .step-circle.active {
            background: #0d6efd; color: #fff; border-color: #0d6efd;
        }
        #modalDicasDisciplinas .step-line {
            flex: 1 1 0; height: 3px; background: #b6c6e6;
        }
        #stepContentDicas {
            min-height: 110px;
            max-height: 180px;
            margin-bottom: 0.5em;
        }
        @media (max-width: 600px) {
            .modal-dicas-content {
                max-width: 98vw;
                min-width: 0;
                padding: 0;
            }
            .modal-dicas-header, .modal-dicas-body {
                padding-left: 12px;
                padding-right: 12px;
            }
        }
        .dica-step-card {
    display: flex;
    align-items: flex-start;
    gap: 18px;
    border-radius: 16px;
    padding: 18px 18px 18px 18px;
    margin-bottom: 0.5em;
    box-shadow: 0 2px 12px #e3e9f7;
    font-size: 1.13em;
    font-weight: 500;
    background: #f8faff;
}
.dica-step-icone {
    font-size: 2.3em;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 2px;
    box-shadow: 0 2px 8px #e3e9f7;
}
.dica-blue { color: #0d6efd; background: #e3e9f7; }
.dica-green { color: #198754; background: #e6f7ec; }
.dica-yellow { color: #ffc107; background: #fffbe6; }
.dica-red { color: #dc3545; background: #ffe6e9; }
.dica-orange { color: #fd7e14; background: #fff3e6; }
.dica-purple { color: #6f42c1; background: #f3e6ff; }
.bg-dica-blue { background: #f8faff; }
.bg-dica-green { background: #e6f7ec; }
.bg-dica-yellow { background: #fffbe6; }
.bg-dica-red { background: #ffe6e9; }
.bg-dica-orange { background: #fff3e6; }
.bg-dica-purple { background: #f3e6ff; }
.text-dica-blue { color: #0d6efd; }
.text-dica-purple { color: #6f42c1; }
.btn-gradient-dicas {
    background: linear-gradient(90deg,#0d6efd 60%,#4f8cff 100%);
    color: #fff !important;
    border: none;
    transition: background 0.2s, box-shadow 0.2s;
}
.btn-gradient-dicas:hover, .btn-gradient-dicas:focus {
    background: linear-gradient(90deg,#4f8cff 60%,#0d6efd 100%);
    color: #fff !important;
    box-shadow: 0 4px 16px #0d6efd33;
}
</style>
    </div>
    <!-- Modal de Criar/Editar Disciplina -->
    <div id="modalDisciplina"
        style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);">
        <div
            style="background:#fff;padding:0;border-radius:18px;max-width:520px;width:95vw;margin:60px auto;position:relative;box-shadow:0 8px 32px rgba(0,0,0,0.18);overflow:hidden;">
            <div
                style="background:linear-gradient(90deg,#0d6efd 60%,#4f8cff 100%);padding:18px 32px 14px 32px;display:flex;align-items:center;gap:12px;">
                <i class="bi bi-book-fill text-white" style="font-size:2rem;"></i>
                <h4 id="tituloModalDisciplina" class="mb-0 text-white">Criar Disciplina</h4>
                <span onclick="fecharModalDisciplina()"
                    style="position:absolute;top:14px;right:22px;font-size:28px;cursor:pointer;color:#fff;opacity:0.8;">&times;</span>
            </div>
            <div style="padding:30px 32px 18px 32px;">
                <form id="formDisciplina" action="../controllers/criar_disciplina.php" method="POST" autocomplete="off">
                    <input type="hidden" name="id_disciplina" id="id_disciplina">
                    <div class="input-group mb-3">
                        <span class="input-group-text bg-white"><i class="bi bi-book"></i></span>
                        <input type="text" name="nome" id="nome_disciplina" placeholder="Nome da disciplina" required
                            class="form-control" maxlength="100" autocomplete="off" oninput="atualizarCodigoDisciplina()">
                    </div>
                    <div class="input-group mb-3">
                        <span class="input-group-text bg-white"><i class="bi bi-qr-code"></i></span>
                        <input type="text" name="codigo" id="codigo_disciplina" placeholder="Código automático"
                            class="form-control" readonly style="background:#f8f9fa;">
                    </div>
                    <div class="input-group mb-3">
                        <span class="input-group-text bg-white"><i class="bi bi-info-circle"></i></span>
                        <textarea name="descricao" id="descricao_disciplina" class="form-control" placeholder="Descrição" rows="2" maxlength="255"></textarea>
                    </div>
                    <!-- Status sempre ativa, não editável -->
                    <input type="hidden" name="status" id="status_disciplina" value="ativa">
                    <input type="hidden" name="redirect" value="disciplinas.php">
                </form>
                <div class="d-flex justify-content-end gap-2 pt-3 border-top mt-4" style="background:transparent;">
                    <button type="button" class="btn btn-secondary d-flex align-items-center gap-1"
                        onclick="fecharModalDisciplina()"><i class="bi bi-x-circle"></i> Cancelar</button>
                    <button type="submit" class="btn btn-primary d-flex align-items-center gap-1" id="btnSalvarDisciplina"
                        form="formDisciplina"><i class="bi bi-check-circle"></i> Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmar Exclusão -->
    <div id="modalExcluirDisciplina"
        style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);">
        <div
            style="background:#fff;padding:0;border-radius:22px;max-width:600px;width:96vw;margin:100px auto;position:relative;box-shadow:0 8px 32px rgba(0,0,0,0.18);overflow:hidden;">
            <div
                style="background:linear-gradient(90deg,#dc3545 60%,#ff6f91 100%);padding:26px 40px 18px 40px;display:flex;align-items:center;gap:18px;">
                <i class="bi bi-trash-fill text-white" style="font-size:2.5rem;"></i>
                <h3 class="mb-0 text-white" style="font-weight:700;font-size:1.6rem;">Excluir Disciplina</h3>
                <span onclick="fecharModalExcluirDisciplina()"
                    style="position:absolute;top:18px;right:32px;font-size:36px;cursor:pointer;color:#fff;opacity:0.8;">&times;</span>
            </div>
            <div style="padding:38px 40px 24px 40px;">
                <div class="alert alert-danger d-flex flex-column align-items-center gap-3 mb-4 text-center" style="font-size:1.18em;">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size:2.1em;"></i>
                    <div>
                        <b class="fw-bold" style="font-size:1.13em;">Atenção!</b> <br>
                        Esta ação <span class="fw-bold text-danger">não poderá ser desfeita</span>.<br>
                        <span style="font-size:1.04em;">
                            Ao excluir a disciplina, <b>todos os planos, capítulos, tópicos, registros de tópicos ministrados, aulas, tópicos personalizados e vínculos com turmas</b> relacionados a ela também serão <span class="text-danger fw-bold">excluídos permanentemente</span>.
                        </span>
                    </div>
                </div>
                <form id="formExcluirDisciplina" action="../controllers/excluir_disciplina_ajax.php" method="POST">
                    <input type="hidden" name="id" id="excluir_id_disciplina">
                    <input type="hidden" name="redirect" value="disciplinas.php">
                    <div style="margin:22px 0 30px 0;text-align:center;">
                        <span style="font-size:1.25em;font-weight:700;color:#dc3545;letter-spacing:1px;">
                            Tem certeza que deseja excluir a disciplina<br>
                            <span id="excluir_nome_disciplina" style="font-size:1.45em;font-weight:900;color:#b30000;text-shadow:0 1px 0 #fff,0 2px 8px #ffb3b3;">
                                <!-- nome da disciplina aqui -->
                            </span>
                            ?
                        </span>
                    </div>
                    <div class="d-flex justify-content-center gap-3 pt-2">
                        <button type="submit" class="btn btn-danger d-flex align-items-center gap-2 px-4 py-2" style="font-size:1.13em;font-weight:600;"><i
                                class="bi bi-trash"></i> Confirmar Exclusão</button>
                        <button type="button" class="btn btn-secondary d-flex align-items-center gap-2 px-4 py-2"
                            onclick="fecharModalExcluirDisciplina()" style="font-size:1.13em;font-weight:600;"><i class="bi bi-x-circle"></i> Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmar Cancelamento/Ativação -->
    <div id="modalCancelarAtivarDisciplina"
        style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);">
        <div
            style="background:#fff;padding:0;border-radius:22px;max-width:900px;width:98vw;margin:100px auto;position:relative;box-shadow:0 8px 32px rgba(0,0,0,0.18);overflow:hidden;">
            <div id="modalCancelarAtivarDisciplinaHeader"
                style="background:linear-gradient(90deg,#ffc107 60%,#ffb347 100%);padding:26px 40px 18px 40px;display:flex;align-items:center;gap:18px;">
                <i id="modalCancelarAtivarDisciplinaIcon" class="bi bi-exclamation-octagon-fill text-white" style="font-size:2.5rem;"></i>
                <h3 id="modalCancelarAtivarDisciplinaTitle" class="mb-0 text-white" style="font-weight:700;font-size:1.6rem;">Cancelar/Ativar Disciplina</h3>
                <span onclick="fecharModalCancelarAtivarDisciplina()"
                    style="position:absolute;top:18px;right:32px;font-size:36px;cursor:pointer;color:#fff;opacity:0.8;">&times;</span>
            </div>
            <div style="padding:38px 40px 24px 40px;">
                <div class="alert alert-warning d-flex flex-column align-items-center gap-3 mb-4 text-center" style="font-size:1.18em;">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size:2.1em;"></i>
                    <div>
                        <b class="fw-bold" style="font-size:1.13em;">Atenção!</b> <br>
                        Esta ação <span class="fw-bold text-danger">não poderá ser desfeita</span>.<br>
                        <span style="font-size:1.04em;">
                            Ao cancelar a disciplina, <b>todos os planos, capítulos e tópicos</b> relacionados a ela também terão o status <span class="text-warning fw-bold">cancelado</span>.<br>
                            As aulas e vínculos permanecem, mas não poderão ser utilizados normalmente.<br>
                            <br>
                            Ao ativar a disciplina, <b>todos os planos, capítulos e tópicos</b> relacionados a ela também poderão ser reativados.<br>
                            A disciplina e seus conteúdos voltarão a ficar disponíveis para uso normal.
                        </span>
                    </div>
                </div>
                <form id="formCancelarAtivarDisciplina" action="../controllers/cancelar_disciplina_ajax.php" method="POST">
                    <input type="hidden" name="id" id="cancelar_ativar_id_disciplina">
                    <input type="hidden" name="acao" id="cancelar_ativar_acao">
                    <div style="margin:22px 0 30px 0;text-align:center;">
                        <span style="font-size:1.25em;font-weight:700;letter-spacing:1px;">
                            Tem certeza que deseja <span id="acaoTexto" style="font-weight:900;"></span> a disciplina<br>
                            <span id="cancelar_ativar_nome_disciplina" style="font-size:1.45em;font-weight:900;color:#b36b00;text-shadow:0 1px 0 #fff,0 2px 8px #ffe0b2;">
                                <!-- nome da disciplina aqui -->
                            </span>
                            ?
                        </span>
                    </div>
                    <div class="d-flex justify-content-center gap-3 pt-2">
                        <button type="submit" id="btnConfirmarCancelarAtivarDisciplina"
                            class="btn btn-warning d-flex align-items-center gap-2 px-4 py-2"
                            style="font-size:1.13em;font-weight:600;color:#fff;">
                            <i class="bi bi-exclamation-octagon"></i> Confirmar
                        </button>
                        <button type="button" class="btn btn-secondary d-flex align-items-center gap-2 px-4 py-2"
                            onclick="fecharModalCancelarAtivarDisciplina()" style="font-size:1.13em;font-weight:600;"><i class="bi bi-x-circle"></i> Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
function atualizarCodigoDisciplina() {
    const nome = document.getElementById('nome_disciplina').value.trim();
    let codigo = '';
    if (nome.length >= 3) {
        // Pega as 3 primeiras letras, remove acentos, deixa maiúsculo
        codigo = nome.normalize('NFD').replace(/[\u0300-\u036f]/g, '').substr(0,3).toUpperCase();
        codigo += new Date().getFullYear();
    }
    document.getElementById('codigo_disciplina').value = codigo;
}

function abrirModalDisciplina() {
    document.getElementById('formDisciplina').action = '../controllers/criar_disciplina.php';
    document.getElementById('tituloModalDisciplina').innerText = 'Criar Disciplina';
    document.getElementById('id_disciplina').value = '';
    document.getElementById('nome_disciplina').value = '';
    document.getElementById('codigo_disciplina').value = '';
    document.getElementById('descricao_disciplina').value = '';
    document.getElementById('status_disciplina').value = 'ativa';
    document.getElementById('codigo_disciplina').readOnly = true;
    document.getElementById('modalDisciplina').style.display = 'block';
    document.getElementById('modalDisciplina').classList.add('modal-custom-aberta');
    document.body.classList.add('modal-aberta');
    // Fecha dropdowns abertos
    document.querySelectorAll('.dropdown-menu.show').forEach(function(el){el.classList.remove('show');});
}

function abrirModalEditarDisciplina(id, nome, codigo, descricao, status) {
    document.getElementById('formDisciplina').action = '../controllers/editar_disciplina.php';
    document.getElementById('tituloModalDisciplina').innerText = 'Editar Disciplina';
    document.getElementById('id_disciplina').value = id;
    document.getElementById('nome_disciplina').value = nome;
    document.getElementById('codigo_disciplina').value = codigo;
    document.getElementById('descricao_disciplina').value = descricao;
    document.getElementById('status_disciplina').value = 'ativa';
    document.getElementById('codigo_disciplina').readOnly = true;
    document.getElementById('modalDisciplina').style.display = 'block';
}

function fecharModalDisciplina() {
    document.getElementById('modalDisciplina').style.display = 'none';
    document.getElementById('modalDisciplina').classList.remove('modal-custom-aberta');
    document.body.classList.remove('modal-aberta');
}

function abrirModalExcluirDisciplina(id, nome) {
    document.getElementById('excluir_id_disciplina').value = id;
    document.getElementById('excluir_nome_disciplina').innerHTML = '<span>' + nome + '</span>';
    document.getElementById('modalExcluirDisciplina').style.display = 'block';
    document.getElementById('modalExcluirDisciplina').classList.add('modal-custom-aberta');
    document.body.classList.add('modal-aberta');
    document.querySelectorAll('.dropdown-menu.show').forEach(function(el){el.classList.remove('show');});
}

function fecharModalExcluirDisciplina() {
    document.getElementById('modalExcluirDisciplina').style.display = 'none';
    document.getElementById('modalExcluirDisciplina').classList.remove('modal-custom-aberta');
    document.body.classList.remove('modal-aberta');
}

function abrirModalToggleDisciplina(id, nome, status, btn) {
    document.getElementById('toggle_id_disciplina').value = id;
    document.getElementById('toggle_nome_disciplina').innerHTML = '<b>' + nome + '</b>';
    document.getElementById('toggle_status_disciplina').value = status === 'cancelada' ? 'ativa' : 'cancelada';
    document.getElementById('modalToggleDisciplina').style.display = 'block';
    document.getElementById('btnConfirmToggleDisciplina').onclick = function() {
        fecharModalToggleDisciplina();
        toggleStatusDisciplina(id, btn);
    };
}

function fecharModalToggleDisciplina() {
    document.getElementById('modalToggleDisciplina').style.display = 'none';
}

// Modal de Confirmar Cancelamento/Ativação
function abrirModalCancelarAtivarDisciplina(id, nome, statusAtual) {
    document.getElementById('cancelar_ativar_id_disciplina').value = id;
    document.getElementById('cancelar_ativar_nome_disciplina').innerHTML = '<span>' + nome + '</span>';
    let acao, acaoTexto, grad, icone, title, btnClass, btnIcon;
    if (statusAtual === 'cancelada') {
        acao = 'ativar';
        acaoTexto = '<span style="color:#198754;">ATIVAR</span>';
        grad = 'linear-gradient(90deg,#198754 60%,#43e97b 100%)';
        icone = 'bi bi-check-circle-fill text-white';
        title = 'Ativar Disciplina';
        btnClass = 'btn btn-success d-flex align-items-center gap-2 px-4 py-2';
        btnIcon = 'bi bi-check-circle';
    } else {
        acao = 'cancelar';
        acaoTexto = '<span style="color:#ff9800;">CANCELAR</span>';
        grad = 'linear-gradient(90deg,#ffc107 60%,#ffb347 100%)';
        icone = 'bi bi-exclamation-octagon-fill text-white';
        title = 'Cancelar Disciplina';
        btnClass = 'btn btn-warning d-flex align-items-center gap-2 px-4 py-2';
        btnIcon = 'bi bi-exclamation-octagon';
    }
    document.getElementById('cancelar_ativar_acao').value = acao;
    document.getElementById('acaoTexto').innerHTML = acaoTexto;
    let header = document.getElementById('modalCancelarAtivarDisciplinaHeader');
    header.style.background = grad;
    document.getElementById('modalCancelarAtivarDisciplinaIcon').className = icone;
    document.getElementById('modalCancelarAtivarDisciplinaTitle').innerText = title;
    let btn = document.getElementById('btnConfirmarCancelarAtivarDisciplina');
    btn.className = btnClass;
    btn.querySelector('i').className = btnIcon;
    btn.innerHTML = `<i class="${btnIcon}"></i> Confirmar`;
    document.getElementById('modalCancelarAtivarDisciplina').style.display = 'block';
    document.getElementById('modalCancelarAtivarDisciplina').classList.add('modal-custom-aberta');
    document.body.classList.add('modal-aberta');
    document.querySelectorAll('.dropdown-menu.show').forEach(function(el){el.classList.remove('show');});
}

function fecharModalCancelarAtivarDisciplina() {
    document.getElementById('modalCancelarAtivarDisciplina').style.display = 'none';
    document.getElementById('modalCancelarAtivarDisciplina').classList.remove('modal-custom-aberta');
    document.body.classList.remove('modal-aberta');
}

document.getElementById('formCancelarAtivarDisciplina').onsubmit = function(e) {
    e.preventDefault();
    var formData = new FormData(this);
    fetch(this.action, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            fecharModalCancelarAtivarDisciplina();
            sessionStorage.setItem('notificacao', JSON.stringify({msg: 'Status da disciplina atualizado!', tipo: 'success'}));
            location.reload();
        } else {
            mostrarNotificacao(res.error || 'Erro ao atualizar status da disciplina', 'danger');
        }
    })
    .catch(() => {
        mostrarNotificacao('Erro de conexão', 'danger');
    });
};
// AJAX para criar/editar disciplina
document.getElementById('formDisciplina').onsubmit = function(e) {
    e.preventDefault();
    const form = e.target;
    const dados = new FormData(form);

    // Corrige nomes dos campos para AJAX
    let data = {
        nome: document.getElementById('nome_disciplina').value,
        codigo: document.getElementById('codigo_disciplina').value,
        descricao: document.getElementById('descricao_disciplina').value
    };
    let url = form.action.includes('editar_disciplina') ? '../controllers/editar_disciplina_ajax.php' : '../controllers/criar_disciplina_ajax.php';
    if (form.action.includes('editar_disciplina')) {
        data.id = document.getElementById('id_disciplina').value;
    }

    fetch(url, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams(data)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            fecharModalDisciplina();
            sessionStorage.setItem('notificacao', JSON.stringify({msg: 'Disciplina salva com sucesso!', tipo: 'success'}));
            location.reload();
        } else {
            mostrarNotificacao(res.error || 'Erro ao salvar disciplina', 'danger');
        }
    })
    .catch(() => {
        mostrarNotificacao('Erro de conexão', 'danger');
    });
};
// AJAX para exclusão de disciplina
document.querySelector('#modalExcluirDisciplina form').onsubmit = function(e) {
    e.preventDefault();
    var formData = new FormData(this);
    fetch(this.action, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            fecharModalExcluirDisciplina();
            sessionStorage.setItem('notificacao', JSON.stringify({msg: 'Disciplina excluída com sucesso!', tipo: 'success'}));
            location.reload();
        } else {
            mostrarNotificacao(res.error || 'Erro ao excluir disciplina', 'danger');
        }
    })
    .catch(() => {
        mostrarNotificacao('Erro de conexão', 'danger');
    });
};
// Função para ativar/inativar disciplina
function toggleStatusDisciplina(id, btn) {
    btn.disabled = true;
    fetch('../controllers/toggle_status_disciplina.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'id_disciplina=' + encodeURIComponent(id)
        })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            if (res.success) {
                const badge = document.getElementById('disciplina-status-' + id);
                if (badge) {
                    badge.className = 'badge bg-' + (res.novo_status === 'ativa' ? 'success' : 'secondary') +
                        ' text-dark border border-' + (res.novo_status === 'ativa' ? 'success' : 'secondary') + ' turma-status-badge';
                    badge.innerHTML = '<i class="bi bi-activity"></i> ' + res.novo_status;
                }
                btn.className = 'btn p-0 m-0 border-0 bg-transparent turma-toggle-btn';
                btn.style.fontSize = '2.1rem';
                btn.style.lineHeight = '1';
                btn.style.boxShadow = 'none';
                btn.title = (res.novo_status === 'ativa' ? 'Inativar' : 'Ativar') + ' disciplina';
                btn.querySelector('i').className = 'bi ' + (res.novo_status === 'ativa' ? 'bi-toggle-off' : 'bi-toggle-on');
                const card = btn.closest('.card-turma');
                if (card) {
                    card.classList.remove('cancelada');
                    let overlay = card.querySelector('.status-overlay');
                    if (overlay) overlay.remove();
                    if (res.novo_status === 'inativa') {
                        card.classList.add('cancelada');
                        card.insertAdjacentHTML('afterbegin', '<div class="status-overlay">INATIVA</div>');
                    }
                }
                sessionStorage.setItem('notificacao', JSON.stringify({msg: 'Status da disciplina atualizado!', tipo: 'success'}));
                location.reload();
            } else {
                mostrarNotificacao(res.error || 'Erro ao atualizar status', 'danger');
            }
        })
        .catch(() => {
            btn.disabled = false;
            mostrarNotificacao('Erro de conexão', 'danger');
        });
}
// Altere o onclick dos botões toggle para chamar o modal de confirmação
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.turma-toggle-btn').forEach(function(btn) {
        btn.onclick = function(e) {
            e.preventDefault();
            let card = this.closest('.card-turma');
            let id = null;
            if (card && card.id && card.id.startsWith('disciplina-card-')) {
                id = card.id.replace('disciplina-card-', '');
            }
            if (id) {
                confirmarToggleStatusDisciplina(id, this);
            } else {
                alert('Não foi possível identificar a disciplina para ativar/inativar.');
            }
        };
    });
});
// ADICIONE ESTA FUNÇÃO NO <script> FINAL DO ARQUIVO (antes de </body>)
// Ela deve existir para os botões de toggle de status funcionarem corretamente
function confirmarToggleStatusDisciplina(id, btn) {
    // Busca o card da disciplina para pegar o status atual
    let card = document.getElementById('disciplina-card-' + id);
    let statusAtual = 'ativa';
    if (card && card.classList.contains('cancelada')) statusAtual = 'cancelada';
    else if (card && card.classList.contains('concluida')) statusAtual = 'concluída';

    // Pegue o nome da disciplina do card (ou passe como parâmetro no botão)
    let nome = '';
    let nomeSpan = card ? card.querySelector('.fw-bold') : null;
    if (nomeSpan) nome = nomeSpan.textContent.trim();

    // Chame o modal novo de cancelamento/ativação
    abrirModalCancelarAtivarDisciplina(id, nome, statusAtual);
}
    // Exibir notificação após reload, se existir
    window.addEventListener('DOMContentLoaded', function() {
        var notif = sessionStorage.getItem('notificacao');
        if (notif) {
            try {
                notif = JSON.parse(notif);
                if (notif.msg && notif.tipo) {
                    mostrarNotificacao(notif.msg, notif.tipo);
                }
            } catch (e) {}
            sessionStorage.removeItem('notificacao');
        }
    });
    </script>
    <?php include 'footer.php'; ?>
<script>
// Modal de Dicas de Funcionamento
<?php $isProfessor = (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'professor'); ?>
const stepsDicas = <?php if ($isProfessor): ?>[
    {
        title: 'Filtros e Pesquisa',
        html: `<div class="dica-step-card bg-dica-blue"><div class="dica-step-icone dica-blue"><i class="bi bi-funnel-fill"></i></div><div><span class="fw-bold text-dica-blue">Filtros:</span> Use o botão <span class="badge bg-primary text-white"><i class="bi bi-funnel-fill"></i></span> para abrir filtros avançados.<br><span class="fw-bold text-dica-blue">Pesquisa:</span> Digite no campo para buscar disciplinas pelo nome.<br><span class="fw-bold text-dica-blue">Ordenação:</span> Escolha como deseja ordenar as disciplinas.<br><span class="fw-bold text-dica-blue">Status:</span> Filtre por disciplinas ativas ou canceladas.</div></div>`
    },
    {
        title: 'Badges, Planos, Capítulos e Tópicos',
        html: `<div class="dica-step-card bg-dica-purple"><div class="dica-step-icone dica-purple"><i class="bi bi-collection"></i></div><div><span class="fw-bold text-dica-purple">Badges:</span> Mostram o total de turmas vinculadas, ativas, concluídas e canceladas.<br><span class="fw-bold text-dica-purple">Planos:</span> Cada disciplina pode ter planos de ensino vinculados às turmas.<br><span class="fw-bold text-dica-purple">Capítulos:</span> Os planos são divididos em capítulos.<br><span class="fw-bold text-dica-purple">Tópicos:</span> Cada capítulo possui tópicos que representam os conteúdos a serem ministrados.<br><span class="fw-bold text-dica-purple">Paginação:</span> Navegue entre as páginas usando os botões abaixo dos cards.</div></div>`
    }
]<?php else: ?>[
    {
        title: 'Filtros e Pesquisa',
        html: `<div class="dica-step-card bg-dica-blue"><div class="dica-step-icone dica-blue"><i class="bi bi-funnel-fill"></i></div><div><span class="fw-bold text-dica-blue">Filtros:</span> Use o botão <span class="badge bg-primary text-white"><i class="bi bi-funnel-fill"></i></span> para abrir filtros avançados.<br><span class="fw-bold text-dica-blue">Pesquisa:</span> Digite no campo para buscar disciplinas pelo nome.<br><span class="fw-bold text-dica-blue">Ordenação:</span> Escolha como deseja ordenar as disciplinas.<br><span class="fw-bold text-dica-blue">Status:</span> Filtre por disciplinas ativas ou canceladas.</div></div>`
    },
    {
        title: 'Criar Nova Disciplina',
        html: `<div class="dica-step-card bg-dica-green"><div class="dica-step-icone dica-green"><i class="bi bi-plus-circle"></i></div><div>Clique em <span class="badge bg-success text-white"><i class="bi bi-plus-circle"></i> Nova Disciplina</span> para adicionar uma nova disciplina.<br>Preencha os campos obrigatórios e clique em <span class="badge bg-primary text-white"><i class="bi bi-check-circle"></i> Salvar</span>.</div></div>`
    },
    {
        title: 'Editar Disciplina',
        html: `<div class="dica-step-card bg-dica-yellow"><div class="dica-step-icone dica-yellow"><i class="bi bi-pencil-square"></i></div><div>Clique no botão <span class="badge bg-primary text-white"><i class="bi bi-pencil-square"></i></span> em um card para editar os dados da disciplina.<br>Altere as informações desejadas e clique em <span class="badge bg-primary text-white"><i class="bi bi-check-circle"></i> Salvar</span>.</div></div>`
    },
    {
        title: 'Excluir Disciplina',
        html: `<div class="dica-step-card bg-dica-red"><div class="dica-step-icone dica-red"><i class="bi bi-trash"></i></div><div>Clique no botão <span class="badge bg-danger text-white"><i class="bi bi-trash"></i></span> para excluir uma disciplina.<br><span class="fw-bold text-danger">Atenção:</span> esta ação é <u>irreversível</u>!</div></div>`
    },
    {
        title: 'Ativar/Cancelar Disciplina',
        html: `<div class="dica-step-card bg-dica-orange"><div class="dica-step-icone dica-orange"><i class="bi bi-toggle-on"></i></div><div>Use o botão <span class="badge bg-warning text-dark"><i class="bi bi-toggle-off"></i> / <i class="bi bi-toggle-on"></i></span> para ativar ou cancelar uma disciplina.<br>Confirme a ação no modal exibido.</div></div>`
    },
    {
        title: 'Badges, Planos, Capítulos e Tópicos',
        html: `<div class="dica-step-card bg-dica-purple"><div class="dica-step-icone dica-purple"><i class="bi bi-collection"></i></div><div><span class="fw-bold text-dica-purple">Badges:</span> Mostram o total de turmas vinculadas, ativas, concluídas e canceladas.<br><span class="fw-bold text-dica-purple">Planos:</span> Cada disciplina pode ter planos de ensino vinculados às turmas.<br><span class="fw-bold text-dica-purple">Capítulos:</span> Os planos são divididos em capítulos.<br><span class="fw-bold text-dica-purple">Tópicos:</span> Cada capítulo possui tópicos que representam os conteúdos a serem ministrados.<br><span class="fw-bold text-dica-purple">Paginação:</span> Navegue entre as páginas usando os botões abaixo dos cards.</div></div>`
    }
]<?php endif; ?>;
let stepAtual = 0;
function mostrarStepDicas(idx) {
    stepAtual = idx;
    // Atualiza stepper
    // Ícones para cada step (ordem deve bater com stepsDicas)
    let icones = <?php if ($isProfessor): ?>[
        'bi-funnel-fill',
        'bi-collection'
    ]<?php else: ?>[
        'bi-funnel-fill',
        'bi-plus-circle',
        'bi-pencil-square',
        'bi-trash',
        'bi-toggle-on',
        'bi-collection'
    ]<?php endif; ?>;
    for (let i = 0; i < 6; i++) {
        let el = document.getElementById('stepCircle'+(i+1));
        if (el) {
            el.style.display = i < stepsDicas.length ? '' : 'none';
            el.classList.toggle('active', i === idx);
            let icon = el.querySelector('i');
            if (icon) {
                icon.className = 'bi ' + (icones[i] || '');
                icon.style.opacity = i < stepsDicas.length ? 1 : 0;
                icon.style.fontSize = '1.25em';
            }
        }
        let line = el && el.nextElementSibling && el.nextElementSibling.classList.contains('step-line') ? el.nextElementSibling : null;
        if (line) line.style.display = (i < stepsDicas.length-1) ? '' : 'none';
    }
    // Atualiza conteúdo
    document.getElementById('stepContentDicas').innerHTML = `
        <h5 class='fw-bold mb-3 text-primary'>${stepsDicas[idx].title}</h5>
        <div style='font-size:1.13em;'>${stepsDicas[idx].html}</div>
    `;
    // Botões
    document.getElementById('btnStepAnterior').style.display = idx === 0 ? 'none' : '';
    document.getElementById('btnStepProximo').innerHTML = idx === stepsDicas.length-1 ? 'Fechar <i class="bi bi-x"></i>' : 'Próximo <i class="bi bi-arrow-right"></i>';
}
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('btnDicasDisciplinas').onclick = function(e) {
        if (e) e.stopPropagation();
        document.getElementById('modalDicasDisciplinas').style.display = 'block';
        mostrarStepDicas(0);
    };
    document.getElementById('btnStepAnterior').onclick = function() {
        if (stepAtual > 0) mostrarStepDicas(stepAtual-1);
    };
    document.getElementById('btnStepProximo').onclick = function() {
        if (stepAtual < stepsDicas.length-1) mostrarStepDicas(stepAtual+1);
        else fecharModalDicasDisciplinas();
    };
});
function fecharModalDicasDisciplinas() {
    document.getElementById('modalDicasDisciplinas').style.display = 'none';
}
</script>
</body>
</html>