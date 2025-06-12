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

// Buscar turmas com filtro e paginação
$sql = "SELECT * FROM turmas " . ($where ? $where : '') . " ORDER BY ano_letivo DESC, nome LIMIT $per_page OFFSET $offset";
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
    <style>
        body { background: #f5f5f5; }
        .card-turma { border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); min-height: 180px; }
        .card-title { font-size: 1.2rem; font-weight: 600; }
        .turma-meta { font-size: 0.97em; color: #888; }
        .turma-label { font-size: 1em; color: #666; }
        .disciplina-status { font-size: 1.05em; }
        .disciplina-status .bi { vertical-align: -0.15em; }
        .turma-actions { display: flex; gap: 0.5rem; align-items: center; }
        .card-title .turma-actions { float: right; }
        .card-title { display: flex; justify-content: space-between; align-items: center; }
        .turma-disc-list { margin-bottom: 0.5rem; }
        .turma-toggle-btn:focus, .turma-toggle-btn:active {
            outline: none !important;
            box-shadow: none !important;
        }
        .turma-toggle-btn .bi {
            vertical-align: middle;
        }
        .card-turma.inativa {
            background: #e9ecef !important;
            opacity: 0.85;
            border: 2px dashed #adb5bd;
            position: relative;
        }
        .card-turma.inativa .status-overlay {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%,-50%);
            font-size: 2rem;
            color: #6c757d;
            background: rgba(255,255,255,0.85);
            padding: 0.5em 1.5em;
            border-radius: 1em;
            font-weight: bold;
            z-index: 2;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }
        .card-turma.concluida {
            background: #e6f9ea !important;
            border: 2px solid #198754;
            position: relative;
        }
        .card-turma.concluida .status-overlay {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%,-50%);
            font-size: 2.2rem;
            color: #198754;
            background: rgba(255,255,255,0.92);
            padding: 0.5em 1.5em;
            border-radius: 1em;
            font-weight: bold;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 0.5em;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }
        .card-turma.cancelada {
            background: #e9ecef !important;
            opacity: 0.85;
            border: 2px dashed #adb5bd;
            position: relative;
        }
        .card-turma.cancelada .status-overlay {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%,-50%);
            font-size: 2rem;
            color: #6c757d;
            background: rgba(255,255,255,0.85);
            padding: 0.5em 1.5em;
            border-radius: 1em;
            font-weight: bold;
            z-index: 2;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }
        .pagination { justify-content: center; }
        .search-turma-form { max-width: 400px; margin: 0 auto 1.5rem auto; }
    </style>
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
        if (urlParams.get('erro') === 'turma_vinculada') msg = 'Não é possível excluir turma com disciplinas vinculadas!';
        if (msg) mostrarNotificacao(msg, 'danger');
    }
</script>
<div class="container-fluid py-4">

    <div class="row">
        <div class="col-12">
            <div class="bg-white rounded shadow-sm p-4 mb-3">
                <h2 class="mb-2"><i class="bi bi-people-fill"></i> Turmas</h2>
                <div class="turma-label mb-1">
                    <i class="bi bi-info-circle"></i>
                    Aqui você encontra todas as turmas cadastradas. Cada card mostra o nome, ano letivo, turno, disciplinas e planos.
                </div>
                <?php if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'professor'): ?>
                    <button class="btn btn-success mt-2" onclick="abrirModalTurma()">
                        <i class="bi bi-plus-circle"></i> Criar Turma
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Barra de pesquisa, filtros e contadores -->
    <div class="row mb-3 g-2 align-items-end">
        <div class="col-12 col-md-8">
            <form class="d-flex align-items-end flex-wrap gap-2" id="filtrosForm" method="get" action="">
                <div class="input-group" style="max-width:360px;">
                    <span class="input-group-text bg-white border-end-0" style="border-radius:8px 0 0 8px;">
                        <i class="bi bi-search text-primary"></i>
                    </span>
                    <input type="text" class="form-control border-start-0 border-end-0" name="search" placeholder="Pesquisar turma..." value="<?= htmlspecialchars($search) ?>" style="border-radius:0; box-shadow:none;">
                    <?php if ($search !== ''): ?>
                        <button type="submit" class="btn btn-outline-secondary border-start-0 border-end-0" style="border-radius:0;" tabindex="-1" onclick="this.form.search.value=''; this.form.submit(); return false;" title="Limpar pesquisa">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    <?php endif; ?>
                    <button class="btn btn-primary" type="submit" style="border-radius:0 8px 8px 0;">
                        <i class="bi bi-arrow-right-circle"></i>
                    </button>
                </div>
                <div class="dropdown">
                    <button class="btn btn-gradient-primary dropdown-toggle px-4 py-2 fw-bold shadow-sm" type="button" id="dropdownFiltros" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius: 12px; background: linear-gradient(90deg,#0d6efd 60%,#4f8cff 100%); color: #fff; border: none;">
                        <i class="bi bi-funnel-fill me-1"></i> Filtros Avançados
                    </button>
                    <div class="dropdown-menu p-4 shadow-lg border-0" style="min-width: 520px; border-radius: 18px; background: #f8faff;" onclick="event.stopPropagation();">
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
                                    <input type="radio" class="btn-check" name="per_page" id="per_page_<?= $opt ?>" value="<?= $opt ?>" autocomplete="off" <?= $per_page_sel==$opt?'checked':'' ?>>
                                    <label class="btn btn-outline-primary" for="per_page_<?= $opt ?>" style="min-width:44px;">
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
                                    <input type="radio" class="btn-check" name="ordem" id="ordem_<?= $key ?>" value="<?= $key ?>" autocomplete="off" <?= $ordem==$key?'checked':'' ?>>
                                    <label class="btn btn-outline-primary" for="ordem_<?= $key ?>" style="min-width:90px;">
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
                                    <input type="radio" class="btn-check" name="status_filtro" id="status_<?= $key ?: 'todos' ?>" value="<?= $key ?>" autocomplete="off" <?= $status_filtro===$key?'checked':'' ?>>
                                    <label class="btn btn-outline-primary" for="status_<?= $key ?: 'todos' ?>" style="min-width:90px;">
                                        <i class="bi <?= $info['icon'] ?>"></i> <?= $info['label'] ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-gradient-primary w-100 mt-2 fw-bold" style="border-radius: 8px; background: linear-gradient(90deg,#0d6efd 60%,#4f8cff 100%); color: #fff; border: none;">
                            <i class="bi bi-funnel"></i> Salvar Filtros
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-12 col-md-4 d-flex gap-2 justify-content-md-end mt-md-0 mt-2">
            <span class="badge bg-primary text-white" title="Turmas nesta página" style="font-size:1em;  border-radius:8px;">
                <i class="bi bi-list-ol"></i> Página: <?= count($turmas) ?>
            </span>
            <span class="badge bg-secondary text-white" title="Total de turmas registradas" style="font-size:1em;  border-radius:8px;">
                <i class="bi bi-collection"></i> Total: <?= $total_turmas ?>
            </span>
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
    if (isset($_GET['ordem'])) {
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
    <div class="<?= $cardClass ?>" 
    >
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
                        <?php if ($turma['status'] === 'ativa'): ?>
                            <a href="registro_aulas.php?turma_id=<?= $turma['id'] ?>" class="btn btn-outline-primary btn-sm" title="Registrar Aula">
                                <i class="bi bi-journal-plus"></i> Registrar Aula
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="planos.php?turma_id=<?= $turma['id'] ?>" class="btn btn-secondary btn-sm" title="Gerenciar Planos">
                            <i class="bi bi-journal-text"></i>Gerenciar Planos
                        </a>
                        <button class="btn btn-primary btn-sm" title="Editar" onclick="abrirModalEditarTurma(<?= $turma['id'] ?>, '<?= htmlspecialchars(addslashes($turma['nome'])) ?>', '<?= $turma['ano_letivo'] ?>', '<?= $turma['turno'] ?>', '<?= $turma['inicio'] ?>', '<?= $turma['fim'] ?>', '<?= $turma['status'] ?>')">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" title="Excluir" onclick="abrirModalExcluirTurma(<?= $turma['id'] ?>, '<?= htmlspecialchars(addslashes($turma['nome'])) ?>')">
                            <i class="bi bi-trash"></i>
                        </button>
                        <button 
                            class="btn p-0 m-0 border-0 bg-transparent turma-toggle-btn" 
                            style="font-size:2.1rem; line-height:1; box-shadow:none;" 
                            title="<?= $turma['status']=='ativa'?'Cancelar':'Ativar' ?> turma"
                            onclick="toggleStatusTurma(<?= $turma['id'] ?>, this)">
                            <i class="bi <?= $turma['status']=='ativa'?'bi-toggle-off':'bi-toggle-on' ?>"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <hr class="my-2">
            <!-- Dados principais -->
            <div class="mb-2">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="badge bg-info-subtle text-dark border border-info"><i class="bi bi-calendar-event"></i> Ano: <?= htmlspecialchars($turma['ano_letivo']) ?></span>
                    <span class="badge bg-warning-subtle text-dark border border-warning"><i class="bi bi-clock"></i> Turno: <?= htmlspecialchars($turma['turno']) ?></span>
                    <span class="badge bg-light text-dark border border-secondary"><i class="bi bi-calendar2-week"></i> Início: <?= $turma['inicio'] ? date('d/m/Y', strtotime($turma['inicio'])) : '-' ?></span>
                    <span class="badge bg-light text-dark border border-secondary"><i class="bi bi-calendar2-week"></i> Fim: <?= $turma['fim'] ? date('d/m/Y', strtotime($turma['fim'])) : '-' ?></span>
                    <span class="badge 
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
            <style>
                .pagination .page-link {
                    border-radius: 50% !important;
                    width: 42px;
                    height: 42px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 3px;
                    font-size: 1.15em;
                    border: none;
                    color: #0d6efd;
                    background: #fff;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
                    transition: background 0.2s, color 0.2s;
                }
                .pagination .page-item.active .page-link {
                    background: #0d6efd;
                    color: #fff;
                    font-weight: bold;
                    border: none;
                }
                .pagination .page-link:focus {
                    box-shadow: 0 0 0 0.15rem #0d6efd33;
                }
                .pagination .page-item.disabled .page-link {
                    background: #f1f1f1;
                    color: #bbb;
                }
            </style>
            <nav aria-label="Navegação de páginas">
                <ul class="pagination justify-content-center mt-4">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page-1])) ?>" tabindex="-1" aria-label="Anterior">
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
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor;
                        if ($end < $total_pages) {
                            if ($end < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            echo '<li class="page-item"><a class="page-link" href="?'.http_build_query(array_merge($_GET, ['page'=>$total_pages])).'">'.$total_pages.'</a></li>';
                        }
                    ?>
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page+1])) ?>" aria-label="Próxima">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- Modal de Criar/Editar Turma -->
<div id="modalTurma" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);">
    <div style="background:#fff;padding:30px 32px;border-radius:12px;max-width:700px;width:95vw;margin:60px auto;position:relative;">
        <span onclick="fecharModalTurma()" style="position:absolute;top:10px;right:15px;font-size:22px;cursor:pointer;">&times;</span>
        <h4 id="tituloModalTurma">Criar Turma</h4>
        <form id="formTurma" action="../controllers/criar_turma.php" method="POST">
            <input type="hidden" name="id_turma" id="id_turma">
            <input type="text" name="nome" id="nome_turma" placeholder="Nome da turma" required class="form-control mb-2">
            <div class="row mb-2">
                <div class="col-md-6 mb-2 mb-md-0">
                    <label>Início:</label>
                    <input type="date" name="inicio" id="inicio_turma" class="form-control" onchange="atualizarAnoLetivo()">
                </div>
                <div class="col-md-6">
                    <label>Fim:</label>
                    <input type="date" name="fim" id="fim_turma" class="form-control" onchange="atualizarAnoLetivo()">
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6 mb-2 mb-md-0">
                    <label>Ano letivo:</label>
                    <input type="number" name="ano_letivo" id="ano_letivo_turma" placeholder="Ano letivo" required class="form-control" min="2000" max="2100">
                </div>
                <div class="col-md-6">
                    <label>Turno:</label>
                    <select name="turno" id="turno_turma" class="form-select">
                        <option value="manha">Manhã</option>
                        <option value="tarde">Tarde</option>
                        <option value="noite">Noite</option>
                    </select>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6 mb-2 mb-md-0">
                    <label>Status:</label>
                    <select name="status" id="status_turma" class="form-select">
                        <option value="ativa">Ativa</option>
                        <option value="concluída">Concluída</option>
                        <option value="cancelada">Cancelada</option>
                    </select>
                </div>
            </div>
            <label class="mt-2"><b>Disciplinas da turma:</b></label>
            <select name="disciplinas[]" id="disciplinas_turma" class="form-select mb-2" multiple required>
                <option></option>
                <?php foreach ($disciplinas as $disc): ?>
                    <option value="<?= $disc['id'] ?>"><?= htmlspecialchars($disc['nome']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="redirect" value="turmas.php">
        </form>
        <div class="d-flex justify-content-end gap-2 pt-3 border-top mt-4" style="background:transparent;">
            <button type="button" class="btn btn-secondary" onclick="fecharModalTurma()">Cancelar</button>
            <button type="submit" class="btn btn-primary" id="btnSalvarTurma" form="formTurma">Salvar</button>
        </div>
    </div>
</div>
<!-- Modal de Confirmar Exclusão -->
<div id="modalExcluirTurma" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);">
    <div style="background:#fff;padding:30px 20px;border-radius:8px;max-width:400px;margin:100px auto;position:relative;">
        <span onclick="fecharModalExcluirTurma()" style="position:absolute;top:10px;right:15px;font-size:22px;cursor:pointer;">&times;</span>
        <h4>Excluir Turma</h4>
        <form action="../controllers/excluir_turma.php" method="POST">
            <input type="hidden" name="id_turma" id="excluir_id_turma">
            <input type="hidden" name="redirect" value="turmas.php">
            <p id="excluir_nome_turma" style="margin:15px 0;"></p>
            <button type="submit" class="btn btn-danger">Confirmar Exclusão</button>
            <button type="button" class="btn btn-secondary" onclick="fecharModalExcluirTurma()">Cancelar</button>
        </form>
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
    document.getElementById('status_turma').value = 'ativa';
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
    document.getElementById('status_turma').value = status || 'ativa';
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
}
function fecharModalTurma() {
    document.getElementById('modalTurma').style.display = 'none';
}
function abrirModalExcluirTurma(id, nome) {
    document.getElementById('excluir_id_turma').value = id;
    document.getElementById('excluir_nome_turma').innerHTML = 'Tem certeza que deseja excluir a turma <b>' + nome + '</b>?';
    document.getElementById('modalExcluirTurma').style.display = 'block';
}
function fecharModalExcluirTurma() {
    document.getElementById('modalExcluirTurma').style.display = 'none';
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
    // Disciplinas
    let disciplinasHtml = '';
    if (turma.disciplinas_nomes && turma.disciplinas_nomes.length) {
        disciplinasHtml = turma.disciplinas_nomes.map(nome =>
            `<li class="disciplina-status d-flex align-items-center gap-2" style="background:#f8f9fa; border-radius:8px; padding:7px 10px; margin-bottom:6px;">
                <i class="bi bi-dot"></i> <span class="fw-semibold">${nome}</span>
            </li>`
        ).join('');
    } else {
        disciplinasHtml = '<li class="text-muted"><i class="bi bi-exclamation-circle"></i> Nenhuma</li>';
    }
    // Status badge
    let badgeClass = 'bg-success', borderClass = 'success';
    if (turma.status === 'cancelada') {
        badgeClass = 'bg-secondary'; borderClass = 'secondary';
    } else if (turma.status === 'concluída') {
        badgeClass = 'bg-success'; borderClass = 'success';
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
    let url = form.action.includes('editar_turma.php') ? '../controllers/editar_turma.php' : '../controllers/criar_turma.php';
    fetch(url, {
        method: 'POST',
        body: dados
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            fecharModalTurma();
            if (form.action.includes('editar_turma.php')) {
                // Atualizar card existente
                let card = document.getElementById('turma-card-' + res.turma.id);
                if (card) {
                    // Substitui o card inteiro pelo novo HTML
                    card.outerHTML = renderTurmaCard(res.turma);
                }
            } else {
                // Adicionar novo card
                document.querySelector('.row.g-4').insertAdjacentHTML('afterbegin', renderTurmaCard(res.turma));
            }
            mostrarNotificacao(res.message || 'Turma salva com sucesso!', 'success');
        } else {
            mostrarNotificacao(res.error || 'Erro ao salvar turma', 'danger');
        }
    });
};

// AJAX para excluir turma
document.querySelector('#modalExcluirTurma form').onsubmit = function(e) {
    e.preventDefault();
    const form = e.target;
    const dados = new FormData(form);
    fetch('../controllers/excluir_turma.php', {
        method: 'POST',
        body: dados
    })
    .then(r => r.json()) // <-- corrigido aqui
    .then(res => {
        if (res.success) {
            fecharModalExcluirTurma();
            let card = document.getElementById('turma-card-' + res.id);
            if (card) card.remove();
            mostrarNotificacao(res.message || 'Turma excluída com sucesso!', 'success');
        } else {
            mostrarNotificacao(res.error || 'Erro ao excluir turma', 'danger');
        }
    });
};

// Função para ativar/desativar turma dinamicamente
function toggleStatusTurma(id, btn) {
    btn.disabled = true;
    fetch('../controllers/toggle_status_turma.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id_turma=' + encodeURIComponent(id)
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        if (res.success) {
            // Atualiza badge de status
            const badge = document.getElementById('turma-status-' + id);
            if (badge) {
                badge.className = 'badge bg-' + (res.novo_status === 'ativa' ? 'success' : (res.novo_status === 'cancelada' ? 'secondary' : 'success')) + ' text-dark border border-' + (res.novo_status === 'ativa' ? 'success' : (res.novo_status === 'cancelada' ? 'secondary' : 'success')) + ' turma-status-badge';
                badge.innerHTML = '<i class="bi bi-activity"></i> ' + res.novo_status;
            }
            // Atualiza ícone/botão
            btn.className = 'btn p-0 m-0 border-0 bg-transparent turma-toggle-btn';
            btn.style.fontSize = '2.1rem';
            btn.style.lineHeight = '1';
            btn.style.boxShadow = 'none';
            btn.title = (res.novo_status === 'ativa' ? 'Cancelar' : 'Ativar') + ' turma';
            btn.querySelector('i').className = 'bi ' + (res.novo_status === 'ativa' ? 'bi-toggle-off' : 'bi-toggle-on');
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
                    card.classList.add('concluida');
                    card.insertAdjacentHTML('afterbegin', '<div class="status-overlay"><i class="bi bi-check-circle-fill"></i> Concluída</div>');
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
</script>
<?php include 'footer.php'; ?>
</body>
</html>