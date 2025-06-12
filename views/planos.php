<?php
session_start();
if (!isset($_SESSION['usuario_nome']) || !in_array($_SESSION['usuario_tipo'], ['coordenador', 'admin', 'professor'])) {
    header('Location: index.php');
    exit();
}
include 'navbar.php';
include 'notificacao.php';
require_once '../config/conexao.php';

$turma_id = isset($_GET['turma_id']) ? intval($_GET['turma_id']) : null;
$turma_nome = '';
if ($turma_id) {
    $stmt = $conn->prepare('SELECT nome FROM turmas WHERE id = ?');
    $stmt->bind_param('i', $turma_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) $turma_nome = $row['nome'];
    $stmt->close();
}

// Buscar disciplinas para o select e para a turma
$disciplinas = [];
if ($turma_id) {
    $sql = 'SELECT d.id, d.nome FROM turma_disciplinas td JOIN disciplinas d ON td.disciplina_id = d.id WHERE td.turma_id = ? ORDER BY d.nome';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $turma_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $disciplinas[] = $row;
    }
    $stmt->close();
} else {
    $sql = 'SELECT id, nome FROM disciplinas ORDER BY nome';
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $disciplinas[] = $row;
        }
    }
}

// Buscar planos de aula
$planos = [];
if ($turma_id) {
    $sql = 'SELECT p.*, d.nome AS disciplina_nome FROM planos p JOIN disciplinas d ON p.disciplina_id = d.id WHERE p.turma_id = ? ORDER BY d.nome';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $turma_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $planos[$row['disciplina_id']] = $row;
    }
    $stmt->close();
} else {
    $sql = 'SELECT p.*, d.nome AS disciplina_nome FROM planos p JOIN disciplinas d ON p.disciplina_id = d.id ORDER BY p.criado_em DESC';
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $planos[] = $row;
        }
    }
}

// Buscar capítulos e tópicos de todos os planos exibidos
$capitulosPorPlano = [];
$topicosPorCapitulo = [];
if (!empty($planos)) {
    $ids = [];
    if ($turma_id) {
        foreach ($planos as $plano) $ids[] = $plano['id'];
    } else {
        foreach ($planos as $plano) $ids[] = $plano['id'];
    }
    if ($ids) {
        $in = implode(',', array_map('intval', $ids));
        // Buscar capítulos
        $sql = "SELECT * FROM capitulos WHERE plano_id IN ($in) ORDER BY plano_id, ordem ASC, id ASC";
        $result = $conn->query($sql);
        $cap_ids = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $capitulosPorPlano[$row['plano_id']][] = $row;
                $cap_ids[] = $row['id'];
            }
        }
        // Buscar tópicos
        if ($cap_ids) {
            $in_caps = implode(',', array_map('intval', $cap_ids));
            $sql = "SELECT * FROM topicos WHERE capitulo_id IN ($in_caps) ORDER BY capitulo_id, ordem ASC, id ASC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $topicosPorCapitulo[$row['capitulo_id']][] = $row;
                }
            }
        }
    }
}

// Buscar tópicos personalizados por plano (por disciplina/turma)
$topicosPersonalizadosPorPlano = [];
if ($turma_id && !empty($planos)) {
    foreach ($planos as $disc_id => $plano) {
        // Buscar aulas desse plano (disciplina/turma)
        $stmt = $conn->prepare("SELECT id FROM aulas WHERE turma_id = ? AND disciplina_id = ?");
        $stmt->bind_param('ii', $turma_id, $disc_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $aula_ids = [];
        while ($row = $res->fetch_assoc()) $aula_ids[] = $row['id'];
        $stmt->close();
        if ($aula_ids) {
            $in_aulas = implode(',', array_map('intval', $aula_ids));
            $sql = "SELECT descricao FROM topicos_personalizados WHERE aula_id IN ($in_aulas)";
            $result = $conn->query($sql);
            while ($row = $result->fetch_assoc()) {
                $topicosPersonalizadosPorPlano[$plano['id']][] = $row['descricao'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Planos de Aula - PI Page</title>
    <link rel="icon" type="image/png" href="../assets/img/LOGO_PAGE.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/turmas.css" rel="stylesheet">
</head>

<body>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
    // Exibir notificação se houver parâmetro na URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('sucesso')) {
        let msg = '';
        if (urlParams.get('sucesso') === 'plano_criado') msg = 'Plano de aula criado com sucesso!';
        if (urlParams.get('sucesso') === 'plano_editado') msg = 'Plano de aula editado com sucesso!';
        if (urlParams.get('sucesso') === 'plano_excluido') msg = 'Plano de aula excluído com sucesso!';
        if (msg) mostrarNotificacao(msg, 'success');
    }
    if (urlParams.has('erro')) {
        let msg = '';
        if (urlParams.get('erro') === 'plano_existente') msg = 'Já existe um plano com esse título para a disciplina!';
        if (urlParams.get('erro') === 'erro_banco') msg = 'Erro ao salvar no banco!';
        if (urlParams.get('erro') === 'dados_invalidos') msg = 'Dados inválidos!';
        if (msg) mostrarNotificacao(msg, 'danger');
    }
    </script>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="bg-white rounded shadow-sm p-4 mb-3 border border-3 border-primary position-relative">
                    <div class="row align-items-end g-2 mb-2">
                        <div class="col-lg-7 col-md-7 col-12">
                            <div class="d-flex align-items-center gap-3 h-100">
                                <span
                                    class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                                    style="width:56px;height:56px;font-size:2.2rem;box-shadow:0 2px 8px #0d6efd33;">
                                    <i class="bi bi-clipboard2-data"></i>
                                </span>
                                <div>
                                    <h2 class="mb-0 fw-bold text-primary">Planos de
                                        Aula<?= $turma_id ? ' <span class=\"text-primary\">da Turma: ' . htmlspecialchars($turma_nome) . '</span>' : '' ?>
                                    </h2>
                                    <div class="text-muted" style="font-size:1.08em;">
                                        <i class="bi bi-info-circle"></i>
                                        <?php if (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'professor'): ?>
                                        Gerencie e registre os planos de aula das suas disciplinas.
                                        <?php else: ?>
                                        Gerencie e visualize todos os planos de aula cadastrados no sistema.
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <?php if ($turma_id): ?>
                <!-- Abas de disciplinas -->
                <ul class="nav nav-tabs" id="disciplinasTab" role="tablist">
                    <?php foreach ($disciplinas as $i => $disc): ?>
                    <?php
                        $temPlano = isset($planos[$disc['id']]);
                    ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link<?= $i === 0 ? ' active' : '' ?>" id="tab-<?= $disc['id'] ?>"
                            data-bs-toggle="tab" data-bs-target="#disciplina-<?= $disc['id'] ?>" type="button"
                            role="tab" aria-controls="disciplina-<?= $disc['id'] ?>"
                            aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"
                            style="font-weight:600;font-size:1.1em;display:flex;align-items:center;gap:7px;position:relative;">
                            <i class="bi bi-journal-bookmark-fill text-primary"></i>
                            <?= htmlspecialchars($disc['nome']) ?>
                            <?php if (!$temPlano): ?>
                            <span class="ms-1 text-danger" title="Nenhum plano cadastrado para esta disciplina">
                                <i class="bi bi-exclamation-octagon-fill"></i>
                            </span>
                            <?php endif; ?>
                        </button>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="tab-content p-3 bg-white rounded-bottom shadow-sm border border-top-0"
                    id="disciplinasTabContent">
                    <?php foreach ($disciplinas as $i => $disc): ?>
                    <div class="tab-pane fade<?= $i === 0 ? ' show active' : '' ?>" id="disciplina-<?= $disc['id'] ?>"
                        role="tabpanel" aria-labelledby="tab-<?= $disc['id'] ?>">
                        <div class="col-12 ">
                            <!-- Card principal da disciplina -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <!-- Bloco do plano e capítulos dentro da aba/disciplina -->
                                <?php if (isset($planos[$disc['id']])): $plano = $planos[$disc['id']];
                                $capitulos = !empty($capitulosPorPlano[$plano['id']]) ? $capitulosPorPlano[$plano['id']] : [];
                                $totalCapitulos = count($capitulos);
                                $totalTopicos = 0;
                                foreach ($capitulos as $c) {
                                    $totalTopicos += isset($topicosPorCapitulo[$c['id']]) ? count($topicosPorCapitulo[$c['id']]) : 0;
                                }
                            ?>
                                <div>
                                   
                                    <span class="badge bg-info-subtle text-dark border border-info"><i
                                            class="bi bi-journal-text"></i> Plano:
                                        <b><?= htmlspecialchars($plano['titulo']) ?></b></span>
                                    <span class="badge bg-light text-dark border border-secondary"><i
                                            class="bi bi-calendar2-week"></i> Criado em:
                                        <?= isset($plano['criado_em']) ? date('d/m/Y', strtotime($plano['criado_em'])) : '-' ?></span>
                                    <span
                                        class="badge <?= $plano['status'] === 'concluido' ? 'bg-success' : 'bg-warning text-dark' ?> border border-<?= $plano['status'] === 'concluido' ? 'success' : 'warning' ?>"><i
                                            class="bi bi-activity"></i>
                                        <?= $plano['status'] === 'concluido' ? 'Concluído' : 'Em andamento' ?></span>
                                    <span class="badge bg-primary-subtle text-primary border border-primary">
                                        <i class="bi bi-journal-bookmark-fill"></i>
                                        <?= $totalCapitulos ?> capítulo<?= $totalCapitulos == 1 ? '' : 's' ?>
                                    </span>
                                    <span class="badge bg-info-subtle text-info border border-info">
                                        <i class="bi bi-list-task"></i>
                                        <?= $totalTopicos ?> tópico<?= $totalTopicos == 1 ? '' : 's' ?>
                                    </span>
                                    <?php if ($totalCapitulos > 0): ?>
                                    <div class="mb-2 d-flex flex-wrap gap-2 align-items-center">
                                        <span class="badge bg-secondary-subtle text-dark border border-secondary">
                                            <i class="bi bi-eye"></i>
                                            Capítulo <span id="capitulo-atual-<?= $plano['id'] ?>">1</span> de
                                            <?= $totalCapitulos ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="turma-actions">
                                    <?php if (isset($planos[$disc['id']])): $plano = $planos[$disc['id']]; ?>
                                    <button class="btn btn-success btn-sm"
                                        onclick="abrirModalCapitulo(<?= $plano['id'] ?>)"><i
                                            class="bi bi-plus-circle"></i> Adicionar Capítulo</button>
                                    <button class="btn btn-primary btn-sm" title="Editar Plano"
                                        onclick="abrirModalEditarPlano(<?= $plano['id'] ?>, '<?= htmlspecialchars(addslashes($plano['titulo'])) ?>', <?= $plano['disciplina_id'] ?>, '<?= htmlspecialchars(addslashes($plano['descricao'])) ?>', '<?= $plano['status'] ?>', <?= $plano['turma_id'] ?>, '<?= $plano['data_inicio'] ?>', '<?= $plano['data_fim'] ?>', '<?= htmlspecialchars(addslashes($plano['objetivo_geral'])) ?>')">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" title="Excluir Plano"
                                        onclick="abrirModalExcluirPlano(<?= $plano['id'] ?>, '<?= htmlspecialchars(addslashes($plano['titulo'])) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Cards dos capítulos -->
                            <?php if ($totalCapitulos > 0): ?>
                            <!-- Carrossel de capítulos -->
                            <div id="capitulosCarousel-<?= $plano['id'] ?>" class="carousel slide" data-bs-ride="false"
                                data-bs-interval="false" data-bs-pause="hover" style="position:relative;">
                                <!-- Indicadores (bolinhas) -->
                                <?php if ($totalCapitulos > 1): ?>
                                <div class="carousel-indicators" style="bottom:-30px; z-index:3;">
                                    <?php foreach ($capitulos as $idx => $cap): ?>
                                    <button type="button" data-bs-target="#capitulosCarousel-<?= $plano['id'] ?>"
                                        data-bs-slide-to="<?= $idx ?>" class="<?= $idx === 0 ? 'active' : '' ?>"
                                        aria-current="<?= $idx === 0 ? 'true' : 'false' ?>"
                                        aria-label="Capítulo <?= $idx+1 ?>"
                                        style="width:12px;height:12px;border-radius:50%;background:#0d6efd;border:1px solid #fff;opacity:<?= $idx === 0 ? '1' : '0.5' ?>;"></button>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                <div class="carousel-inner">
                                    <?php foreach ($capitulos as $idx => $cap): ?>
                                    <div class="carousel-item<?= $idx === 0 ? ' active' : '' ?>">
                                        <div class="row g-4 justify-content-center">
                                            <div class="col-12 col-md-11 col-xl-10 capitulo-card">
                                                <?php
                                                $capStatus = $cap['status'];
                                                $capClass = '';
                                                $capMsg = '';
                                                if ($capStatus === 'cancelado') {
                                                    $capClass = 'bg-secondary text-white position-relative';
                                                    $capMsg = '<div class="cap-status-msg text-center fw-bold py-2" style="background:#6c757d;color:#fff;border-radius:10px 10px 0 0;">Cancelado</div>';
                                                } elseif ($capStatus === 'concluido') {
                                                    $capClass = 'bg-secondary-subtle text-dark position-relative';
                                                    $capMsg = '<div class="cap-status-msg text-center fw-bold py-2" style="background:#adb5bd;color:#222;border-radius:10px 10px 0 0;">Concluído</div>';
                                                }
                                                ?>
                                                <div class="card card-turma h-100 <?= $capClass ?>" style="border-radius:18px; position:relative;">
                                                    <?php if ($capStatus === 'cancelado' || $capStatus === 'concluido'): ?>
                                                        <div class="status-overlay d-flex flex-column justify-content-center align-items-center" style="position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(108,117,125,0.85);z-index:10;border-radius:18px;color:#fff;font-size:1.5em;font-weight:bold;text-shadow:0 2px 8px #000;">
                                                            <i class="bi <?= $capStatus === 'cancelado' ? 'bi-x-circle-fill' : 'bi-check-circle-fill' ?> mb-2" style="font-size:2.5em;"></i>
                                                            <?= $capStatus === 'cancelado' ? 'Capítulo Cancelado' : 'Capítulo Concluído' ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="card-body d-flex flex-column <?= ($capStatus === 'cancelado' || $capStatus === 'concluido') ? 'opacity-50' : '' ?>" style="position:relative;">
                                                        <div class="mb-2 d-flex flex-wrap gap-3 align-items-center"
                                                            style="font-size:1.25rem;">
                                                            <span
                                                                class="badge bg-info-subtle text-dark border border-info"
                                                                style="font-size:1.18rem;">
                                                                <i class="bi bi-journal-bookmark-fill text-primary"></i>
                                                                Capítulo: <b><?= htmlspecialchars($cap['titulo']) ?></b>
                                                            </span>
                                                            <span class="badge bg-secondary" style="font-size:1.13rem;">
                                                                <i class="bi bi-list-ol"></i> Ordem:
                                                                <?= $cap['ordem'] ?>
                                                            </span>
                                                            <span class="badge 
                                                                <?php
                                                                    if ($cap['status'] === 'concluido') echo 'bg-success';
                                                                    elseif ($cap['status'] === 'cancelado') echo 'bg-secondary';
                                                                    else echo 'bg-info text-dark';
                                                                ?>" style="font-size:1.13rem;">
                                                                <i class="bi bi-activity"></i> <?= $cap['status'] ?>
                                                            </span>
                                                            <div class="ms-auto d-flex gap-2" style="position:relative;z-index:20;<?= ($capStatus === 'cancelado' || $capStatus === 'concluido') ? 'pointer-events:auto;' : '' ?>">
                                                                <button class="btn btn-primary btn-sm"
                                                                    title="Editar Capítulo" style="font-size:1.15rem;"
                                                                    onclick="abrirModalEditarCapitulo(<?= $cap['id'] ?>, '<?= htmlspecialchars(addslashes($cap['titulo'])) ?>', <?= $cap['ordem'] ?>, '<?= $cap['status'] ?>', '<?= htmlspecialchars(addslashes($cap['descricao'])) ?>', <?= $cap['duracao_estimativa'] ? $cap['duracao_estimativa'] : 'null' ?>)">
                                                                    <i class="bi bi-pencil-square"></i>
                                                                </button>
                                                                <button class="btn btn-danger btn-sm"
                                                                    title="Excluir Capítulo" style="font-size:1.15rem;"
                                                                    onclick="abrirModalExcluirCapitulo(<?= $cap['id'] ?>, '<?= htmlspecialchars(addslashes($cap['titulo'])) ?>')">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                                <button class="btn btn-success btn-sm"
                                                                    title="Adicionar Tópico" style="font-size:1.15rem;"
                                                                    onclick="abrirModalTopico(<?= $cap['id'] ?>)">
                                                                    <i class="bi bi-plus-circle"></i>Adicionar Tópico
                                                                </button>
                                                                <?php if ($cap['status'] !== 'concluido'): ?>
                                                                <button class="btn btn-outline-secondary btn-sm"
                                                                    title="Ativar/Cancelar Capítulo"
                                                                    style="font-size:1.15rem;"
                                                                    onclick="abrirModalToggleCapitulo(<?= $cap['id'] ?>, '<?= addslashes($cap['titulo']) ?>', '<?= $cap['status'] ?>', this)">
                                                                    <i class="bi <?= $cap['status'] === 'cancelado' ? 'bi-toggle-on' : 'bi-toggle-off' ?>"></i>
                                                                </button>
                                                                <?php endif; ?>
                                                                <?php if ($cap['status'] === 'cancelado'): ?>
                                                                <button class="btn btn-outline-secondary btn-sm"
                                                                    title="Ativar Capítulo"
                                                                    style="font-size:1.15rem;"
                                                                    onclick="abrirModalToggleCapitulo(<?= $cap['id'] ?>, '<?= addslashes($cap['titulo']) ?>', '<?= $cap['status'] ?>', this)">
                                                                    <i class="bi bi-toggle-on"></i>
                                                                </button>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <div
                                                                style="background: linear-gradient(90deg, #e3f0ff 80%, #f8fafc 100%); border: 1.5px solid #b6d4fe; border-radius: 12px; box-shadow: 0 2px 8px #0d6efd11; padding: 10px 16px; display: flex; align-items: center; gap: 10px; min-height: 38px;">
                                                                <i class="bi bi-card-text text-primary"
                                                                    style="font-size:1.15rem;"></i>
                                                                <span
                                                                    style="color:#222; font-size:1.15rem; line-height:1.5; white-space:pre-line; word-break:break-word;">
                                                                    <?= nl2br(htmlspecialchars($cap['descricao'])) ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="ms-3">
                                                            <?php if (!empty($topicosPorCapitulo[$cap['id']])): foreach ($topicosPorCapitulo[$cap['id']] as $top): ?>
                                                            <?php
                                                            $topStatus = $top['status'];
                                                            $topClass = '';
                                                            if ($topStatus === 'cancelado' || $topStatus === 'concluido') {
                                                                $topClass = 'position-relative';
                                                            }
                                                            ?>
                                                            <div class="mb-3 p-3 rounded shadow-sm <?= $topClass ?>" style="background:linear-gradient(90deg,#f8fafc 80%,#e3f0ff 100%);border:1.5px solid #e3e6ea;position:relative;font-size:1.13rem;">
                                                                <?php if ($topStatus === 'cancelado' || $topStatus === 'concluido'): ?>
                                                                    <div class="status-overlay d-flex flex-column justify-content-center align-items-center" style="position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(108,117,125,0.85);z-index:10;border-radius:12px;color:#fff;font-size:1.2em;font-weight:bold;text-shadow:0 2px 8px #000;">
                                                                        <i class="bi <?= $topStatus === 'cancelado' ? 'bi-x-circle-fill' : 'bi-check-circle-fill' ?> mb-2" style="font-size:2em;"></i>
                                                                        <?= $topStatus === 'cancelado' ? 'Tópico Cancelado' : 'Tópico Concluído' ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <div class="<?= ($topStatus === 'cancelado' || $topStatus === 'concluido') ? 'opacity-50' : '' ?>">
                                                                    <div class="d-flex align-items-center mb-1">
                                                                        <i class="bi bi-dot fs-4 text-primary me-1"></i>
                                                                        <span class="fw-bold fs-5 text-primary"
                                                                            style="font-size:1.18rem;">
                                                                            <i
                                                                                class="bi bi-lightbulb-fill text-warning"></i>
                                                                            <?= htmlspecialchars($top['titulo']) ?>
                                                                        </span>
                                                                        <span class="badge bg-secondary ms-2"
                                                                            style="font-size:1.08rem;">
                                                                            <i class="bi bi-list-ol"></i> Ordem:
                                                                            <?= $top['ordem'] ?>
                                                                        </span>
                                                                        <span class="badge 
                                                                            <?php
                                                                                if ($top['status'] === 'concluido') echo 'bg-success';
                                                                                elseif ($top['status'] === 'cancelado') echo 'bg-secondary';
                                                                                elseif ($top['status'] === 'pendente') echo 'bg-warning text-dark';
                                                                                else echo 'bg-info text-dark';
                                                                            ?>" style="font-size:1.08rem;">
                                                                            <i class="bi bi-activity"></i>
                                                                            <?= $top['status'] ?>
                                                                        </span>
                                                                        <span
                                                                            class="badge bg-light text-muted border border-secondary"
                                                                            style="font-size:1.08rem;">
                                                                            <i class="bi bi-calendar-event"></i>
                                                                            <?= date('d/m/Y', strtotime($top['data_criacao'])) ?>
                                                                        </span>
                                                                        <div class="ms-auto d-flex gap-2" style="position:relative;z-index:20;<?= ($topStatus === 'cancelado' || $topStatus === 'concluido') ? 'pointer-events:auto;' : '' ?>">
                                                                            <button class="btn btn-primary btn-sm"
                                                                                title="Editar Tópico"
                                                                                style="font-size:1.08rem;"
                                                                                onclick="abrirModalEditarTopico(<?= $top['id'] ?>, <?= $cap['id'] ?>, '<?= htmlspecialchars(addslashes($top['titulo'])) ?>', '<?= htmlspecialchars(addslashes($top['descricao'])) ?>', '<?= $top['status'] ?>', '<?= htmlspecialchars(addslashes($top['observacoes'] ?? '')) ?>')">
                                                                                <i class="bi bi-pencil-square"></i>
                                                                            </button>
                                                                            <button class="btn btn-danger btn-sm"
                                                                                title="Excluir Tópico"
                                                                                style="font-size:1.08rem;"
                                                                                onclick="abrirModalExcluirTopico(<?= $top['id'] ?>, '<?= htmlspecialchars(addslashes($top['titulo'])) ?>')">
                                                                                <i class="bi bi-trash"></i>
                                                                            </button>
                                                                            <?php if ($top['status'] !== 'concluido'): ?>
                                                                            <button class="btn btn-outline-secondary btn-sm"
                                                                                title="Ativar/Cancelar Tópico"
                                                                                style="font-size:1.08rem;"
                                                                                onclick="abrirModalToggleTopico(<?= $top['id'] ?>, '<?= addslashes($top['titulo']) ?>', '<?= $top['status'] ?>', this)">
                                                                                <i class="bi <?= $top['status'] === 'cancelado' ? 'bi-toggle-on' : 'bi-toggle-off' ?>"></i>
                                                                            </button>
                                                                            <?php endif; ?>
                                                                            <?php if ($top['status'] === 'cancelado'): ?>
                                                                            <button class="btn btn-outline-secondary btn-sm"
                                                                                title="Ativar Tópico"
                                                                                style="font-size:1.08rem;"
                                                                                onclick="abrirModalToggleTopico(<?= $top['id'] ?>, '<?= addslashes($top['titulo']) ?>', '<?= $top['status'] ?>', this)">
                                                                                <i class="bi bi-toggle-on"></i>
                                                                            </button>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                    <div class="mt-1 mb-1 p-2 rounded"
                                                                        style="background:#fffbe6;border:1px solid #ffe58f;font-size:1.08rem;">
                                                                        <i class="bi bi-info-circle text-warning"></i>
                                                                        <span
                                                                            class="text-dark"><?= nl2br(htmlspecialchars($top['descricao'])) ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <?php endforeach; else: ?>
                                                            <div class="d-flex justify-content-center align-items-center"
                                                                style="min-height:120px;">
                                                                <div class="w-100" style="max-width:420px;">
                                                                    <div class="p-3 rounded-4 shadow-sm border border-2 border-warning bg-white text-center"
                                                                        style="background:linear-gradient(90deg,#fff 80%,#fffbe6 100%);">
                                                                        <div class="mb-2">
                                                                            <i class="bi bi-exclamation-circle text-warning"
                                                                                style="font-size:2em;"></i>
                                                                        </div>
                                                                        <div class="fw-bold text-warning mb-1"
                                                                            style="font-size:1.13em;">
                                                                            Nenhum tópico cadastrado neste capítulo!
                                                                        </div>
                                                                        <div class="text-muted" style="font-size:1em;">
                                                                            Clique em <span
                                                                                class="badge bg-success text-white"><i
                                                                                    class="bi bi-plus-circle"></i>
                                                                                Adicionar Tópico</span> para cadastrar o
                                                                            primeiro tópico deste capítulo.
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php if ($totalCapitulos > 1): ?>
                                <button class="carousel-control-prev" type="button"
                                    data-bs-target="#capitulosCarousel-<?= $plano['id'] ?>" data-bs-slide="prev"
                                    style="width:48px;height:48px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,0.15);border-radius:50%;border:none;z-index:2;">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"
                                        style="filter:invert(1);"></span>
                                    <span class="visually-hidden">Anterior</span>
                                </button>
                                <button class="carousel-control-next" type="button"
                                    data-bs-target="#capitulosCarousel-<?= $plano['id'] ?>" data-bs-slide="next"
                                    style="width:48px;height:48px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,0.15);border-radius:50%;border:none;z-index:2;">
                                    <span class="carousel-control-next-icon" aria-hidden="true"
                                        style="filter:invert(1);"></span>
                                    <span class="visually-hidden">Próximo</span>
                                </button>
                                <?php endif; ?>
                            </div>
                            <script>
                            // Atualiza o número do capítulo/tópico atual no carrossel
                            document.addEventListener('DOMContentLoaded', function() {
                                var carousel = document.getElementById('capitulosCarousel-<?= $plano['id'] ?>');
                                if (carousel) {
                                    carousel.addEventListener('slid.bs.carousel', function(e) {
                                        var idx = e.to + 1;
                                        document.getElementById('capitulo-atual-<?= $plano['id'] ?>')
                                            .textContent = idx;
                                        // Atualiza o número do tópico atual (primeiro tópico do capítulo)
                                        var capIds =
                                            <?= json_encode(array_values(array_column($capitulos, 'id'))) ?>;
                                        var topicos = <?= json_encode($topicosPorCapitulo) ?>[capIds[e
                                            .to]] || [];
                                        document.getElementById('topico-atual-<?= $plano['id'] ?>')
                                            .textContent = topicos.length > 0 ? 1 : 0;
                                    });
                                }
                            });
                            </script>
                            <?php else: ?>
                            <div class="col-12 d-flex justify-content-center align-items-center"
                                style="min-height:180px;">
                                <div class="w-100" style="max-width:420px;">
                                    <div class="p-3 rounded-4 shadow-sm border border-2 border-warning bg-white text-center"
                                        style="background:linear-gradient(90deg,#fff 80%,#fffbe6 100%);">
                                        <div class="mb-2">
                                            <i class="bi bi-exclamation-circle text-warning" style="font-size:2em;"></i>
                                        </div>
                                        <div class="fw-bold text-warning mb-1" style="font-size:1.13em;">
                                            Nenhum capítulo cadastrado neste plano!
                                        </div>
                                        <div class="text-muted" style="font-size:1em;">
                                            Clique em <span class="badge bg-success text-white"><i
                                                    class="bi bi-plus-circle"></i> Adicionar Capítulo</span> para
                                            cadastrar o primeiro capítulo deste plano.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($topicosPersonalizadosPorPlano[$plano['id']])): ?>
                            <div class="mt-2">
                                <span class="badge bg-warning-subtle text-dark border border-warning"><i
                                        class="bi bi-lightbulb"></i> Tópicos personalizados ministrados:</span>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($topicosPersonalizadosPorPlano[$plano['id']] as $desc): ?>
                                    <li class="list-group-item ps-4 text-primary">
                                        <i class="bi bi-lightbulb"></i> <?= htmlspecialchars($desc) ?>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                            <?php else: ?>
    <div class="d-flex justify-content-center align-items-center" style="min-height:320px;">
        <div class="w-100 mx-auto" style="max-width:540px;">
            <div class="p-4 rounded-4 shadow-sm border border-2 border-danger bg-white text-center"
                style="background:linear-gradient(90deg,#fff 80%,#ffeaea 100%);">
                <div class="mb-3">
                    <i class="bi bi-exclamation-octagon-fill text-danger"
                        style="font-size:3em;"></i>
                </div>
                <h4 class="fw-bold text-danger mb-2">Nenhum plano cadastrado para esta
                    disciplina!</h4>
                <div class="text-muted mb-3" style="font-size:1.13em;">
                    O coordenador pode criar um plano para esta disciplina clicando no botão
                    abaixo.
                </div>
                <button class="btn btn-success btn-lg d-flex align-items-center gap-2 mx-auto"
                    style="font-size:1.15em;box-shadow:0 2px 8px #19875433;"
                    onclick="abrirModalPlanoDisciplina(<?= $disc['id'] ?>, '<?= htmlspecialchars(addslashes($disc['nome'])) ?>', <?= $turma_id ?>)">
                    <i class="bi bi-plus-circle"></i> Criar Plano
                </button>
            </div>
        </div>
    </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
   
    <!-- Modal de Criar/Editar Plano (novo estilo) -->
    <div id="modalPlanoNovo"
        style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);">
        <div
            style="background:#fff;padding:0;border-radius:18px;max-width:700px;width:95vw;margin:60px auto;position:relative;box-shadow:0 8px 32px rgba(0,0,0,0.18);overflow:hidden;">
            <div
                style="background:linear-gradient(90deg,#0d6efd 60%,#4f8cff 100%);padding:18px 32px 14px 32px;display:flex;align-items:center;gap:12px;">
                <i class="bi bi-journal-bookmark-fill text-white" style="font-size:2rem;"></i>
                <h4 id="tituloModalPlanoNovo" class="mb-0 text-white">Criar Plano de Aula</h4>
                <span onclick="fecharModalPlanoNovo()"
                    style="position:absolute;top:14px;right:22px;font-size:28px;cursor:pointer;color:#fff;opacity:0.8;">&times;</span>
            </div>
            <div style="padding:30px 32px 18px 32px;">
                <form id="formPlanoNovo" action="../controllers/criar_plano_ajax.php" method="POST">
                    <input type="hidden" name="id_plano" id="id_plano_novo">
                    <?php if ($turma_id): ?>
                    <input type="hidden" name="turma_id" id="turma_id_plano_novo" value="<?= $turma_id ?>">
                    <input type="hidden" name="disciplina_id" id="disciplina_id_plano_novo" value="">
                    <!-- O campo de nome da disciplina só para exibir -->
                    <input type="text" id="disciplina_nome_plano_novo" class="form-control mb-2" value="" readonly
                        style="display:none;">
                    <?php else: ?>
                    <input type="hidden" name="disciplina_id" id="disciplina_id_plano_novo" value="">
                    <?php endif; ?>
                    <div class="input-group mb-2">
                        <span class="input-group-text bg-white"><i class="bi bi-type-bold"></i></span>
                        <input type="text" name="titulo" id="titulo_plano_novo" placeholder="Título do plano" required
                            class="form-control">
                    </div>
                    <input type="hidden" name="status" id="status_plano_novo" value="em_andamento">
                    <div class="row mb-2">
                        <div class="col-md-6 mb-2 mb-md-0">
                            <label>Data início:</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-calendar-event"></i></span>
                                <input type="date" name="data_inicio" id="data_inicio_plano_novo" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label>Data fim:</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-calendar2-week"></i></span>
                                <input type="date" name="data_fim" id="data_fim_plano_novo" class="form-control">
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="redirect"
                        value="planos.php<?= $turma_id ? '?turma_id=' . $turma_id : '' ?>">
                </form>
                <div class="d-flex justify-content-end gap-2 pt-3 border-top mt-4" style="background:transparent;">
                    <button type="button" class="btn btn-secondary d-flex align-items-center gap-1"
                        onclick="fecharModalPlanoNovo()"><i class="bi bi-x-circle"></i> Cancelar</button>
                    <button type="submit" class="btn btn-primary d-flex align-items-center gap-1"
                        id="btnSalvarPlanoNovo" form="formPlanoNovo"><i class="bi bi-check-circle"></i>
                        Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Criar/Editar Capítulo (novo estilo) -->
    <div id="modalCapituloNovo"
        style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);">
        <div
            style="background:#fff;padding:0;border-radius:18px;max-width:700px;width:95vw;margin:60px auto;position:relative;box-shadow:0 8px 32px rgba(0,0,0,0.18);overflow:hidden;">
            <div
                style="background:linear-gradient(90deg,#0d6efd 60%,#4f8cff 100%);padding:18px 32px 14px 32px;display:flex;align-items:center;gap:12px;">
                <i class="bi bi-journal-text text-white" style="font-size:2rem;"></i>
                <h4 id="tituloModalCapituloNovo" class="mb-0 text-white">Adicionar Capítulo</h4>
                <span onclick="fecharModalCapituloNovo()"
                    style="position:absolute;top:14px;right:22px;font-size:28px;cursor:pointer;color:#fff;opacity:0.8;">&times;</span>
            </div>
            <div style="padding:30px 32px 18px 32px;">
                <form id="formCapituloNovo" action="../controllers/criar_capitulo_ajax.php" method="POST">
                    <input type="hidden" name="plano_id" id="plano_id_capitulo_novo">
                    <input type="hidden" name="id_capitulo" id="id_capitulo_novo">
                    <input type="hidden" name="status" id="status_capitulo_novo" value="em_andamento">
                    <div class="input-group mb-2">
                        <span class="input-group-text bg-white"><i class="bi bi-type-bold"></i></span>
                        <input type="text" name="titulo" id="titulo_capitulo_novo" placeholder="Título do capítulo"
                            required class="form-control">
                    </div>
                    <div class="mb-2">
                        <label>Descrição do capítulo:</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-card-text"></i></span>
                            <textarea name="descricao" id="descricao_capitulo_novo" placeholder="Descrição do capítulo"
                                class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </form>
                <div class="d-flex justify-content-end gap-2 pt-3 border-top mt-4" style="background:transparent;">
                    <button type="button" class="btn btn-secondary d-flex align-items-center gap-1"
                        onclick="fecharModalCapituloNovo()"><i class="bi bi-x-circle"></i> Cancelar</button>
                    <button type="submit" class="btn btn-primary d-flex align-items-center gap-1"
                        id="btnSalvarCapituloNovo" form="formCapituloNovo"><i class="bi bi-check-circle"></i>
                        Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Criar/Editar Tópico (novo estilo) -->
    <div id="modalTopicoNovo"
        style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);">
        <div
            style="background:#fff;padding:0;border-radius:18px;max-width:700px;width:95vw;margin:60px auto;position:relative;box-shadow:0 8px 32px rgba(0,0,0,0.18);overflow:hidden;">
            <div
                style="background:linear-gradient(90deg,#0d6efd 60%,#4f8cff 100%);padding:18px 32px 14px 32px;display:flex;align-items:center;gap:12px;">
                <i class="bi bi-lightbulb-fill text-warning" style="font-size:2rem;"></i>
                <h4 id="tituloModalTopicoNovo" class="mb-0 text-white">Adicionar Tópico</h4>
                <span onclick="fecharModalTopicoNovo()"
                    style="position:absolute;top:14px;right:22px;font-size:28px;cursor:pointer;color:#fff;opacity:0.8;">&times;</span>
            </div>
            <div style="padding:30px 32px 18px 32px;">
                <form id="formTopicoNovo" action="../controllers/criar_topico_ajax.php" method="POST">
                    <input type="hidden" name="capitulo_id" id="capitulo_id_topico_novo">
                    <input type="hidden" name="id_topico" id="id_topico_novo">
                    <input type="hidden" name="status" id="status_topico_novo" value="em_andamento">
                    <div class="input-group mb-2">
                        <span class="input-group-text bg-white"><i class="bi bi-type-bold"></i></span>
                        <input type="text" name="titulo" id="titulo_topico_novo" placeholder="Título do tópico" required
                            class="form-control">
                    </div>
                    <div class="mb-2">
                        <label>Descrição do tópico:</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-card-text"></i></span>
                            <textarea name="descricao" id="descricao_topico_novo" placeholder="Descrição do tópico"
                                required class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <!-- Removido campo de observações -->
                </form>
                <div class="d-flex justify-content-end gap-2 pt-3 border-top mt-4" style="background:transparent;">
                    <button type="button" class="btn btn-secondary d-flex align-items-center gap-1"
                        onclick="fecharModalTopicoNovo()"><i class="bi bi-x-circle"></i> Cancelar</button>
                    <button type="submit" class="btn btn-primary d-flex align-items-center gap-1"
                        id="btnSalvarTopicoNovo" form="formTopicoNovo"><i class="bi bi-check-circle"></i>
                        Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmar Exclusão de Plano -->
    <div id="modalExcluirPlano"
        style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);">
        <div
            style="background:#fff;padding:0;border-radius:14px;max-width:400px;margin:100px auto;position:relative;box-shadow:0 8px 32px rgba(0,0,0,0.18);overflow:hidden;">
            <div
                style="background:linear-gradient(90deg,#dc3545 60%,#ff6f91 100%);padding:16px 24px 12px 24px;display:flex;align-items:center;gap:10px;">
                <i class="bi bi-trash-fill text-white" style="font-size:1.7rem;"></i>
                <h4 class="mb-0 text-white">Excluir Plano</h4>
                <span onclick="fecharModalExcluirPlano()"
                    style="position:absolute;top:10px;right:18px;font-size:26px;cursor:pointer;color:#fff;opacity:0.8;">&times;</span>
            </div>
            <div style="padding:24px 24px 18px 24px;">
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" style="font-size:1.1em;">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size:1.5em;"></i>
                    Atenção! Esta ação não poderá ser desfeita.
                </div>
                <form action="../controllers/excluir_plano.php" method="POST" id="formExcluirPlano">
                    <input type="hidden" name="id_plano" id="excluir_id_plano">
                    <input type="hidden" name="redirect" value="">
                    <p id="excluir_nome_plano" style="margin:15px 0;"></p>
                    <div class="d-flex justify-content-end gap-2 pt-2">
                        <button type="submit" class="btn btn-danger d-flex align-items-center gap-1"><i
                                class="bi bi-trash"></i> Confirmar Exclusão</button>
                        <button type="button" class="btn btn-secondary d-flex align-items-center gap-1"
                            onclick="fecharModalExcluirPlano()"><i class="bi bi-x-circle"></i> Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmar Exclusão de Capítulo -->
    <div id="modalExcluirCapitulo"
        style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);">
        <div
            style="background:#fff;padding:0;border-radius:14px;max-width:400px;margin:100px auto;position:relative;box-shadow:0 8px 32px rgba(0,0,0,0.18);overflow:hidden;">
            <div
                style="background:linear-gradient(90deg,#dc3545 60%,#ff6f91 100%);padding:16px 24px 12px 24px;display:flex;align-items:center;gap:10px;">
                <i class="bi bi-trash-fill text-white" style="font-size:1.7rem;"></i>
                <h4 class="mb-0 text-white">Excluir Capítulo</h4>
                <span onclick="fecharModalExcluirCapitulo()"
                    style="position:absolute;top:10px;right:18px;font-size:26px;cursor:pointer;color:#fff;opacity:0.8;">&times;</span>
            </div>
            <div style="padding:24px 24px 18px 24px;">
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" style="font-size:1.1em;">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size:1.5em;"></i>
                    Atenção! Esta ação não poderá ser desfeita.
                </div>
                <form action="../controllers/excluir_capitulo_ajax.php" method="POST" id="formExcluirCapitulo">
                    <input type="hidden" name="id_capitulo" id="excluir_id_capitulo">
                    <p id="excluir_nome_capitulo" style="margin:15px 0;"></p>
                    <div class="d-flex justify-content-end gap-2 pt-2">
                        <button type="submit" class="btn btn-danger d-flex align-items-center gap-1"><i
                                class="bi bi-trash"></i> Confirmar Exclusão</button>
                        <button type="button" class="btn btn-secondary d-flex align-items-center gap-1"
                            onclick="fecharModalExcluirCapitulo()"><i class="bi bi-x-circle"></i> Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmar Exclusão de Tópico -->
    <div id="modalExcluirTopico"
        style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);">
        <div
            style="background:#fff;padding:0;border-radius:14px;max-width:400px;margin:100px auto;position:relative;box-shadow:0 8px 32px rgba(0,0,0,0.18);overflow:hidden;">
            <div
                style="background:linear-gradient(90deg,#dc3545 60%,#ff6f91 100%);padding:16px 24px 12px 24px;display:flex;align-items:center;gap:10px;">
                <i class="bi bi-trash-fill text-white" style="font-size:1.7rem;"></i>
                <h4 class="mb-0 text-white">Excluir Tópico</h4>
                <span onclick="fecharModalExcluirTopico()"
                    style="position:absolute;top:10px;right:18px;font-size:26px;cursor:pointer;color:#fff;opacity:0.8;">&times;</span>
            </div>
            <div style="padding:24px 24px 18px 24px;">
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" style="font-size:1.1em;">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size:1.5em;"></i>
                    Atenção! Esta ação não poderá ser desfeita.
                </div>
                <form action="../controllers/excluir_topico_ajax.php" method="POST" id="formExcluirTopico">
                    <input type="hidden" name="id_topico" id="excluir_id_topico">
                    <p id="excluir_nome_topico" style="margin:15px 0;"></p>
                    <div class="d-flex justify-content-end gap-2 pt-2">
                        <button type="submit" class="btn btn-danger d-flex align-items-center gap-1"><i
                                class="bi bi-trash"></i> Confirmar Exclusão</button>
                        <button type="button" class="btn btn-secondary d-flex align-items-center gap-1"
                            onclick="fecharModalExcluirTopico()"><i class="bi bi-x-circle"></i> Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmar Toggle de Capítulo -->
    <div id="modalToggleCapitulo"
        style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);">
        <div
            style="background:#fff;padding:0;border-radius:14px;max-width:400px;margin:100px auto;position:relative;box-shadow:0 8px 32px rgba(0,0,0,0.18);overflow:hidden;">
            <div
                style="background:linear-gradient(90deg,#0d6efd 60%,#4f8cff 100%);padding:16px 24px 12px 24px;display:flex;align-items:center;gap:10px;">
                <i class="bi bi-arrow-repeat text-white" style="font-size:1.7rem;"></i>
                <h4 class="mb-0 text-white">Toggle Capítulo</h4>
                <span onclick="fecharModalToggleCapitulo()"
                    style="position:absolute;top:10px;right:18px;font-size:26px;cursor:pointer;color:#fff;opacity:0.8;">&times;</span>
            </div>
            <div style="padding:24px 24px 18px 24px;">
                <div class="alert alert-info d-flex align-items-center gap-2 mb-3" style="font-size:1.1em;">
                    <i class="bi bi-info-circle-fill" style="font-size:1.5em;"></i>
                    Deseja alterar o status deste capítulo?
                </div>
                <form action="../controllers/toggle_capitulo_ajax.php" method="POST" id="formToggleCapitulo">
                    <input type="hidden" name="id_capitulo" id="toggle_id_capitulo">
                    <input type="hidden" name="status" id="toggle_status_capitulo">
                    <p id="toggle_nome_capitulo" style="margin:15px 0;"></p>
                    <div class="d-flex justify-content-end gap-2 pt-2">
                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-1"><i
                                class="bi bi-arrow-repeat"></i> Confirmar Alteração</button>
                        <button type="button" class="btn btn-secondary d-flex align-items-center gap-1"
                            onclick="fecharModalToggleCapitulo()"><i class="bi bi-x-circle"></i> Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmar Toggle de Tópico -->
    <div id="modalToggleTopico"
        style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);">
        <div
            style="background:#fff;padding:0;border-radius:14px;max-width:400px;margin:100px auto;position:relative;box-shadow:0 8px 32px rgba(0,0,0,0.18);overflow:hidden;">
            <div
                style="background:linear-gradient(90deg,#0d6efd 60%,#4f8cff 100%);padding:16px 24px 12px 24px;display:flex;align-items:center;gap:10px;">
                <i class="bi bi-arrow-repeat text-white" style="font-size:1.7rem;"></i>
                <h4 class="mb-0 text-white">Toggle Tópico</h4>
                <span onclick="fecharModalToggleTopico()"
                    style="position:absolute;top:10px;right:18px;font-size:26px;cursor:pointer;color:#fff;opacity:0.8;">&times;</span>
            </div>
            <div style="padding:24px 24px 18px 24px;">
                <div class="alert alert-info d-flex align-items-center gap-2 mb-3" style="font-size:1.1em;">
                    <i class="bi bi-info-circle-fill" style="font-size:1.5em;"></i>
                    Deseja alterar o status deste tópico?
                </div>
                <form action="../controllers/toggle_topico_ajax.php" method="POST" id="formToggleTopico">
                    <input type="hidden" name="id_topico" id="toggle_id_topico">
                    <input type="hidden" name="status" id="toggle_status_topico">
                    <p id="toggle_nome_topico" style="margin:15px 0;"></p>
                    <div class="d-flex justify-content-end gap-2 pt-2">
                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-1"><i
                                class="bi bi-arrow-repeat"></i> Confirmar Alteração</button>
                        <button type="button" class="btn btn-secondary d-flex align-items-center gap-1"
                            onclick="fecharModalToggleTopico()"><i class="bi bi-x-circle"></i> Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function abrirModalPlano() {
        // Limpar o formulário
        document.getElementById('formPlano').reset();
        if (document.getElementById('disciplina_id_plano')) document.getElementById('disciplina_id_plano').value =
            '';
        if (document.getElementById('disciplina_nome_plano')) document.getElementById('disciplina_nome_plano')
            .value = '';
        if (document.getElementById('titulo_plano')) document.getElementById('titulo_plano').value = '';
        if (document.getElementById('descricao_plano')) document.getElementById('descricao_plano').value = '';
        if (document.getElementById('objetivo_geral_plano')) document.getElementById('objetivo_geral_plano').value =
            '';
        if (document.getElementById('data_inicio_plano')) document.getElementById('data_inicio_plano').value = '';
        if (document.getElementById('data_fim_plano')) document.getElementById('data_fim_plano').value = '';
        if (document.getElementById('status_plano')) document.getElementById('status_plano').value = 'em_andamento';
        const modal = new bootstrap.Modal(document.getElementById('modalPlano'));
        modal.show();
    }

    function fecharModalPlano() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalPlano'));
        if (modal) modal.hide();
    }

    function abrirModalEditarPlano(id, titulo, disciplina_id, descricao, status, turma_id, data_inicio, data_fim,
        objetivo_geral) {
        document.getElementById('formPlanoNovo').reset();
        document.getElementById('formPlanoNovo').action = '../controllers/editar_plano.php';
        document.getElementById('tituloModalPlanoNovo').innerText = 'Editar Plano de Aula';
        document.getElementById('id_plano_novo').value = id;
        if (document.getElementById('disciplina_id_plano_novo')) document.getElementById('disciplina_id_plano_novo')
            .value = disciplina_id || '';
        if (document.getElementById('disciplina_nome_plano_novo')) document.getElementById(
            'disciplina_nome_plano_novo').value = '';
        document.getElementById('titulo_plano_novo').value = titulo || '';
        document.getElementById('data_inicio_plano_novo').value = data_inicio || '';
        document.getElementById('data_fim_plano_novo').value = data_fim || '';
        document.getElementById('status_plano_novo').value = 'em_andamento';
        document.getElementById('modalPlanoNovo').style.display = 'block';
    }

    function fecharModalPlanoNovo() {
        document.getElementById('modalPlanoNovo').style.display = 'none';
    }

    // Substitui o submit do modal antigo pelo novo
    document.getElementById('formPlanoNovo').onsubmit = async function(e) {
        e.preventDefault();
        const form = this;
        const formData = new FormData(form);
        const url = form.action;
        const resp = await fetch(url.includes('editar_plano.php') ? '../controllers/editar_plano_ajax.php' :
            '../controllers/criar_plano_ajax.php', {
                method: 'POST',
                body: formData
            });
        const data = await resp.json();
        if (data.success) {
            fecharModalPlanoNovo();
            mostrarNotificacao('Plano de aula salvo com sucesso!', 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            mostrarNotificacao(data.error || 'Erro ao salvar plano', 'danger');
        }
    };

    // Novo submit AJAX para capítulo (criar/editar)
    document.getElementById('formCapituloNovo').onsubmit = async function(e) {
        e.preventDefault();
        const form = this;
        const formData = new FormData(form);
        const url = form.action;
        const resp = await fetch(url.includes('editar_capitulo_ajax.php') ?
            '../controllers/editar_capitulo_ajax.php' : '../controllers/criar_capitulo_ajax.php', {
                method: 'POST',
                body: formData
            });
        const data = await resp.json();
        if (data.success) {
            fecharModalCapituloNovo();
            mostrarNotificacao('Capítulo salvo com sucesso!', 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            mostrarNotificacao(data.error || 'Erro ao salvar capítulo', 'danger');
        }
    };

    function abrirModalCapitulo(plano_id) {
        document.getElementById('formCapituloNovo').reset();
        document.getElementById('formCapituloNovo').action = '../controllers/criar_capitulo_ajax.php';
        document.getElementById('tituloModalCapituloNovo').innerText = 'Adicionar Capítulo';
        document.getElementById('id_capitulo_novo').value = '';
        document.getElementById('plano_id_capitulo_novo').value = plano_id;
        document.getElementById('titulo_capitulo_novo').value = '';
        document.getElementById('descricao_capitulo_novo').value = '';
        document.getElementById('status_capitulo_novo').value = 'em_andamento';
        document.getElementById('modalCapituloNovo').style.display = 'block';
    }

    function abrirModalEditarCapitulo(id, titulo, ordem, status, descricao, duracao) {
        document.getElementById('formCapituloNovo').reset();
        document.getElementById('formCapituloNovo').action = '../controllers/editar_capitulo_ajax.php';
        document.getElementById('tituloModalCapituloNovo').innerText = 'Editar Capítulo';
        document.getElementById('id_capitulo_novo').value = id;
        document.getElementById('titulo_capitulo_novo').value = titulo || '';
        document.getElementById('descricao_capitulo_novo').value = descricao || '';
        document.getElementById('status_capitulo_novo').value = 'em_andamento';
        document.getElementById('modalCapituloNovo').style.display = 'block';
    }

    function fecharModalCapituloNovo() {
        document.getElementById('modalCapituloNovo').style.display = 'none';
    }

    function abrirModalTopico(capitulo_id) {
        document.getElementById('formTopicoNovo').reset();
        document.getElementById('formTopicoNovo').action = '../controllers/criar_topico_ajax.php';
        document.getElementById('tituloModalTopicoNovo').innerText = 'Adicionar Tópico';
        document.getElementById('id_topico_novo').value = '';
        document.getElementById('capitulo_id_topico_novo').value = capitulo_id;
        document.getElementById('titulo_topico_novo').value = '';
        document.getElementById('descricao_topico_novo').value = '';
        document.getElementById('status_topico_novo').value = 'em_andamento';
        document.getElementById('modalTopicoNovo').style.display = 'block';
    }

    function abrirModalEditarTopico(id, capitulo_id, titulo, descricao, status, observacoes) {
        document.getElementById('formTopicoNovo').reset();
        document.getElementById('formTopicoNovo').action = '../controllers/editar_topico_ajax.php';
        document.getElementById('tituloModalTopicoNovo').innerText = 'Editar Tópico';
        document.getElementById('id_topico_novo').value = id;
        document.getElementById('capitulo_id_topico_novo').value = capitulo_id;
        document.getElementById('titulo_topico_novo').value = titulo || '';
        document.getElementById('descricao_topico_novo').value = descricao || '';
        document.getElementById('status_topico_novo').value = 'em_andamento';
        document.getElementById('modalTopicoNovo').style.display = 'block';
    }

    function fecharModalTopicoNovo() {
        document.getElementById('modalTopicoNovo').style.display = 'none';
    }

    // Novo submit AJAX para tópico (criar/editar)
    document.getElementById('formTopicoNovo').onsubmit = async function(e) {
        e.preventDefault();
        const form = this;
        const formData = new FormData(form);
        const url = form.action;
        const resp = await fetch(url.includes('editar_topico_ajax.php') ?
            '../controllers/editar_topico_ajax.php' : '../controllers/criar_topico_ajax.php', {
                method: 'POST',
                body: formData
            });
        const data = await resp.json();
        if (data.success) {
            fecharModalTopicoNovo();
            mostrarNotificacao('Tópico salvo com sucesso!', 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            mostrarNotificacao(data.error || 'Erro ao salvar tópico', 'danger');
        }
    };

    function abrirModalPlanoDisciplina(disc_id, disc_nome, turma_id) {
        document.getElementById('formPlanoNovo').reset();
        document.getElementById('formPlanoNovo').action = '../controllers/criar_plano.php';
        document.getElementById('tituloModalPlanoNovo').innerText = 'Criar Plano de Aula';
        document.getElementById('id_plano_novo').value = '';
        document.getElementById('titulo_plano_novo').value = '';
        document.getElementById('data_inicio_plano_novo').value = '';
        document.getElementById('data_fim_plano_novo').value = '';
        document.getElementById('status_plano_novo').value = 'em_andamento';
        if (document.getElementById('disciplina_id_plano_novo')) document.getElementById('disciplina_id_plano_novo')
            .value = disc_id || '';
        if (document.getElementById('disciplina_nome_plano_novo')) document.getElementById(
            'disciplina_nome_plano_novo').value = disc_nome || '';
        document.getElementById('modalPlanoNovo').style.display = 'block';
    }

    function abrirModalExcluirPlano(id, nome) {
        document.getElementById('excluir_id_plano').value = id;
        document.getElementById('excluir_nome_plano').innerHTML = '<b>' + nome + '</b>';
        // Redirecionamento após exclusão
        var urlParams = new URLSearchParams(window.location.search);
        var redirect = 'planos.php' + (urlParams.has('turma_id') ? '?turma_id=' + urlParams.get('turma_id') : '');
        document.querySelector('#formExcluirPlano input[name="redirect"]').value = redirect;
        document.getElementById('modalExcluirPlano').style.display = 'block';
    }

    function fecharModalExcluirPlano() {
        document.getElementById('modalExcluirPlano').style.display = 'none';
    }

    function abrirModalExcluirCapitulo(id, nome) {
        document.getElementById('excluir_id_capitulo').value = id;
        document.getElementById('excluir_nome_capitulo').innerHTML = '<b>' + nome + '</b>';
        document.getElementById('modalExcluirCapitulo').style.display = 'block';
    }

    function fecharModalExcluirCapitulo() {
        document.getElementById('modalExcluirCapitulo').style.display = 'none';
    }

    function abrirModalExcluirTopico(id, nome) {
        document.getElementById('excluir_id_topico').value = id;
        document.getElementById('excluir_nome_topico').innerHTML = '<b>' + nome + '</b>';
        document.getElementById('modalExcluirTopico').style.display = 'block';
    }

    function fecharModalExcluirTopico() {
        document.getElementById('modalExcluirTopico').style.display = 'none';
    }

    // Exclusão AJAX de Capítulo
    document.getElementById('formExcluirCapitulo').onsubmit = async function(e) {
        e.preventDefault();
        const form = this;
        const formData = new FormData(form);
        const resp = await fetch(form.action, {
            method: 'POST',
            body: formData
        });
        const data = await resp.json();
        if (data.success) {
            fecharModalExcluirCapitulo();
            mostrarNotificacao('Capítulo excluído com sucesso!', 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            mostrarNotificacao(data.error || 'Erro ao excluir capítulo', 'danger');
        }
    };

    // Exclusão AJAX de Tópico
    document.getElementById('formExcluirTopico').onsubmit = async function(e) {
        e.preventDefault();
        const form = this;
        const formData = new FormData(form);
        const resp = await fetch(form.action, {
            method: 'POST',
            body: formData
        });
        const data = await resp.json();
        if (data.success) {
            fecharModalExcluirTopico();
            mostrarNotificacao('Tópico excluído com sucesso!', 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            mostrarNotificacao(data.error || 'Erro ao excluir tópico', 'danger');
        }
    };

    // Exclusão AJAX de Plano
    document.getElementById('formExcluirPlano').onsubmit = async function(e) {
        e.preventDefault();
        const form = this;
        const formData = new FormData(form);
        const resp = await fetch(form.action, {
            method: 'POST',
            body: formData
        });
        const data = await resp.json();
        if (data.success) {
            fecharModalExcluirPlano();
            mostrarNotificacao('Plano excluído com sucesso!', 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            mostrarNotificacao(data.error || 'Erro ao excluir plano', 'danger');
        }
    };

    // Função para abrir o modal de toggle de Capítulo
   
    function abrirModalToggleCapitulo(id, nome, status, btn) {
        document.getElementById('toggle_id_capitulo').value = id;
        document.getElementById('toggle_nome_capitulo').innerHTML = '<b>' + nome + '</b>';
        document.getElementById('toggle_status_capitulo').value = status === 'cancelado' ? 'em_andamento' :
            'cancelado';
        document.getElementById('modalToggleCapitulo').style.display = 'block';
    }

    function fecharModalToggleCapitulo() {
        document.getElementById('modalToggleCapitulo').style.display = 'none';
    }

    // Função para abrir o modal de toggle de Tópico
    function abrirModalToggleTopico(id, nome, status, btn) {
        document.getElementById('toggle_id_topico').value = id;
        document.getElementById('toggle_nome_topico').innerHTML = '<b>' + nome + '</b>';
        document.getElementById('toggle_status_topico').value = status === 'cancelado' ? 'em_andamento' :
            'cancelado';
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
            mostrarNotificacao('Status do capítulo alterado com sucesso!', 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            mostrarNotificacao(data.error || 'Erro ao alterar status do capítulo', 'danger');
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
            mostrarNotificacao('Status do tópico alterado com sucesso!', 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            mostrarNotificacao(data.error || 'Erro ao alterar status do tópico', 'danger');
        }
    };
    </script>
    <?php include 'footer.php'; ?>
</body>

</html>