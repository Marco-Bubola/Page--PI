<?php
session_start();
if (!isset($_SESSION['usuario_nome']) || !in_array($_SESSION['usuario_tipo'], ['professor', 'coordenador', 'admin'])) {
    header('Location: ../index.php');
    exit();
}
require_once '../config/conexao.php';

// Filtros e busca
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$ordem = isset($_GET['ordem']) ? $_GET['ordem'] : 'recentes';
$professor_filtro = isset($_GET['professor']) ? trim($_GET['professor']) : '';
$turma_filtro = isset($_GET['turma']) ? trim($_GET['turma']) : '';
$disciplina_filtro = isset($_GET['disciplina']) ? trim($_GET['disciplina']) : '';
$data_aula_filtro = isset($_GET['data_aula']) ? trim($_GET['data_aula']) : '';
$aulas_por_pagina = 8;
$pagina = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($pagina - 1) * $aulas_por_pagina;

// Montar WHERE
$where = [];
$params = [];
$types = '';
if ($search !== '') {
    $where[] = "(d.nome LIKE ? OR t.nome LIKE ? OR u.nome LIKE ? OR a.data LIKE ? OR a.comentario LIKE ?)";
    for ($i=0; $i<5; $i++) {
        $params[] = "%$search%";
        $types .= 's';
    }
}
if ($professor_filtro !== '') {
    $where[] = "u.nome = ?";
    $params[] = $professor_filtro;
    $types .= 's';
}
if ($turma_filtro !== '') {
    $where[] = "t.nome = ?";
    $params[] = $turma_filtro;
    $types .= 's';
}
if ($disciplina_filtro !== '') {
    $where[] = "d.nome = ?";
    $params[] = $disciplina_filtro;
    $types .= 's';
}
if ($data_aula_filtro !== '') {
    $where[] = "a.data = ?";
    $params[] = $data_aula_filtro;
    $types .= 's';
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Ordenação
switch ($ordem) {
    case 'antigas': $order_sql = 'a.data ASC, a.id ASC'; break;
    case 'az': $order_sql = 'd.nome ASC, t.nome ASC, a.data DESC'; break;
    case 'za': $order_sql = 'd.nome DESC, t.nome DESC, a.data DESC'; break;
    default: $order_sql = 'a.data DESC, a.id DESC'; // recentes
}

// Buscar total de aulas
$sql_total = "SELECT COUNT(*) as total
    FROM aulas a
    JOIN disciplinas d ON a.disciplina_id = d.id
    JOIN turmas t ON a.turma_id = t.id
    JOIN usuarios u ON a.professor_id = u.id
    $where_sql";
$stmt = $conn->prepare($sql_total);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res_total = $stmt->get_result();
$total_aulas = $res_total ? intval($res_total->fetch_assoc()['total']) : 0;
$total_paginas = ceil($total_aulas / $aulas_por_pagina);
$stmt->close();

// Buscar aulas
$sql = "SELECT a.id, a.data, d.nome AS disciplina_nome, t.nome AS turma_nome, a.comentario, u.nome AS professor_nome
    FROM aulas a
    JOIN disciplinas d ON a.disciplina_id = d.id
    JOIN turmas t ON a.turma_id = t.id
    JOIN usuarios u ON a.professor_id = u.id
    $where_sql
    ORDER BY $order_sql
    LIMIT $aulas_por_pagina OFFSET $offset";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$aulas = [];
$aula_ids = [];
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $aulas[] = $row;
        $aula_ids[] = $row['id'];
    }
}
$stmt->close();
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
// Buscar todos os professores, turmas e disciplinas para filtro
$professores = [];
$res_prof = $conn->query("SELECT DISTINCT u.nome FROM usuarios u JOIN aulas a ON a.professor_id = u.id ORDER BY u.nome");
while ($row = $res_prof->fetch_assoc()) $professores[] = $row['nome'];
$turmas = [];
$res_turmas = $conn->query("SELECT DISTINCT t.nome FROM turmas t JOIN aulas a ON a.turma_id = t.id ORDER BY t.nome");
while ($row = $res_turmas->fetch_assoc()) $turmas[] = $row['nome'];
$disciplinas = [];
$res_disc = $conn->query("SELECT DISTINCT d.nome FROM disciplinas d JOIN aulas a ON a.disciplina_id = d.id ORDER BY d.nome");
while ($row = $res_disc->fetch_assoc()) $disciplinas[] = $row['nome'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Histórico de Aulas Ministradas - PI Page</title>
    <link rel="stylesheet" href="../assets/css/css_base_page.css">
    <link rel="icon" type="image/png" href="../assets/img/LOGO_PAGE.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        body { background: #f5f5f5; }
        .head-historico {
            border: 3px solid #0d6efd;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            padding: 1.5rem 2rem 1.2rem 2rem;
            margin-bottom: 2.2rem;
            position: relative;
        }
        .head-historico .icon {
            background: #0d6efd;
            color: #fff;
            border-radius: 50%;
            width: 56px;
            height: 56px;
            font-size: 2.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px #0d6efd33;
        }
        .section-title { font-size: 2rem; font-weight: 700; color: #0d6efd; margin-bottom: 0; }
        .section-desc { color: #666; font-size: 1.08em; }
        .dica-btn { font-size: 1.1rem; border-radius: 14px; font-weight: 600; box-shadow: 0 2px 8px #0d6efd33; }
        .modal-dica .modal-content { border-radius: 18px; }
        .modal-dica .modal-header { background: #e3f0ff; border-bottom: none; }
        .modal-dica .modal-title { font-size: 1.3rem; font-weight: 700; color: #0d6efd; }
        .modal-dica .modal-body { font-size: 1.08rem; }
        .step-dica {
            display: flex; align-items: flex-start; gap: 1.1rem; margin-bottom: 1.5rem;
        }
        .step-dica:last-child { margin-bottom: 0; }
        .step-dica .step-icone {
            width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 2rem; background: #e3f0ff; color: #0d6efd; box-shadow: 0 2px 8px #0d6efd22;
        }
        .step-dica .step-titulo { font-weight: 700; color: #0d6efd; font-size: 1.13em; margin-bottom: 0.2em; }
        .aula-item {
            background: #f8fafc;
            border: 2.5px solid #0d6efd;
            border-radius: 18px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .aula-item:hover {
            border-color: #4f8cff;
            box-shadow: 0 4px 24px rgba(13,110,253,0.13);
        }
        @media (max-width: 767px) {
            .head-historico { padding: 1.2rem 1rem; }
        }
    </style>
    <script>
    function limparFiltros() {
        document.querySelector('input[name=search]').value = '';
        document.querySelector('select[name=professor]').selectedIndex = 0;
        document.querySelector('select[name=turma]').selectedIndex = 0;
        document.querySelector('select[name=disciplina]').selectedIndex = 0;
        document.querySelectorAll('input[name=ordem]').forEach(r=>r.checked = r.value==='recentes');
        document.querySelector('form').submit();
    }
    </script>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container-fluid py-5">
    <div class="row mb-4">
        <div class="col-12">
            <div class="head-historico">
                <div class="row align-items-end g-2 mb-2">
                    <div class="col-lg-6 col-md-6 col-12">
                        <div class="d-flex align-items-center gap-3 h-100">
                            <span class="icon"><i class="fa-solid fa-chalkboard"></i></span>
                            <div>
                                <h2 class="section-title mb-0">Histórico de Aulas Ministradas</h2>
                                <div class="section-desc mt-1">
                                    <i class="fa-solid fa-circle-info"></i> Veja as aulas ministradas por todos os professores no sistema.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-12 mt-3 mt-md-0">
                        <form class="d-flex align-items-end justify-content-end w-100" method="get" action="">
                            <div class="d-flex gap-2 w-100" style="max-width: 600px;">
                                <div class="input-group flex-nowrap" style="flex:2 1 0; min-width:0;">
                                    <span class="input-group-text bg-white border-end-0" style="border-radius:8px 0 0 8px;">
                                        <i class="fa-solid fa-search text-primary"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0 border-end-0" name="search"
                                        placeholder="Pesquisar aula, disciplina, turma..." value="<?= htmlspecialchars($search) ?>"
                                        style="border-radius:0; box-shadow:none;">
                                    <?php if ($search !== ''): ?>
                                    <button type="submit" class="btn btn-outline-secondary border-start-0 border-end-0"
                                        style="border-radius:0;" tabindex="-1"
                                        onclick="this.form.search.value=''; this.form.submit(); return false;"
                                        title="Limpar pesquisa">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button class="btn btn-primary" type="submit" style="border-radius:0 8px 8px 0;">
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </button>
                                </div>
                                <button class="btn btn-gradient-primary dropdown-toggle fw-bold shadow-sm px-3 py-2 btn-historico-aulas"
                                    type="button" id="dropdownFiltros" data-bs-toggle="dropdown" aria-expanded="false"
                                    style="border-radius: 12px; background: linear-gradient(90deg,#0d6efd 60%,#4f8cff 100%); color: #fff; border: none; min-width: 110px; max-width: 110px;">
                                    <i class="bi bi-funnel-fill me-1"></i> Filtros
                                </button>
                                <button type="button" class="btn btn-gradient-dicas shadow-sm px-3 py-2 d-flex align-items-center gap-2 fw-bold btn-historico-aulas" onclick="abrirModalDicasAulas()"  title="Dicas da página" style="border-radius: 14px; font-size:1.13em; box-shadow: 0 2px 8px #0d6efd33; min-width: 110px; max-width: 110px;">
                                    <i class="bi bi-lightbulb-fill" style="font-size:1.35em;"></i>
                                    Dicas
                                </button>
                            </div>
                            <div class="dropdown-menu p-4 shadow-lg border-0"
                                style="min-width: 320px; border-radius: 18px; background: #f8faff;" onclick="event.stopPropagation();">
                                    <div class="mb-3">
                                        <label class="form-label mb-2 fw-semibold text-primary">
                                            <i class="fa-solid fa-sort me-1"></i>Ordenar:
                                        </label>
                                        <div class="btn-group w-100" role="group" aria-label="Ordenar">
                                            <?php
                                                $ordem_opts = [
                                                    'recentes' => ['icon' => 'fa-clock-rotate-left', 'label' => 'Mais recentes'],
                                                    'antigas' => ['icon' => 'fa-clock', 'label' => 'Mais antigas'],
                                                    'az' => ['icon' => 'fa-arrow-down-a-z', 'label' => 'A-Z'],
                                                    'za' => ['icon' => 'fa-arrow-down-z-a', 'label' => 'Z-A']
                                                ];
                                            ?>
                                            <?php foreach ($ordem_opts as $key => $info): ?>
                                            <input type="radio" class="btn-check" name="ordem" id="ordem_<?= $key ?>"
                                                value="<?= $key ?>" autocomplete="off" <?= $ordem==$key?'checked':'' ?>>
                                            <label class="btn btn-outline-primary" for="ordem_<?= $key ?>"
                                                style="min-width:90px;">
                                                <i class="fa <?= $info['icon'] ?>"></i> <?= $info['label'] ?>
                                            </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label mb-2 fw-semibold text-primary">
                                            <i class="fa-solid fa-chalkboard-user me-1"></i>Professor:
                                        </label>
                                        <select class="form-select" name="professor" style="border-radius:8px;">
                                            <option value="">Todos</option>
                                            <?php foreach ($professores as $prof): ?>
                                            <option value="<?= htmlspecialchars($prof) ?>" <?= $professor_filtro===$prof?'selected':'' ?>><?= htmlspecialchars($prof) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label mb-2 fw-semibold text-primary">
                                            <i class="fa-solid fa-users me-1"></i>Turma:
                                        </label>
                                        <select class="form-select" name="turma" style="border-radius:8px;">
                                            <option value="">Todas</option>
                                            <?php foreach ($turmas as $turma): ?>
                                            <option value="<?= htmlspecialchars($turma) ?>" <?= $turma_filtro===$turma?'selected':'' ?>><?= htmlspecialchars($turma) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label mb-2 fw-semibold text-primary">
                                            <i class="fa-solid fa-calendar-day me-1"></i>Data da Aula:
                                        </label>
                                        <input type="text" class="form-control" id="filtroDataAula" name="data_aula" placeholder="Escolha uma data" style="border-radius:8px; background:#fff;" readonly value="<?= isset($_GET['data_aula']) ? htmlspecialchars($_GET['data_aula']) : '' ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label mb-2 fw-semibold text-primary">
                                            <i class="fa-solid fa-book me-1"></i>Disciplina:
                                        </label>
                                        <select class="form-select" name="disciplina" style="border-radius:8px;">
                                            <option value="">Todas</option>
                                            <?php foreach ($disciplinas as $disc): ?>
                                            <option value="<?= htmlspecialchars($disc) ?>" <?= $disciplina_filtro===$disc?'selected':'' ?>><?= htmlspecialchars($disc) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-gradient-primary w-100 mt-2 fw-bold"
                                            style="border-radius: 8px; background: linear-gradient(90deg,#0d6efd 60%,#4f8cff 100%); color: #fff; border: none;">
                                            <i class="fa-solid fa-filter"></i> Salvar Filtros
                                        </button>
                                        <button type="button" onclick="limparFiltros()" class="btn btn-outline-secondary w-100 mt-2 fw-bold" style="border-radius: 8px;">
                                            <i class="fa-solid fa-eraser"></i> Limpar Filtros
                                        </button>
                                    </div>
                                </div>
                                                             </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-12">
            <?php if ($total_paginas > 1): ?>
            <nav aria-label="Navegação de páginas" class="mb-3">
                <ul class="pagination justify-content-end mb-0">
                    <li class="page-item<?= $pagina == 1 ? ' disabled' : '' ?>">
                        <a class="page-link" href="?page=1" title="Primeira">&laquo;</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <li class="page-item<?= $i == $pagina ? ' active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>$i])) ?>"> <?= $i ?> </a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item<?= $pagina == $total_paginas ? ' disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $total_paginas ?>" title="Última">&raquo;</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
    <div class="row g-4">
        <?php if ($aulas): ?>
            <?php foreach ($aulas as $aula): ?>
                <div class="col-12 col-md-6">
                    <div class="list-group-item aula-item mb-4 shadow-sm rounded-4 border-0 p-4">
                        <div class="d-flex flex-wrap align-items-center mb-3 gap-2">
                            <span class="badge bg-primary fs-6 px-3 py-2"><i class="fa-solid fa-calendar-day me-1"></i> <?= date('d/m/Y', strtotime($aula['data'])) ?></span>
                            <span class="badge bg-info text-dark fs-6 px-3 py-2"><i class="fa-solid fa-users me-1"></i>Turma: <?= htmlspecialchars($aula['turma_nome']) ?></span>
                            <span class="badge bg-success fs-6 px-3 py-2"><i class="fa-solid fa-book me-1"></i>Disciplina: <?= htmlspecialchars($aula['disciplina_nome']) ?></span>
                            <span class="badge bg-secondary fs-6 px-3 py-2"><i class="fa-solid fa-chalkboard-user me-1"></i>Prof: <?= htmlspecialchars($aula['professor_nome']) ?></span>
                        </div>
                        <?php if (!empty($topicos_aula[$aula['id']])): ?>
                        <div class="mb-2">
                            <div class="fw-bold text-primary mb-1" style="font-size:1.08em;"><i class="fa-solid fa-list-check me-1"></i>Tópicos ministrados:</div>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($topicos_aula[$aula['id']] as $topico): ?>
                                    <span class="badge bg-primary text-white px-3 py-2 rounded-pill fs-6"><i class="fa-solid fa-check me-1"></i><?= htmlspecialchars($topico) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($topicos_personalizados_aula[$aula['id']])): ?>
                        <div class="mb-2">
                            <div class="fw-bold text-warning mb-1" style="font-size:1.08em;"><i class="fa-solid fa-pen-nib me-1"></i>Tópicos personalizados:</div>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($topicos_personalizados_aula[$aula['id']] as $topico): ?>
                                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fs-6"><i class="fa-solid fa-star me-1"></i><?= htmlspecialchars($topico) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($aula['comentario'])): ?>
                        <div class="mt-2">
                            <div class="fw-bold text-info mb-1" style="font-size:1.08em;"><i class="fa-solid fa-comment-dots me-1"></i>Observações:</div>
                            <div class="alert alert-info mb-0 py-2 px-3 rounded-3" style="font-size:1.08em;"> <?= nl2br(htmlspecialchars($aula['comentario'])) ?> </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12"><div class="list-group-item text-muted"><i class="fa-solid fa-circle-exclamation me-1"></i>Nenhuma aula registrada.</div></div>
        <?php endif; ?>
    </div>
</div>
<!-- Modal de Dicas de Funcionamento (estilo Disciplinas) -->
<div id="modalDicasAulas" style="display:none;position:fixed;z-index:2100;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.45);">
    <div class="modal-dicas-content" style="max-width:760px;min-width:420px;width:98vw;">
        <div class="modal-dicas-header">
            <div class="modal-dicas-icone">
                <i class="bi bi-lightbulb-fill"></i>
            </div>
            <h4 class="mb-0 text-white">Dicas do Histórico de Aulas</h4>
            <span onclick="fecharModalDicasAulas()" class="modal-dicas-close">&times;</span>
        </div>
        <div class="modal-dicas-body">
            <div id="stepperDicasAulas" class="mb-4">
                <div class="d-flex justify-content-center align-items-center gap-2 mb-3">
                    <span class="step-circle" id="stepCircleAulas1"><i class="bi"></i></span>
                    <span class="step-line"></span>
                    <span class="step-circle" id="stepCircleAulas2"><i class="bi"></i></span>
                    <span class="step-line"></span>
                    <span class="step-circle" id="stepCircleAulas3"><i class="bi"></i></span>
                    <span class="step-line"></span>
                    <span class="step-circle" id="stepCircleAulas4"><i class="bi"></i></span>
                </div>
            </div>
            <div id="stepContentDicasAulas"></div>
        </div>
        <div class="modal-dicas-footer">
            <button class="btn btn-outline-primary" id="btnStepAnteriorAulas" style="display:none;"><i class="bi bi-arrow-left"></i> Anterior</button>
            <button class="btn btn-outline-primary ms-3" id="btnStepProximoAulas">Próximo <i class="bi bi-arrow-right"></i></button>
        </div>
    </div>
    <style>
    .modal-dicas-content {
        background: #fff;
        border-radius: 22px;
        max-width: 540px;
        width: 96vw;
        min-width: 340px;
        min-height: 320px;
        max-height: 80vh;
        margin: 60px auto;
        position: relative;
        box-shadow: 0 8px 32px rgba(0,0,0,0.18);
        overflow-y: auto;
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
    #modalDicasAulas .step-circle {
        width: 32px; height: 32px; border-radius: 50%; background: #e3e9f7; color: #0d6efd; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.15em; border: 2px solid #b6c6e6;
        transition: background 0.2s, color 0.2s;
    }
    #modalDicasAulas .step-circle.active {
        background: #0d6efd; color: #fff; border-color: #0d6efd;
    }
    #modalDicasAulas .step-line {
        flex: 1 1 0; height: 3px; background: #b6c6e6;
    }
    #stepContentDicasAulas {
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
<script>
const stepsDicasAulas = [
    {
        title: 'Filtros e Pesquisa',
        html: `<div class=\"dica-step-card bg-dica-blue\"><div class=\"dica-step-icone dica-blue\"><i class=\"bi bi-funnel-fill\"></i></div><div><span class=\"fw-bold text-dica-blue\">Filtros:</span> Use o botão <span class=\"badge bg-primary text-white\"><i class=\"bi bi-funnel-fill\"></i></span> para abrir filtros avançados.<br><span class=\"fw-bold text-dica-blue\">Pesquisa:</span> Digite no campo para buscar aulas por disciplina, turma, professor, data ou observação.<br><span class=\"fw-bold text-dica-blue\">Ordenação:</span> Escolha como deseja ordenar as aulas.<br><span class=\"fw-bold text-dica-blue\">Status:</span> Combine filtros para refinar sua busca.</div></div>`
    },
    {
        title: 'Visualização dos Cards',
        html: `<div class=\"dica-step-card bg-dica-purple\"><div class=\"dica-step-icone dica-purple\"><i class=\"bi bi-collection\"></i></div><div><span class=\"fw-bold text-dica-purple\">Cards:</span> Cada card mostra turma, disciplina, professor, tópicos ministrados, tópicos personalizados e observações.<br><span class=\"fw-bold text-dica-purple\">Responsivo:</span> Os cards aparecem em duas colunas em telas grandes e em uma coluna no mobile.</div></div>`
    },
    {
        title: 'Navegação e Paginação',
        html: `<div class=\"dica-step-card bg-dica-green\"><div class=\"dica-step-icone dica-green\"><i class=\"bi bi-arrows-angle-expand\"></i></div><div><span class=\"fw-bold text-dica-green\">Paginação:</span> Use os botões de navegação para trocar de página.<br><span class=\"fw-bold text-dica-green\">Total de aulas:</span> O número de aulas exibidas por página é otimizado para facilitar a visualização.</div></div>`
    },
    {
        title: 'Dicas Extras',
        html: `<div class=\"dica-step-card bg-dica-yellow\"><div class=\"dica-step-icone dica-yellow\"><i class=\"bi bi-lightbulb-fill\"></i></div><div><ul class=\"mb-0 ps-3\"><li>Clique em <b>Limpar Filtros</b> para visualizar todas as aulas novamente.</li><li>Você pode combinar filtros para refinar ainda mais sua busca.</li><li>O sistema é responsivo e funciona bem em qualquer dispositivo.</li></ul></div></div>`
    }
];
let stepAtualAulas = 0;
function mostrarStepDicasAulas(idx) {
    stepAtualAulas = idx;
    let icones = [
        'bi-funnel-fill',
        'bi-collection',
        'bi-arrows-angle-expand',
        'bi-lightbulb-fill'
    ];
    for (let i = 0; i < 4; i++) {
        let el = document.getElementById('stepCircleAulas'+(i+1));
        if (el) {
            el.classList.toggle('active', i === idx);
            let icon = el.querySelector('i');
            if (icon) {
                icon.className = 'bi ' + (icones[i] || '');
                icon.style.opacity = i < stepsDicasAulas.length ? 1 : 0;
                icon.style.fontSize = '1.25em';
            }
        }
        let line = el && el.nextElementSibling && el.nextElementSibling.classList.contains('step-line') ? el.nextElementSibling : null;
        if (line) line.style.display = (i < stepsDicasAulas.length-1) ? '' : 'none';
    }
    document.getElementById('stepContentDicasAulas').innerHTML = `
        <h5 class='fw-bold mb-3 text-primary'>${stepsDicasAulas[idx].title}</h5>
        <div style='font-size:1.13em;'>${stepsDicasAulas[idx].html}</div>
    `;
    document.getElementById('btnStepAnteriorAulas').style.display = idx === 0 ? 'none' : '';
    document.getElementById('btnStepProximoAulas').innerHTML = idx === stepsDicasAulas.length-1 ? 'Fechar <i class="bi bi-x"></i>' : 'Próximo <i class="bi bi-arrow-right"></i>';
}
function abrirModalDicasAulas() {
    document.getElementById('modalDicasAulas').style.display = 'block';
    mostrarStepDicasAulas(0);
}
function fecharModalDicasAulas() {
    document.getElementById('modalDicasAulas').style.display = 'none';
}
document.addEventListener('DOMContentLoaded', function() {
    var btnAnterior = document.getElementById('btnStepAnteriorAulas');
    if (btnAnterior) {
        btnAnterior.onclick = function() { if (stepAtualAulas > 0) mostrarStepDicasAulas(stepAtualAulas-1); };
    }
    var btnProximo = document.getElementById('btnStepProximoAulas');
    if (btnProximo) {
        btnProximo.onclick = function() {
            if (stepAtualAulas < stepsDicasAulas.length-1) mostrarStepDicasAulas(stepAtualAulas+1);
            else fecharModalDicasAulas();
        };
    }
    document.addEventListener('keydown', function(e) {
        var modalDicasAulas = document.getElementById('modalDicasAulas');
        if (modalDicasAulas && modalDicasAulas.style.display === 'block' && e.key === 'Escape') fecharModalDicasAulas();
    });
});

// Steps do modal de dicas
(function() {
  let step = 1;
  const total = 4;
  function showStep(n) {
    document.querySelectorAll('#stepsDicasAulas .step-dica').forEach((el, i) => {
      el.classList.toggle('d-none', i !== n-1);
    });
    var elStepAtual = document.getElementById('stepAtual');
    if (elStepAtual) elStepAtual.textContent = n;
    document.getElementById('stepPrev').disabled = n === 1;
    document.getElementById('stepNext').disabled = n === total;
    document.getElementById('stepNext').textContent = n === total ? 'Finalizar' : 'Próximo ';
    if (n === total) document.getElementById('stepNext').innerHTML = 'Finalizar <i class="fa-solid fa-check"></i>';
    else document.getElementById('stepNext').innerHTML = 'Próximo <i class="fa-solid fa-arrow-right"></i>';
  }
  document.addEventListener('DOMContentLoaded', function() {
    showStep(step);
    document.getElementById('stepPrev').onclick = function() { if (step > 1) showStep(--step); };
    document.getElementById('stepNext').onclick = function() {
      if (step < total) showStep(++step);
      else document.querySelector('#modalDicas .btn-close').click();
    };
    var modalDicasAulas = document.getElementById('modalDicasAulas');
    if (modalDicasAulas) {
        // Se quiser adicionar algum evento customizado ao abrir o modal customizado, faça aqui
        // Exemplo: modalDicasAulas.addEventListener('show', function() { ... });
    }
  });
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php
// Coletar todas as datas de aulas registradas (formato Y-m-d)
$datas_aulas_registradas = [];
$res_datas = $conn->query("SELECT DISTINCT data FROM aulas");
while ($row = $res_datas->fetch_assoc()) {
    $datas_aulas_registradas[] = $row['data'];
}
?>
<script>
var datasAulasRegistradas = <?= json_encode($datas_aulas_registradas) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
<script>
flatpickr("#filtroDataAula", {
    dateFormat: "Y-m-d",
    enable: datasAulasRegistradas,
    locale: "pt"
});
</script>
<?php include 'footer.php'; ?>
</body>
</html>
