<?php
session_start();
if (!isset($_SESSION['usuario_nome']) || !in_array($_SESSION['usuario_nome'], ['coordenador', 'admin', 'professor'])) {
    header('Location: index.php');
    exit();
}
include 'navbar.php';
include 'notificacao.php';
require_once '../config/conexao.php';

// --- NOVO: Pesquisa e Paginação ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 6;
$where = '';
$params = [];
$types = '';

if ($search !== '') {
    $where = "WHERE nome LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}

// --- ORDEM SQL ---
// Corrigido: respeita corretamente o dropdown para todos, inclusive professor
$ordem_sql = "ORDER BY ano_letivo DESC, nome";
if (isset($_GET['ordem']) && $_GET['ordem'] !== '') {
    if ($_GET['ordem'] == 'recentes') $ordem_sql = "ORDER BY id DESC";
    elseif ($_GET['ordem'] == 'antigas') $ordem_sql = "ORDER BY id ASC";
    elseif ($_GET['ordem'] == 'az') $ordem_sql = "ORDER BY nome ASC";
    elseif ($_GET['ordem'] == 'za') $ordem_sql = "ORDER BY nome DESC";
} elseif (
    (!isset($_GET['ordem']) || $_GET['ordem'] === '') &&
    isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'professor'
) {
    // Só usa "ativas primeiro" se não houver ordenação escolhida no dropdown
    $ordem_sql = "ORDER BY (status = 'ativa') DESC, ano_letivo DESC, nome";
}

// --- FILTRO DE STATUS ---
if (isset($_GET['status_filtro']) && in_array($_GET['status_filtro'], ['ativa','concluída','cancelada'])) {
    $where .= ($where ? " AND " : "WHERE ") . "status = ?";
    $params[] = $_GET['status_filtro'];
    $types .= 's';
}

// Contar total de turmas para paginação
$sql_count = "SELECT COUNT(*) as total FROM turmas " . ($where ? $where : '');
$stmt_count = $conn->prepare($sql_count);
if ($where) $stmt_count->bind_param($types, ...$params);
$stmt_count->execute();
$res_count = $stmt_count->get_result();
$total_turmas = $res_count->fetch_assoc()['total'];
$stmt_count->close();

$total_pages = ceil($total_turmas / $per_page);
$offset = ($page - 1) * $per_page;

// --- QUANTIDADE POR PÁGINA ---
$per_page_options = [3, 6, 12, 24, 48];
if (isset($_GET['per_page']) && in_array(intval($_GET['per_page']), $per_page_options)) {
    $per_page = intval($_GET['per_page']);
    $total_pages = ceil($total_turmas / $per_page);
    $offset = ($page - 1) * $per_page;
}

// Buscar turmas com filtro e paginação (agora usando $ordem_sql)
$sql = "SELECT * FROM turmas " . ($where ? $where : '') . " $ordem_sql LIMIT $per_page OFFSET $offset";
$stmt = $conn->prepare($sql);
if ($where) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$turmas = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $turmas[] = $row;
    }
}
$stmt->close();

// Buscar todas as disciplinas
$disciplinas = [];
$sql = 'SELECT * FROM disciplinas ORDER BY nome';
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $disciplinas[] = $row;
    }
}
// Buscar disciplinas vinculadas para cada turma (para edição)
$turmaDisciplinas = [];
$sql = 'SELECT turma_id, disciplina_id FROM turma_disciplinas';
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $turmaDisciplinas[$row['turma_id']][] = $row['disciplina_id'];
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
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Turmas - PI Page</title>
    <link rel="icon" type="image/png" href="../assets/img/LOGO_PAGE.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/turmas.css" rel="stylesheet">
</head>

<body>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Exibir notificação se houver parâmetro na URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('sucesso')) {
        let msg = '';
        if (urlParams.get('sucesso') === 'turma_criada') msg = 'Turma criada com sucesso!';
        if (urlParams.get('sucesso') === 'turma_editada') msg = 'Turma editada com sucesso!';
        if (urlParams.get('sucesso') === 'turma_excluida') msg = 'Turma excluída com sucesso!';
        if (msg) mostrarNotificacao(msg, 'success');
    }
    if (urlParams.has('erro')) {
        let msg = '';
        if (urlParams.get('erro') === 'erro_banco') msg = 'Erro ao salvar no banco!';
        if (urlParams.get('erro') === 'dados_invalidos') msg = 'Dados inválidos!';
        if (urlParams.get('erro') === 'turma_vinculada') msg =
            'Não é possível excluir turma com disciplinas vinculadas!';
        if (msg) mostrarNotificacao(msg, 'danger');
    }
    </script>
    <div class="container-fluid py-4">

    <div class="row">
        <div class="col-12">
            <div class="bg-white rounded shadow-sm p-4 mb-3 border border-3 border-primary position-relative">
                <div class="row align-items-end g-2 mb-2">
                    <div class="col-lg-6 col-md-6 col-12">
                        <div class="d-flex align-items-center gap-3 h-100">
                            <span
                                class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                                style="width:56px;height:56px;font-size:2.2rem;box-shadow:0 2px 8px #0d6efd33;">
                                <i class="bi bi-people-fill"></i>
                            </span>
                            <div>
                                <h2 class="mb-0 fw-bold text-primary">Turmas</h2>
                                <div class="text-muted" style="font-size:1.08em;">
                                    <i class="bi bi-info-circle"></i>
                                    <?php if (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'professor'): ?>
                                        Registre as aulas ministradas em suas turmas.
                                    <?php else: ?>
                                        Gerencie e visualize todas as turmas cadastradas no sistema.
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
                                    placeholder="Pesquisar turma..." value="<?= htmlspecialchars($search) ?>"
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
                                <button type="button" class="btn btn-gradient-dicas shadow-sm px-3 py-2 d-flex align-items-center gap-2 fw-bold" id="btnDicasTurmas" title="Dicas da página" style="border-radius: 14px; font-size:1.13em; box-shadow: 0 2px 8px #0d6efd33;">
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
                                    $per_page_options = [3, 6, 12, 24, 48];
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
                                    $ordem = isset($_GET['ordem']) ? $_GET['ordem'] : 'recentes';
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
                                        'concluída' => ['icon' => 'bi-check2-square text-primary', 'label' => 'Concluídas'],
                                        'cancelada' => ['icon' => 'bi-x-circle-fill text-danger', 'label' => 'Canceladas']
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
                    <?php if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'professor'): ?>
                    <div class="col-lg-2 col-md-12 col-12 d-flex justify-content-lg-end justify-content-start mt-2 mt-lg-0">
                        <button class="btn btn-success d-flex align-items-center gap-2 shadow-sm px-3 py-2"
                            style="font-size:1em;" onclick="abrirModalTurma()">
                            <i class="bi bi-plus-circle"></i> Nova Turma
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="row g-2 mt-2 mb-1">
                    <?php
                    // Contagem de status
                    $ativas = 0; $concluidas = 0; $canceladas = 0;
                    foreach ($turmas as $t) {
                        if ($t['status'] === 'ativa') $ativas++;
                        elseif ($t['status'] === 'concluída') $concluidas++;
                        elseif ($t['status'] === 'cancelada') $canceladas++;
                    }
                ?>
                    <div class="col-auto">
                        <span
                            class="badge bg-primary-subtle text-primary border border-primary d-flex align-items-center gap-1"
                            style="font-size:1.08em;">
                            <i class="bi bi-collection"></i> Total: <b><?= $total_turmas ?></b>
                        </span>
                    </div>
                    <div class="col-auto">
                        <span
                            class="badge bg-success-subtle text-success border border-success d-flex align-items-center gap-1"
                            style="font-size:1.08em;">
                            <i class="bi bi-check-circle-fill"></i> Ativas: <b><?= $ativas ?></b>
                        </span>
                    </div>
                    <div class="col-auto">
                        <span
                            class="badge bg-info-subtle text-info border border-info d-flex align-items-center gap-1"
                            style="font-size:1.08em;">
                            <i class="bi bi-flag"></i> Concluídas: <b><?= $concluidas ?></b>
                        </span>
                    </div>
                    <div class="col-auto">
                        <span
                            class="badge bg-secondary-subtle text-secondary border border-secondary d-flex align-items-center gap-1"
                            style="font-size:1.08em;">
                            <i class="bi bi-x-circle-fill"></i> Canceladas: <b><?= $canceladas ?></b>
                        </span>
                    </div>
                </div>
               
            </div>

        </div>
    </div>

    <script>
    // Mantém seleção ao redirecionar (Bootstrap já faz isso, mas garantimos)
    document.getElementById('filtrosForm').onsubmit = function(e) {
        // Não faz nada especial, apenas deixa o submit padrão acontecer
    };
    </script>
    <?php
    // --- ORDEM SQL ---
    $ordem_sql = "ORDER BY ano_letivo DESC, nome";
    if (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'professor') {
        // Professores: turmas ativas primeiro
        $ordem_sql = "ORDER BY (status = 'ativa') DESC, ano_letivo DESC, nome";
    } elseif (isset($_GET['ordem'])) {
        if ($_GET['ordem'] == 'recentes') $ordem_sql = "ORDER BY id DESC";
        elseif ($_GET['ordem'] == 'antigas') $ordem_sql = "ORDER BY id ASC";
        elseif ($_GET['ordem'] == 'az') $ordem_sql = "ORDER BY nome ASC";
        elseif ($_GET['ordem'] == 'za') $ordem_sql = "ORDER BY nome DESC";
    }
    // --- FILTRO DE STATUS ---
    if (isset($_GET['status_filtro']) && in_array($_GET['status_filtro'], ['ativa','concluída','cancelada'])) {
        $where .= ($where ? " AND " : "WHERE ") . "status = ?";
        $params[] = $_GET['status_filtro'];
        $types .= 's';
    }
    // --- QUANTIDADE POR PÁGINA ---
    if (isset($_GET['per_page']) && in_array(intval($_GET['per_page']), $per_page_options)) {
        $per_page = intval($_GET['per_page']);
        $total_pages = ceil($total_turmas / $per_page);
        $offset = ($page - 1) * $per_page;
    }
    // Buscar turmas com filtro e paginação
    $sql = "SELECT * FROM turmas " . ($where ? $where : '') . " $ordem_sql LIMIT $per_page OFFSET $offset";
    $stmt = $conn->prepare($sql);
    if ($where) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $turmas = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $turmas[] = $row;
        }
    }
    $stmt->close();
    ?>
    <div class="row">
        <div class="col-12">
            <div class="row g-4">
                <?php if (empty($turmas)): ?>
                <div class="col-12">
                    <div class="alert alert-warning text-center">Nenhuma turma encontrada.</div>
                </div>
                <?php endif; ?>
                <?php foreach ($turmas as $turma): ?>
                <?php
    $cardClass = 'card card-turma h-100';
    $statusOverlay = '';
    if ($turma['status'] === 'cancelada') {
        $cardClass .= ' cancelada';
        $statusOverlay = '<div class="status-overlay">CANCELADA</div>';
    } else if ($turma['status'] === 'concluída') {
        $cardClass .= ' concluida';
        $statusOverlay = '<div class="status-overlay"><i class="bi bi-check-circle-fill"></i> Concluída</div>';
    }
    $isProfessor = (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'professor');
?>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="<?= $cardClass ?>" id="turma-card-<?= $turma['id'] ?>">
                        <?= $statusOverlay ?>
                        <div class="card-body d-flex flex-column" style="position:relative;">
                            <!-- Cabeçalho: Título + Ações -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <span class="fw-bold fs-5">
                                        <i class="bi bi-mortarboard-fill text-primary"></i>
                                        <?= htmlspecialchars($turma['nome']) ?>
                                    </span>
                                </div>
                                <div class="turma-actions">
                                    <?php if ($isProfessor): ?>
                                    <?php if ($turma['status'] === 'ativa' || $turma['status'] === 'concluída'): ?>
                                    <a href="registro_aulas.php?turma_id=<?= $turma['id'] ?>"
                                    class="btn btn-outline-primary btn-sm" title="Registrar Aula">
                                    <i class="bi bi-journal-plus"></i> Registrar Aula
                                    </a>
                                    <?php endif; ?>
                                    <?php else: ?>
                                    <a href="planos.php?turma_id=<?= $turma['id'] ?>" class="btn btn-secondary btn-sm"
                                        title="Gerenciar Planos">
                                        <i class="bi bi-journal-text"></i>Gerenciar Planos
                                    </a>
                                    <button class="btn btn-primary btn-sm" title="Editar"
                                        onclick="abrirModalEditarTurma(<?= $turma['id'] ?>, '<?= htmlspecialchars(addslashes($turma['nome'])) ?>', '<?= $turma['ano_letivo'] ?>', '<?= $turma['turno'] ?>', '<?= $turma['inicio'] ?>', '<?= $turma['fim'] ?>', '<?= $turma['status'] ?>')">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" title="Excluir"
                                        onclick="abrirModalExcluirTurma(<?= $turma['id'] ?>, '<?= htmlspecialchars(addslashes($turma['nome'])) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <button class="btn p-0 m-0 border-0 bg-transparent turma-toggle-btn"
                                        style="font-size:2.1rem; line-height:1; box-shadow:none;"
                                        title="<?= $turma['status']=='ativa'?'Cancelar':'Ativar' ?> turma"
                                        onclick="toggleStatusTurma(<?= $turma['id'] ?>, this)">
                                        <i
                                            class="bi <?= $turma['status']=='ativa'?'bi-toggle-off':'bi-toggle-on' ?>"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <hr class="my-2">
                            <!-- Dados principais -->
                            <div class="mb-2">
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="badge bg-info-subtle text-dark border border-info"><i
                                            class="bi bi-calendar-event"></i> Ano:
                                        <?= htmlspecialchars($turma['ano_letivo']) ?></span>
                                    <span class="badge bg-warning-subtle text-dark border border-warning"><i
                                            class="bi bi-clock"></i> Turno:
                                        <?= htmlspecialchars($turma['turno']) ?></span>
                                    <span class="badge bg-light text-dark border border-secondary"><i
                                            class="bi bi-calendar2-week"></i> Início:
                                        <?= $turma['inicio'] ? date('d/m/Y', strtotime($turma['inicio'])) : '-' ?></span>
                                    <span class="badge bg-light text-dark border border-secondary"><i
                                            class="bi bi-calendar2-week"></i> Fim:
                                        <?= $turma['fim'] ? date('d/m/Y', strtotime($turma['fim'])) : '-' ?></span>
                                    <span
                                        class="badge 
                    <?php
                        if ($turma['status']=='ativa') echo 'bg-success';
                        else if ($turma['status']=='cancelada') echo 'bg-secondary';
                        else if ($turma['status']=='concluída') echo 'bg-success';
                    ?>
                    text-dark border border-<?= $turma['status']=='ativa'?'success':($turma['status']=='cancelada'?'secondary':'success') ?> turma-status-badge"
                                        id="turma-status-<?= $turma['id'] ?>">
                                        <i class="bi bi-activity"></i> <?= htmlspecialchars($turma['status']) ?>
                                    </span>
                                </div>
                            </div>
                            <hr class="my-2">
                            <!-- Disciplinas e planos -->
                            <div class="mb-2 turma-disc-list">
                                <b><i class="bi bi-book"></i> Disciplinas:</b>
                                <ul class="list-unstyled ms-2 mb-0">
                                    <?php
                    $ids = isset($turmaDisciplinas[$turma['id']]) ? $turmaDisciplinas[$turma['id']] : [];
                    $temDisc = false;
                    foreach ($disciplinas as $disc) {
                        if (in_array($disc['id'], $ids)) {
                            $temDisc = true;
                            $temPlano = isset($planosPorTurmaDisciplina[$turma['id']][$disc['id']]);
                            // Novo bloco estilizado para status do plano
                            echo '<li class="disciplina-status d-flex align-items-center gap-2" style="background:#f8f9fa; border-radius:8px; padding:7px 10px; margin-bottom:6px;">';
                            echo '<i class="bi bi-dot"></i> <span class="fw-semibold">' . htmlspecialchars($disc['nome']) . '</span>';
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
                                    echo " <span class='ms-3'><i class='bi bi-journal-bookmark-fill text-primary'></i> <b>$capCount</b> capítulo(s)</span>";
                                    echo " <span class='ms-2'><i class='bi bi-list-task text-info'></i> <b>$topCount</b> tópico(s)</span>";
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
    <div id="modalDicasTurmas" style="display:none;position:fixed;z-index:2100;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.45);">
        <div class="modal-dicas-content">
            <div class="modal-dicas-header">
                <div class="modal-dicas-icone">
                    <i class="bi bi-lightbulb-fill"></i>
                </div>
                <h4 class="mb-0 text-white">Dicas de Funcionamento</h4>
                <span onclick="fecharModalDicasTurmas()" class="modal-dicas-close">&times;</span>
            </div>
            <div class="modal-dicas-body">
                <!-- Stepper -->
                <div id="stepperDicasTurmas" class="mb-4">
                    <div class="d-flex justify-content-center align-items-center gap-2 mb-3">
                        <span class="step-circle" id="stepCircleTurmas1"><i class="bi"></i></span>
                        <span class="step-line"></span>
                        <span class="step-circle" id="stepCircleTurmas2"><i class="bi"></i></span>
                        <span class="step-line"></span>
                        <span class="step-circle" id="stepCircleTurmas3"><i class="bi"></i></span>
                        <span class="step-line"></span>
                        <span class="step-circle" id="stepCircleTurmas4"><i class="bi"></i></span>
                        <span class="step-line"></span>
                        <span class="step-circle" id="stepCircleTurmas5"><i class="bi"></i></span>
                        <span class="step-line"></span>
                        <span class="step-circle" id="stepCircleTurmas6"><i class="bi"></i></span>
                    </div>
                </div>
                <div id="stepContentDicasTurmas">
                    <!-- Conteúdo dos steps será preenchido via JS -->
                </div>
            </div>
            <div class="modal-dicas-footer">
                <button class="btn btn-outline-primary" id="btnStepAnteriorTurmas" style="display:none;"><i class="bi bi-arrow-left"></i> Anterior</button>
                <button class="btn btn-outline-primary ms-3" id="btnStepProximoTurmas">Próximo <i class="bi bi-arrow-right"></i></button>
            </div>
        </div>
        <style>
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
        /* Esconde dropdown e botão de dicas quando modal customizado está aberto */
        body.modal-aberta .dropdown,
        body.modal-aberta .btn-gradient-dicas {
            z-index: 1 !important;
            pointer-events: none !important;
            opacity: 1 !important;
        }
        .modal-dicas-content {
            background: #fff;
            border-radius: 22px;
            max-width: 760px;
            width: 98vw;
            min-width: 420px;
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
        #modalDicasTurmas .step-circle {
            width: 32px; height: 32px; border-radius: 50%; background: #e3e9f7; color: #0d6efd; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.15em; border: 2px solid #b6c6e6;
            transition: background 0.2s, color 0.2s;
        }
        #modalDicasTurmas .step-circle.active {
            background: #0d6efd; color: #fff; border-color: #0d6efd;
        }
        #modalDicasTurmas .step-line {
            flex: 1 1 0; height: 3px; background: #b6c6e6;
        }
        #stepContentDicasTurmas {
            min-height: 110px;
            max-height: 180px;
            margin-bottom: 0.5em;
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
        </style>
    </div>
    <!-- Modal de Criar/Editar Turma -->
    <div id="modalTurma"
        style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);">
        <div
            style="background:#fff;padding:0;border-radius:18px;max-width:700px;width:95vw;margin:60px auto;position:relative;box-shadow:0 8px 32px rgba(0,0,0,0.18);overflow:hidden;">
            <div
                style="background:linear-gradient(90deg,#0d6efd 60%,#4f8cff 100%);padding:18px 32px 14px 32px;display:flex;align-items:center;gap:12px;">
                <i class="bi bi-mortarboard-fill text-white" style="font-size:2rem;"></i>
                <h4 id="tituloModalTurma" class="mb-0 text-white">Criar Turma</h4>
                <span onclick="fecharModalTurma()"
                    style="position:absolute;top:14px;right:22px;font-size:28px;cursor:pointer;color:#fff;opacity:0.8;">&times;</span>
            </div>
            <div style="padding:30px 32px 18px 32px;">
                <form id="formTurma" action="../controllers/criar_turma.php" method="POST">
                    <input type="hidden" name="id_turma" id="id_turma">
                    <div class="input-group mb-2">
                        <span class="input-group-text bg-white"><i class="bi bi-mortarboard"></i></span>
                        <input type="text" name="nome" id="nome_turma" placeholder="Nome da turma" required
                            class="form-control">
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6 mb-2 mb-md-0">
                            <label>Início:</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-calendar-event"></i></span>
                                <input type="date" name="inicio" id="inicio_turma" class="form-control"
                                    onchange="atualizarAnoLetivo()">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label>Fim:</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-calendar2-week"></i></span>
                                <input type="date" name="fim" id="fim_turma" class="form-control"
                                    onchange="atualizarAnoLetivo()">
                            </div>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6 mb-2 mb-md-0">
                            <label>Ano letivo:</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-calendar-range"></i></span>
                                <input type="number" name="ano_letivo" id="ano_letivo_turma" placeholder="Ano letivo"
                                    required class="form-control" min="2000" max="2100">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label>Turno:</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-clock"></i></span>
                                <select name="turno" id="turno_turma" class="form-select">
                                    <option value="manha">Manhã</option>
                                    <option value="tarde">Tarde</option>
                                    <option value="noite">Noite</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-2" id="statusRowTurma" style="display:none;">
                        <div class="col-md-6 mb-2 mb-md-0">
                            <label>Status:</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-activity"></i></span>
                                <input type="text" id="status_turma_view" class="form-control" value="Ativa" disabled>
                            </div>
                        </div>
                    </div>
                    <label class="mt-2"><b>Disciplinas da turma:</b></label>
                    <div class="input-group mb-2">
                        <span class="input-group-text bg-white"><i class="bi bi-book"></i></span>
                        <select name="disciplinas[]" id="disciplinas_turma" class="form-select" multiple required>
                            <option></option>
                            <?php foreach ($disciplinas as $disc): ?>
                            <option value="<?= $disc['id'] ?>"><?= htmlspecialchars($disc['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" name="redirect" value="turmas.php">
                </form>
                <div class="d-flex justify-content-end gap-2 pt-3 border-top mt-4" style="background:transparent;">
                    <button type="button" class="btn btn-secondary d-flex align-items-center gap-1"
                        onclick="fecharModalTurma()"><i class="bi bi-x-circle"></i> Cancelar</button>
                    <button type="submit" class="btn btn-primary d-flex align-items-center gap-1" id="btnSalvarTurma"
                        form="formTurma"><i class="bi bi-check-circle"></i> Salvar</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal de Confirmar Exclusão -->
    <div id="modalExcluirTurma"
        style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);">
        <div
            style="background:#fff;padding:0;border-radius:14px;max-width:400px;margin:100px auto;position:relative;box-shadow:0 8px 32px rgba(0,0,0,0.18);overflow:hidden;">
            <div
                style="background:linear-gradient(90deg,#dc3545 60%,#ff6f91 100%);padding:16px 24px 12px 24px;display:flex;align-items:center;gap:10px;">
                <i class="bi bi-trash-fill text-white" style="font-size:1.7rem;"></i>
                <h4 class="mb-0 text-white">Excluir Turma</h4>
                <span onclick="fecharModalExcluirTurma()"
                    style="position:absolute;top:10px;right:18px;font-size:26px;cursor:pointer;color:#fff;opacity:0.8;">&times;</span>
            </div>
            <div style="padding:24px 24px 18px 24px;">
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" style="font-size:1.1em;">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size:1.5em;"></i>
                    Atenção! Esta ação não poderá ser desfeita.
                </div>
                <form action="../controllers/excluir_turma.php" method="POST">
                    <input type="hidden" name="id_turma" id="excluir_id_turma">
                    <input type="hidden" name="redirect" value="turmas.php">
                    <p id="excluir_nome_turma" style="margin:15px 0;"></p>
                    <div class="d-flex justify-content-end gap-2 pt-2">
                        <button type="submit" class="btn btn-danger d-flex align-items-center gap-1"><i
                                class="bi bi-trash"></i> Confirmar Exclusão</button>
                        <button type="button" class="btn btn-secondary d-flex align-items-center gap-1"
                            onclick="fecharModalExcluirTurma()"><i class="bi bi-x-circle"></i> Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function atualizarAnoLetivo() {
        var inicio = document.getElementById('inicio_turma').value;
        var fim = document.getElementById('fim_turma').value;
        var ano = '';
        if (inicio) {
            ano = inicio.substring(0, 4);
        } else if (fim) {
            ano = fim.substring(0, 4);
        }
        document.getElementById('ano_letivo_turma').value = ano;
    }

    function abrirModalTurma() {
        document.getElementById('formTurma').action = '../controllers/criar_turma.php';
        document.getElementById('tituloModalTurma').innerText = 'Criar Turma';
        document.getElementById('id_turma').value = '';
        document.getElementById('nome_turma').value = '';
        document.getElementById('ano_letivo_turma').value = '';
        document.getElementById('turno_turma').value = 'manha';
        document.getElementById('inicio_turma').value = '';
        document.getElementById('fim_turma').value = '';
        // Esconde o campo de status visual
        document.getElementById('statusRowTurma').style.display = 'none';
        let sel = document.getElementById('disciplinas_turma');
        for (let i = 0; i < sel.options.length; i++) sel.options[i].selected = false;
        // Destroy and re-initialize Select2
        if ($('#disciplinas_turma').hasClass('select2-hidden-accessible')) {
            $('#disciplinas_turma').select2('destroy');
        }
        $('#disciplinas_turma').select2({
            width: '100%',
            placeholder: 'Selecione as disciplinas',
            language: 'pt-BR',
            tags: true,
            tokenSeparators: [',', ' ']
        });
        document.getElementById('modalTurma').style.display = 'block';
        document.body.classList.add('modal-aberta');
    }

    function abrirModalEditarTurma(id, nome, ano, turno, inicio, fim, status) {
        document.getElementById('formTurma').action = '../controllers/editar_turma.php';
        document.getElementById('tituloModalTurma').innerText = 'Editar Turma';
        document.getElementById('id_turma').value = id;
        document.getElementById('nome_turma').value = nome;
        document.getElementById('ano_letivo_turma').value = ano;
        document.getElementById('turno_turma').value = turno;
        document.getElementById('inicio_turma').value = inicio || '';
        document.getElementById('fim_turma').value = fim || '';
        // Mostra o campo de status visual
        document.getElementById('statusRowTurma').style.display = '';
        document.getElementById('status_turma_view').value = status || 'Ativa';
        atualizarAnoLetivo();
        let sel = document.getElementById('disciplinas_turma');
        for (let i = 0; i < sel.options.length; i++) sel.options[i].selected = false;
        let turmaDiscs = {};
        <?php foreach ($turmaDisciplinas as $tid => $dids): ?>
        turmaDiscs[<?= $tid ?>] = [<?= implode(',', $dids) ?>];
        <?php endforeach; ?>
        if (turmaDiscs[id]) {
            for (let i = 0; i < sel.options.length; i++) {
                if (turmaDiscs[id].includes(parseInt(sel.options[i].value))) sel.options[i].selected = true;
            }
        }
        // Destroy and re-initialize Select2
        if ($('#disciplinas_turma').hasClass('select2-hidden-accessible')) {
            $('#disciplinas_turma').select2('destroy');
        }
        $('#disciplinas_turma').select2({
            width: '100%',
            placeholder: 'Selecione as disciplinas',
            language: 'pt-BR',
            tags: true,
            tokenSeparators: [',', ' ']
        });
        document.getElementById('modalTurma').style.display = 'block';
        document.body.classList.add('modal-aberta');
    }

    function fecharModalTurma() {
        document.getElementById('modalTurma').style.display = 'none';
        document.body.classList.remove('modal-aberta');
    }

    function abrirModalExcluirTurma(id, nome) {
        document.getElementById('excluir_id_turma').value = id;
        document.getElementById('excluir_nome_turma').innerHTML = 'Tem certeza que deseja excluir a turma <b>' + nome +
            '</b>?';
        document.getElementById('modalExcluirTurma').style.display = 'block';
        document.body.classList.add('modal-aberta');
    }

    function fecharModalExcluirTurma() {
        document.getElementById('modalExcluirTurma').style.display = 'none';
        document.body.classList.remove('modal-aberta');
    }
    window.onclick = function(event) {
        var modalCriar = document.getElementById('modalTurma');
        var modalExc = document.getElementById('modalExcluirTurma');
        if (event.target == modalCriar) fecharModalTurma();
        if (event.target == modalExc) fecharModalExcluirTurma();
    }
    // Função para renderizar uma turma no DOM
    function renderTurmaCard(turma) {
        // Gera o mesmo HTML dos cards do PHP, incluindo status, badges, disciplinas e planos
        let statusOverlay = '';
        let cardClass = 'card card-turma h-100';
        if (turma.status === 'cancelada') {
            cardClass += ' cancelada';
            statusOverlay = '<div class="status-overlay">CANCELADA</div>';
        } else if (turma.status === 'concluída') {
            cardClass += ' concluida';
            statusOverlay = '<div class="status-overlay"><i class="bi bi-check-circle-fill"></i> Concluída</div>';
        }
        // Disciplinas e planos (AJAX não retorna detalhes dos planos/capítulos/tópicos)
        // Renderiza apenas nomes das disciplinas, igual ao PHP quando não há detalhes
        let disciplinasHtml = '';
        if (turma.disciplinas_nomes && turma.disciplinas_nomes.length) {
            disciplinasHtml = turma.disciplinas_nomes.map(nome =>
                `<li class="disciplina-status d-flex align-items-center gap-2" style="background:#f8f9fa; border-radius:8px; padding:7px 10px; margin-bottom:6px;">
                <i class="bi bi-dot"></i> <span class="fw-semibold">${nome}</span>
                <span class="text-danger ms-2"><i class="bi bi-x-circle-fill"></i> Sem plano</span>
            </li>`
            ).join('');
        } else {
            disciplinasHtml = '<li class="text-muted"><i class="bi bi-exclamation-circle"></i> Nenhuma</li>';
        }
        // Status badge
        let badgeClass = 'bg-success',
            borderClass = 'success';
        if (turma.status === 'cancelada') {
            badgeClass = 'bg-secondary';
            borderClass = 'secondary';
        } else if (turma.status === 'concluída') {
            badgeClass = 'bg-success';
            borderClass = 'success';
        }
        // Turno label
        let turnoLabel = turma.turno ? turma.turno : '';
        // Datas formatadas
        let inicio_br = turma.inicio_br || '-';
        let fim_br = turma.fim_br || '-';

        return `
    <div class="col-12 col-md-6 col-xl-4" id="turma-card-${turma.id}">
        <div class="${cardClass}">
            ${statusOverlay}
            <div class="card-body d-flex flex-column" style="position:relative;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <span class="fw-bold fs-5">
                            <i class="bi bi-mortarboard-fill text-primary"></i>
                            ${turma.nome}
                        </span>
                    </div>
                    <div class="turma-actions">
                        <a href="planos.php?turma_id=${turma.id}" class="btn btn-secondary btn-sm" title="Gerenciar Planos">
                            <i class="bi bi-journal-text"></i>Gerenciar Planos
                        </a>
                        <button class="btn btn-primary btn-sm" title="Editar" onclick="abrirModalEditarTurma(${turma.id}, '${turma.nome.replace(/'/g,"\\'")}', '${turma.ano_letivo}', '${turma.turno}', '${turma.inicio}', '${turma.fim}', '${turma.status}')">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" title="Excluir" onclick="abrirModalExcluirTurma(${turma.id}, '${turma.nome.replace(/'/g,"\\'")}')">
                            <i class="bi bi-trash"></i>
                        </button>
                        <button class="btn p-0 m-0 border-0 bg-transparent turma-toggle-btn"
                            style="font-size:2.1rem; line-height:1; box-shadow:none;"
                            title="${turma.status=='ativa'?'Cancelar':'Ativar'} turma"
                            onclick="toggleStatusTurma(${turma.id}, this)">
                            <i class="bi ${turma.status=='ativa'?'bi-toggle-off':'bi-toggle-on'}"></i>
                        </button>
                    </div>
                </div>
                <hr class="my-2">
                <div class="mb-2">
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="badge bg-info-subtle text-dark border border-info"><i class="bi bi-calendar-event"></i> Ano: ${turma.ano_letivo}</span>
                        <span class="badge bg-warning-subtle text-dark border border-warning"><i class="bi bi-clock"></i> Turno: ${turnoLabel}</span>
                        <span class="badge bg-light text-dark border border-secondary"><i class="bi bi-calendar2-week"></i> Início: ${inicio_br}</span>
                        <span class="badge bg-light text-dark border border-secondary"><i class="bi bi-calendar2-week"></i> Fim: ${fim_br}</span>
                        <span class="badge ${badgeClass} text-dark border border-${borderClass} turma-status-badge" id="turma-status-${turma.id}">
                            <i class="bi bi-activity"></i> ${turma.status}
                        </span>
                    </div>
                </div>
                <hr class="my-2">
                <div class="mb-2 turma-disc-list">
                    <b><i class="bi bi-book"></i> Disciplinas:</b>
                    <ul class="list-unstyled ms-2 mb-0">
                        ${disciplinasHtml}
                    </ul>
                </div>
            </div>
        </div>
    </div>
    `;
    }

    // AJAX para criar turma
    document.getElementById('formTurma').onsubmit = function(e) {
        e.preventDefault();
        const form = e.target;
        const dados = new FormData(form);
        let url = form.action.includes('editar_turma.php') ? '../controllers/editar_turma.php' :
            '../controllers/criar_turma.php';
        // console.log('Enviando formulário para:', url);
        fetch(url, {
                method: 'POST',
                body: dados
            })
            .then(r => r.json())
            .then(res => {
                // console.log('Resposta do backend:', res);
                if (res.success) {
                    fecharModalTurma();
                    if (form.action.includes('editar_turma.php')) {
                        // Busca a coluna correta pelo id do card (corrigido)
                        let card = document.getElementById('turma-card-' + res.turma.id);
                        let col = card ? card.closest('.col-12.col-md-6.col-xl-4') : null;
                        // console.log('Coluna encontrada para editar:', col);
                        if (col) {
                            const temp = document.createElement('div');
                            temp.innerHTML = renderTurmaCard(res.turma);
                            col.parentNode.replaceChild(temp.firstElementChild, col);
                            // console.log('Coluna substituída');
                        } else {
                            // console.warn('Coluna/card não encontrada para editar!');
                        }
                    } else {
                        // Só adiciona novo card se for criação
                        document.querySelector('.row.g-4').insertAdjacentHTML('afterbegin', renderTurmaCard(res
                            .turma));
                        // console.log('Novo card adicionado');
                    }
                    mostrarNotificacao(res.message || 'Turma salva com sucesso!', 'success');
                } else {
                    mostrarNotificacao(res.error || 'Erro ao salvar turma', 'danger');
                }
            });
    };

    // Modal de confirmação para toggle status
    function confirmarToggleStatusTurma(id, btn) {
        // Busca a coluna correta pelo id do card (corrigido)
        let card = document.getElementById('turma-card-' + id);
        let col = card ? card.closest('.col-12.col-md-6.col-xl-4') : null;
        if (!card) card = btn.closest('.card-turma');
        const statusAtual = card && card.classList.contains('cancelada') ? 'cancelada' : (card && card.classList
            .contains('concluida') ? 'concluída' : 'ativa');
        let novoStatus = statusAtual === 'ativa' ? 'cancelada' : 'ativa';
        let titulo = novoStatus === 'cancelada' ? 'Cancelar turma' : 'Ativar turma';
        let mensagem = novoStatus === 'cancelada' ?
            'Tem certeza que deseja cancelar esta turma?' :
            'Tem certeza que deseja ativar esta turma?';

        // Cria modal se não existir
        let modal = document.getElementById('modalToggleTurma');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'modalToggleTurma';
            modal.style =
                'display:block;position:fixed;z-index:2000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);';
            modal.innerHTML = `
            <div style="background:#fff;padding:0;border-radius:14px;max-width:400px;margin:100px auto;position:relative;box-shadow:0 8px 32px rgba(0,0,0,0.18);overflow:hidden;">
                <div style="background:linear-gradient(90deg,#0d6efd 60%,#4f8cff 100%);padding:16px 24px 12px 24px;display:flex;align-items:center;gap:10px;">
                    <i class="bi bi-arrow-repeat text-white" id="toggleModalIcon" style="font-size:1.7rem;"></i>
                    <h4 class="mb-0 text-white" id="toggleModalTitulo"></h4>
                    <span id="closeToggleModal" style="position:absolute;top:10px;right:18px;font-size:26px;cursor:pointer;color:#fff;opacity:0.8;">&times;</span>
                </div>
                <div style="padding:24px 24px 18px 24px;">
                    <div class="alert alert-warning d-flex align-items-center gap-2 mb-3" style="font-size:1.1em;">
                        <i class="bi bi-exclamation-circle-fill" style="font-size:1.5em;"></i>
                        Atenção! Esta ação irá alterar o status da turma.
                    </div>
                    <p id="toggleModalMensagem"></p>
                    <div class="d-flex justify-content-end gap-2 pt-2">
                        <button type="button" class="btn btn-secondary d-flex align-items-center gap-1" id="cancelToggleBtn"><i class="bi bi-x-circle"></i> Cancelar</button>
                        <button type="button" class="btn btn-primary d-flex align-items-center gap-1" id="confirmToggleBtn"><i class="bi bi-arrow-repeat"></i> <span id="toggleModalBtnText"></span></button>
                    </div>
                </div>
            </div>
        `;
            document.body.appendChild(modal);
            document.getElementById('closeToggleModal').onclick = function() {
                modal.style.display = 'none';
            };
            document.getElementById('cancelToggleBtn').onclick = function() {
                modal.style.display = 'none';
            };
        } else {
            modal.style.display = 'block';
        }
        document.getElementById('toggleModalTitulo').innerText = titulo;
        document.getElementById('toggleModalMensagem').innerText = mensagem;
        let confirmBtn = document.getElementById('confirmToggleBtn');
        let btnText = document.getElementById('toggleModalBtnText');
        btnText.innerText = titulo;
        // Ícone dinâmico
        let icon = document.getElementById('toggleModalIcon');
        if (novoStatus === 'cancelada') {
            icon.className = 'bi bi-ban text-white';
            confirmBtn.className = 'btn btn-danger d-flex align-items-center gap-1';
            confirmBtn.querySelector('i').className = 'bi bi-ban';
        } else {
            icon.className = 'bi bi-check-circle-fill text-white';
            confirmBtn.className = 'btn btn-success d-flex align-items-center gap-1';
            confirmBtn.querySelector('i').className = 'bi bi-check-circle';
        }
        confirmBtn.onclick = function() {
            modal.style.display = 'none';
            toggleStatusTurma(id, btn);
        };
    }

    // Função para ativar/desativar turma dinamicamente (chamada só após confirmação)
    function toggleStatusTurma(id, btn) {
        btn.disabled = true;
        fetch('../controllers/toggle_status_turma.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'id_turma=' + encodeURIComponent(id)
            })
            .then(r => r.json())
            .then(res => {
                btn.disabled = false;
                if (res.success) {
                    // Atualiza badge de status
                    const badge = document.getElementById('turma-status-' + id);
                    if (badge) {
                        badge.className = 'badge bg-' + (res.novo_status === 'ativa' ? 'success' : (res
                                .novo_status === 'cancelada' ? 'secondary' : 'success')) +
                            ' text-dark border border-' + (res.novo_status === 'ativa' ? 'success' : (res
                                .novo_status === 'cancelada' ? 'secondary' : 'success')) + ' turma-status-badge';
                        badge.innerHTML = '<i class="bi bi-activity"></i> ' + res.novo_status;
                    }
                    // Atualiza ícone/botão e título
                    btn.className = 'btn p-0 m-0 border-0 bg-transparent turma-toggle-btn';
                    btn.style.fontSize = '2.1rem';
                    btn.style.lineHeight = '1';
                    btn.style.boxShadow = 'none';
                    btn.title = (res.novo_status === 'ativa' ? 'Cancelar' : 'Ativar') + ' turma';
                    btn.querySelector('i').className = 'bi ' + (res.novo_status === 'ativa' ? 'bi-toggle-off' :
                        'bi-toggle-on');
                    // Atualiza o card visualmente
                    const card = btn.closest('.card-turma');
                    if (card) {
                        card.classList.remove('cancelada', 'concluida');
                        let overlay = card.querySelector('.status-overlay');
                        if (overlay) overlay.remove();
                        if (res.novo_status === 'cancelada') {
                            card.classList.add('cancelada');
                            card.insertAdjacentHTML('afterbegin', '<div class="status-overlay">CANCELADA</div>');
                        } else if (res.novo_status === 'concluída') {
                            card.classList.add('concluída');
                            card.insertAdjacentHTML('afterbegin',
                                '<div class="status-overlay"><i class="bi bi-check-circle-fill"></i> Concluída</div>'
                                );
                        }
                    }
                    mostrarNotificacao('Status da turma atualizado!', 'success');
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
                // Busca o id corretamente a partir do botão (corrigido)
                let card = this.closest('.card-turma');
                let id = null;
                if (card && card.parentNode && card.parentNode.id && card.parentNode.id.startsWith(
                        'turma-card-')) {
                    // fallback, nunca ocorre pois o id está no card
                    id = card.parentNode.id.replace('turma-card-', '');
                }
                if (!id && card && card.id && card.id.startsWith('turma-card-')) {
                    id = card.id.replace('turma-card-', '');
                }
                if (id) {
                    confirmarToggleStatusTurma(id, this);
                } else {
                    alert('Não foi possível identificar a turma para ativar/cancelar.');
                }
            };
        });
    });

    // AJAX para exclusão de turma
    document.addEventListener('DOMContentLoaded', function() {
        var formExcluir = document.querySelector('#modalExcluirTurma form');
        if (formExcluir) {
            formExcluir.onsubmit = function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                fetch(this.action, {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        fecharModalExcluirTurma();
                        // Remove o card da turma da tela
                        var card = document.getElementById('turma-card-' + res.id);
                        if (card) {
                            var col = card.closest('.col-12.col-md-6.col-xl-4');
                            if (col) col.remove();
                        }
                        mostrarNotificacao('Turma excluída com sucesso!', 'success');
                    } else {
                        mostrarNotificacao(res.error || 'Erro ao excluir turma', 'danger');
                    }
                });
            };
        }
    });

    function abrirModalToggleCapitulo(id, nome, status, btn) {
        document.getElementById('toggle_id_capitulo').value = id;
        document.getElementById('toggle_nome_capitulo').innerHTML = '<b>' + nome + '</b>';
        document.getElementById('toggle_status_capitulo').value = status === 'cancelado' ? 'em_andamento' : 'cancelado';
        document.getElementById('modalToggleCapitulo').style.display = 'block';
    }

    function fecharModalToggleCapitulo() {
        document.getElementById('modalToggleCapitulo').style.display = 'none';
    }

    function abrirModalToggleTopico(id, nome, status, btn) {
        document.getElementById('toggle_id_topico').value = id;
        document.getElementById('toggle_nome_topico').innerHTML = '<b>' + nome + '</b>';
        document.getElementById('toggle_status_topico').value = status === 'cancelado' ? 'em_andamento' : 'cancelado';
        document.getElementById('modalToggleTopico').style.display = 'block';
    }

    function fecharModalToggleTopico() {
        document.getElementById('modalToggleTopico').style.display = 'none';
    }

    // Toggle AJAX de Capítulo
    document.getElementById('formToggleCapitulo').onsubmit = async function(e) {
        e.preventDefault();
        const form = this;
        const formData = new FormData(form);
        const resp = await fetch(form.action, {
            method: 'POST',
            body: formData
        });
        const data = await resp.json();
        if (data.success) {
            fecharModalToggleCapitulo();
            mostrarNotificacao('Status do capítulo atualizado com sucesso!', 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            mostrarNotificacao(data.error || 'Erro ao atualizar status do capítulo', 'danger');
        }
    };

    // Toggle AJAX de Tópico
    document.getElementById('formToggleTopico').onsubmit = async function(e) {
        e.preventDefault();
        const form = this;
        const formData = new FormData(form);
        const resp = await fetch(form.action, {
            method: 'POST',
            body: formData
        });
        const data = await resp.json();
        if (data.success) {
            fecharModalToggleTopico();
            mostrarNotificacao('Status do tópico atualizado com sucesso!', 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            mostrarNotificacao(data.error || 'Erro ao atualizar status do tópico', 'danger');
        }
    };
    </script>
    <?php include 'footer.php'; ?>
<script>
// Modal de Dicas de Funcionamento para Turmas
<?php $isProfessor = (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'professor'); ?>
const stepsDicasTurmas = <?php if ($isProfessor): ?>[
    {
        title: 'Filtros e Pesquisa',
        html: `<div class="dica-step-card bg-dica-blue"><div class="dica-step-icone dica-blue"><i class="bi bi-funnel-fill"></i></div><div><span class="fw-bold text-dica-blue">Filtros:</span> Use o campo de busca para filtrar turmas pelo nome.<br><span class="fw-bold text-dica-blue">Ordenação:</span> Escolha como deseja ordenar as turmas.<br><span class="fw-bold text-dica-blue">Status:</span> Filtre por turmas ativas, concluídas ou canceladas.<br><span class="text-muted">Dica: Você só verá turmas vinculadas a você.</span></div></div>`
    },
    {
        title: 'Visualizar e Registrar Aula',
        html: `<div class="dica-step-card bg-dica-blue"><div class="dica-step-icone dica-blue"><i class="bi bi-journal-plus"></i></div><div><b>Visualize suas turmas e clique em <span class='badge bg-primary text-white'><i class='bi bi-journal-plus'></i> Registrar Aula</span> para lançar as aulas ministradas.</b><br>Preencha a data, o conteúdo e outras informações da aula.<br><div class='exemplo-bloco bg-primary-subtle text-primary border border-primary mt-4 mb-1 p-2 rounded-3' style='display:inline-block;'><b>Exemplo:</b> Aula de 10/04/2024 - Capítulo 2 - Exercícios</div></div></div>`
    },
    {
        title: 'Badges, Planos, Capítulos e Tópicos',
        html: `<div class="dica-step-card bg-dica-purple"><div class="dica-step-icone dica-purple"><i class="bi bi-collection"></i></div><div><span class="fw-bold text-dica-purple">Badges:</span> Mostram o total de turmas ativas, concluídas e canceladas.<br><span class="fw-bold text-dica-purple">Planos:</span> Cada turma pode ter planos de ensino para cada disciplina.<br><span class="fw-bold text-dica-purple">Paginação:</span> Navegue entre as páginas usando os botões abaixo dos cards.<br><div class='exemplo-bloco mt-2'><span class='badge bg-success'><i class='bi bi-check-circle-fill'></i> Ativa</span> <span class='badge bg-info text-dark'><i class='bi bi-flag'></i> Concluída</span> <span class='badge bg-secondary'><i class='bi bi-x-circle-fill'></i> Cancelada</span></div></div></div>`
    }
]<?php else: ?>[
    {
        title: 'Filtros e Pesquisa',
        html: `
        <div class="dica-step-card bg-dica-blue">
            <div class="dica-step-icone dica-blue"><i class="bi bi-funnel-fill"></i></div>
            <div>
                <span class="fw-bold text-dica-blue">Filtros:</span> Use o botão <span class="badge bg-primary text-white"><i class="bi bi-funnel-fill"></i></span> para abrir filtros avançados.<br>
                <span class="fw-bold text-dica-blue">Pesquisa:</span> Digite no campo para buscar turmas pelo nome.<br>
                <span class="fw-bold text-dica-blue">Ordenação:</span> Escolha como deseja ordenar as turmas.<br>
                <span class="fw-bold text-dica-blue">Status:</span> Filtre por turmas ativas, concluídas ou canceladas.
            </div>
        </div>`
    },
    {
        title: 'Criar Nova Turma',
        html: `<div class="dica-step-card bg-dica-green"><div class="dica-step-icone dica-green"><i class="bi bi-plus-circle"></i></div><div><b>1º passo:</b> Clique em <span class="badge bg-success text-white"><i class="bi bi-plus-circle"></i> Nova Turma</span> para adicionar uma nova turma.<br>Preencha os campos obrigatórios e clique em <span class="badge bg-primary text-white"><i class="bi bi-check-circle"></i> Salvar</span>.<br><div class='exemplo-bloco bg-success-subtle text-success border border-success mt-2 p-2 rounded-3'><b>Exemplo:</b> Turma: <span class='fw-bold'>1º Ano A</span></div></div></div>`
    },
    {
        title: 'Editar Turma',
        html: `<div class="dica-step-card bg-dica-yellow"><div class="dica-step-icone dica-yellow"><i class="bi bi-pencil-square"></i></div><div><b>2º passo:</b> Clique no botão <span class="badge bg-primary text-white"><i class="bi bi-pencil-square"></i> Editar</span> em um card para editar os dados da turma.<br>Altere as informações desejadas e clique em <span class="badge bg-primary text-white"><i class="bi bi-check-circle"></i> Salvar</span>.<br><div class='exemplo-bloco bg-warning-subtle text-warning border border-warning mt-2 p-2 rounded-3'><b>Exemplo:</b> Editar nome, turno, disciplinas vinculadas...</div></div></div>`
    },
    {
        title: 'Excluir Turma',
        html: `<div class="dica-step-card bg-dica-red"><div class="dica-step-icone dica-red"><i class="bi bi-trash"></i></div><div><b>3º passo:</b> Clique no botão <span class="badge bg-danger text-white"><i class="bi bi-trash"></i> Excluir</span> para remover uma turma.<br><span class="fw-bold text-danger">Atenção:</span> esta ação é <u>irreversível</u>!<br><div class='exemplo-bloco bg-danger-subtle text-danger border border-danger mt-2 p-2 rounded-3'><b>Exemplo:</b> Excluir turma que não será mais utilizada.</div></div></div>`
    },
    {
        title: 'Ativar/Cancelar Turma',
        html: `<div class="dica-step-card bg-dica-orange"><div class="dica-step-icone dica-orange"><i class="bi bi-toggle-on"></i></div><div><b>4º passo:</b> Use o botão <span class="badge bg-warning text-dark"><i class="bi bi-toggle-off"></i> / <i class="bi bi-toggle-on"></i></span> para ativar ou cancelar uma turma.<br>Confirme a ação no modal exibido.<br><div class='exemplo-bloco bg-warning-subtle text-warning border border-warning mt-2 p-2 rounded-3'><b>Exemplo:</b> Cancelar turma que não terá mais aulas.</div></div></div>`
    },
    {
        title: 'Badges, Planos, Capítulos e Tópicos',
        html: `<div class="dica-step-card bg-dica-purple"><div class="dica-step-icone dica-purple"><i class="bi bi-collection"></i></div><div><span class="fw-bold text-dica-purple">Badges:</span> Mostram o total de turmas ativas, concluídas e canceladas.<br><span class="fw-bold text-dica-purple">Planos:</span> Cada turma pode ter planos de ensino para cada disciplina.<br><span class="fw-bold text-dica-purple">Paginação:</span> Navegue entre as páginas usando os botões abaixo dos cards.<br><div class='exemplo-bloco mt-2'><span class='badge bg-success'><i class='bi bi-check-circle-fill'></i> Ativa</span> <span class='badge bg-info text-dark'><i class='bi bi-flag'></i> Concluída</span> <span class='badge bg-secondary'><i class='bi bi-x-circle-fill'></i> Cancelada</span></div></div></div>`
    }
]<?php endif; ?>;
let stepAtualTurmas = 0;
function mostrarStepDicasTurmas(idx) {
    stepAtualTurmas = idx;
    // Atualiza stepper
    // Stepper: mostra só o número de steps correto
    let totalSteps = stepsDicasTurmas.length;
    // Ícones para cada step (ordem deve bater com stepsDicasTurmas)
    let icones = <?php if ($isProfessor): ?>[
        'bi-funnel-fill',
        'bi-journal-plus',
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
        let el = document.getElementById('stepCircleTurmas'+(i+1));
        if (el) {
            el.style.display = i < totalSteps ? '' : 'none';
            el.classList.toggle('active', i === idx);
            let icon = el.querySelector('i');
            if (icon) {
                icon.className = 'bi ' + (icones[i] || '');
                icon.style.opacity = i < totalSteps ? 1 : 0;
                icon.style.fontSize = '1.25em';
            }
        }
        let line = el && el.nextElementSibling && el.nextElementSibling.classList.contains('step-line') ? el.nextElementSibling : null;
        if (line) line.style.display = (i < totalSteps-1) ? '' : 'none';
    }
    // Atualiza conteúdo
    document.getElementById('stepContentDicasTurmas').innerHTML = `
        <h5 class='fw-bold mb-3 text-primary'>${stepsDicasTurmas[idx].title}</h5>
        <div style='font-size:1.13em;'>${stepsDicasTurmas[idx].html}</div>
    `;
    // Botões
    document.getElementById('btnStepAnteriorTurmas').style.display = idx === 0 ? 'none' : '';
    document.getElementById('btnStepProximoTurmas').innerHTML = idx === stepsDicasTurmas.length-1 ? 'Fechar <i class="bi bi-x"></i>' : 'Próximo <i class="bi bi-arrow-right"></i>';
}
document.getElementById('btnDicasTurmas').onclick = function(e) {
    if (e) e.stopPropagation();
    document.getElementById('modalDicasTurmas').style.display = 'block';
    mostrarStepDicasTurmas(0);
};
document.getElementById('btnStepAnteriorTurmas').onclick = function() {
    if (stepAtualTurmas > 0) mostrarStepDicasTurmas(stepAtualTurmas-1);
};
document.getElementById('btnStepProximoTurmas').onclick = function() {
    if (stepAtualTurmas < stepsDicasTurmas.length-1) mostrarStepDicasTurmas(stepAtualTurmas+1);
    else fecharModalDicasTurmas();
};
function fecharModalDicasTurmas() {
    document.getElementById('modalDicasTurmas').style.display = 'none';
}
</script>
</body>

</html>