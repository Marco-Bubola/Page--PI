<?php
session_start();
if (!isset($_SESSION['usuario_nome']) || !in_array($_SESSION['usuario_tipo'], ['coordenador', 'admin', 'professor'])) {
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
                <button class="btn btn-success mt-2" onclick="abrirModalTurma()">
                    <i class="bi bi-plus-circle"></i> Criar Turma
                </button>
            </div>
        </div>
    </div>
    <!-- Barra de pesquisa, filtros e contadores -->
    <div class="row mb-3 g-2 align-items-end">
        <div class="col-12 col-md-4">
            <form class="d-flex" method="get" action="" style="gap:8px;">
                <input type="text" class="form-control" name="search" placeholder="Pesquisar turma..." value="<?= htmlspecialchars($search) ?>" style="border-radius:8px;">
                <button class="btn btn-outline-primary" type="submit" style="border-radius:8px;"><i class="bi bi-search"></i></button>
                <?php if ($search !== ''): ?>
                    <a href="turmas.php" class="btn btn-outline-secondary" title="Limpar pesquisa" style="border-radius:8px;"><i class="bi bi-x-circle"></i></a>
                <?php endif; ?>
                <!-- Mantém os filtros ao limpar pesquisa -->
                <?php if (isset($_GET['ordem'])): ?><input type="hidden" name="ordem" value="<?= htmlspecialchars($_GET['ordem']) ?>"><?php endif; ?>
                <?php if (isset($_GET['per_page'])): ?><input type="hidden" name="per_page" value="<?= htmlspecialchars($_GET['per_page']) ?>"><?php endif; ?>
            </form>
        </div>
        <div class="col-6 col-md-2">
            <label for="per_page" class="form-label mb-0" style="font-weight:500;">Mostrar:</label>
            <?php
                $per_page_options = [3, 6, 12, 24, 48];
                $per_page_sel = isset($_GET['per_page']) ? intval($_GET['per_page']) : $per_page;
            ?>
            <form method="get" action="" id="perPageForm">
                <select name="per_page" id="per_page" class="form-select form-select-sm" style="width:100%;" onchange="this.form.submit()">
                    <?php foreach ($per_page_options as $opt): ?>
                        <option value="<?= $opt ?>" <?= $per_page_sel==$opt?'selected':'' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
                <!-- Mantém os filtros ao mudar quantidade -->
                <?php if ($search !== ''): ?><input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
                <?php if (isset($_GET['ordem'])): ?><input type="hidden" name="ordem" value="<?= htmlspecialchars($_GET['ordem']) ?>"><?php endif; ?>
            </form>
        </div>
        <div class="col-6 col-md-3">
            <label for="ordem" class="form-label mb-0" style="font-weight:500;">Ordenar:</label>
            <?php $ordem = isset($_GET['ordem']) ? $_GET['ordem'] : 'recentes'; ?>
            <form method="get" action="" id="ordemForm">
                <select name="ordem" id="ordem" class="form-select form-select-sm" style="width:100%;" onchange="this.form.submit()">
                    <option value="recentes" <?= $ordem=='recentes'?'selected':'' ?>>Mais recentes</option>
                    <option value="antigas" <?= $ordem=='antigas'?'selected':'' ?>>Mais antigas</option>
                    <option value="az" <?= $ordem=='az'?'selected':'' ?>>A-Z (alfabética)</option>
                    <option value="za" <?= $ordem=='za'?'selected':'' ?>>Z-A (alfabética)</option>
                </select>
                <!-- Mantém os filtros ao mudar ordem -->
                <?php if ($search !== ''): ?><input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
                <?php if (isset($_GET['per_page'])): ?><input type="hidden" name="per_page" value="<?= htmlspecialchars($_GET['per_page']) ?>"><?php endif; ?>
            </form>
        </div>
        <div class="col-12 col-md-3 d-flex gap-2 justify-content-md-end mt-md-0">
            <span class="badge bg-primary text-white" title="Turmas nesta página" style="font-size:1em;  border-radius:8px;">
                <i class="bi bi-list-ol"></i> Página: <?= count($turmas) ?>
            </span>
            <span class="badge bg-secondary text-white" title="Total de turmas registradas" style="font-size:1em;  border-radius:8px;">
                <i class="bi bi-collection"></i> Total: <?= $total_turmas ?>
            </span>
        </div>
    </div>
    <?php
    // --- ORDEM SQL ---
    $ordem_sql = "ORDER BY ano_letivo DESC, nome";
    if (isset($_GET['ordem'])) {
        if ($_GET['ordem'] == 'recentes') $ordem_sql = "ORDER BY id DESC";
        elseif ($_GET['ordem'] == 'antigas') $ordem_sql = "ORDER BY id ASC";
        elseif ($_GET['ordem'] == 'az') $ordem_sql = "ORDER BY nome ASC";
        elseif ($_GET['ordem'] == 'za') $ordem_sql = "ORDER BY nome DESC";
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
    ?>
    <div class="col-12 col-md-6 col-xl-4">
        <div class="<?= $cardClass ?>">
            <?= $statusOverlay ?>
            <div class="card-body d-flex flex-column" style="position:relative; z-index:3;">
                                <!-- Cabeçalho: Título + Ações -->
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <span class="fw-bold fs-5">
                                            <i class="bi bi-mortarboard-fill text-primary"></i>
                                            <?= htmlspecialchars($turma['nome']) ?>
                            </span>
                                    </div>
                                    <div class="turma-actions">
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
                                                echo '<li class="disciplina-status d-flex align-items-center gap-2">';
                                                echo '<i class="bi bi-dot"></i> <span class="fw-semibold">' . htmlspecialchars($disc['nome']) . '</span>';
                                                // Plano status e ação
                                                if ($temPlano) {
                                                    echo '<span class="text-success" title="Plano criado"><i class="bi bi-check-circle-fill"></i></span>';
                                                    echo '<a href="planos.php?turma_id=' . $turma['id'] . '&disciplina_id=' . $disc['id'] . '" class="btn btn-outline-success btn-sm py-0 px-2 ms-1" title="Ver plano"><i class="bi bi-eye"></i> Plano</a>';
                                                } else {
                                                    echo '<span class="text-danger" title="Sem plano"><i class="bi bi-x-circle-fill"></i></span>';
                                                    echo '<a href="planos.php?turma_id=' . $turma['id'] . '&disciplina_id=' . $disc['id'] . '" class="btn btn-outline-primary btn-sm py-0 px-2 ms-1" title="Criar plano"><i class="bi bi-plus-circle"></i> Plano</a>';
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
            <nav>
                <ul class="pagination mt-4">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page-1])) ?>"><i class="bi bi-chevron-left"></i></a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page+1])) ?>"><i class="bi bi-chevron-right"></i></a>
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
    let disciplinas = turma.disciplinas_nomes && turma.disciplinas_nomes.length
        ? turma.disciplinas_nomes.join(', ')
        : '<span class="text-muted">Nenhuma</span>';
    return `
    <div class="col-12 col-md-6 col-xl-4" id="turma-card-${turma.id}">
        <div class="card card-turma h-100">
            <div class="card-body d-flex flex-column">
                <div class="card-title mb-1">
                    <span>
                        <i class="bi bi-mortarboard-fill text-primary"></i>
                        Turma: ${turma.nome}
                    </span>
                    <span class="turma-actions">
                     <a href="planos.php?turma_id=${turma.id}" class="btn btn-secondary btn-sm" title="Gerenciar Planos">
                            <i class="bi bi-journal-text"></i>Gerenciar Planos
                        </a>
                        <button class="btn btn-primary btn-sm" title="Editar" onclick="abrirModalEditarTurma(${turma.id}, '${turma.nome.replace(/'/g,"\\'")}', '${turma.ano_letivo}', '${turma.turno}', '${turma.inicio}', '${turma.fim}', '${turma.status}')">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" title="Excluir" onclick="abrirModalExcluirTurma(${turma.id}, '${turma.nome.replace(/'/g,"\\'")}')">
                            <i class="bi bi-trash"></i>
                        </button>
                       
                        <button class="btn btn-outline-${turma.status=='ativa'?'secondary':'success'} btn-sm" 
                            title="${turma.status=='ativa'?'Desativar':'Ativar'} turma"
                            onclick="toggleStatusTurma(${turma.id}, this)">
                            <i class="bi ${turma.status=='ativa'?'bi-toggle-off':'bi-toggle-on'}"></i>
                        </button>
                    </span>
                </div>
                <div class="turma-meta mb-2">
                    <i class="bi bi-calendar-event"></i> Ano letivo: ${turma.ano_letivo} |
                    <i class="bi bi-clock"></i> Turno: ${turma.turno} |
                    <i class="bi bi-calendar2-week"></i> Início: ${turma.inicio ? turma.inicio_br : '-'}
                    | <i class="bi bi-calendar2-week"></i> Fim: ${turma.fim ? turma.fim_br : '-'}
                    | <i class="bi bi-activity"></i> Status: ${turma.status}
                </div>
                <div class="mb-2 turma-disc-list">
                    <b><i class="bi bi-book"></i> Disciplinas:</b>
                    <ul class="list-unstyled ms-2 mb-0">
                    ${turma.disciplinas.map(disc => `
                        <li class="disciplina-status">
                            <i class="bi bi-dot"></i> ${disc.nome} 
                            ${disc.tem_plano ? '<span class="text-success" title="Plano criado"><i class="bi bi-check-circle-fill"></i></span>' : '<span class="text-danger" title="Sem plano"><i class="bi bi-x-circle-fill"></i></span>'}
                        </li>
                    `).join('')}
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
                if (card) card.outerHTML = renderTurmaCard(res.turma);
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