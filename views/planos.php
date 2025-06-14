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
                                    <div class="d-flex align-items-center gap-2">
                                        <h2 class="mb-0 fw-bold text-primary">Planos de
                                            Aula<?= $turma_id ? ' <span class=\"text-primary\">da Turma: ' . htmlspecialchars($turma_nome) . '</span>' : '' ?>
                                        </h2>
                                        <button type="button" class="btn btn-gradient-dicas shadow-sm px-3 py-2 d-flex align-items-center gap-2 fw-bold" id="btnDicasPlanos" title="Dicas da página" style="border-radius: 14px; font-size:1.13em; box-shadow: 0 2px 8px #0d6efd33;">
                                            <i class="bi bi-lightbulb-fill" style="font-size:1.35em;"></i>
                                            Dicas
                                        </button>
                                    </div>
    <!-- Modal de Dicas de Funcionamento -->
    <?php include __DIR__ . '/modais_planos/modalDicasPlanos.php'; ?>
    <!-- Fim do modal de dicas -->
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
                            <div class="d-flex justify-content-between align-items-center mb-3">
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
                                    <span class="badge bg-info-subtle text-dark border border-info" style="font-size:1.2rem;padding:8px 12px;"><i
                                            class="bi bi-journal-text"></i> Plano:
                                        <b><?= htmlspecialchars($plano['titulo']) ?></b></span>
                                    <span class="badge bg-light text-dark border border-secondary" style="font-size:1.2rem;padding:8px 12px;"><i
                                            class="bi bi-calendar2-week"></i> Criado em:
                                        <?= isset($plano['criado_em']) ? date('d/m/Y', strtotime($plano['criado_em'])) : '-' ?></span>
                                    <span class="badge <?= $plano['status'] === 'concluido' ? 'bg-success' : 'bg-warning text-dark' ?> border border-<?= $plano['status'] === 'concluido' ? 'success' : 'warning' ?>" style="font-size:1.2rem;padding:8px 12px;"><i
                                            class="bi bi-activity"></i>
                                        <?= $plano['status'] === 'concluido' ? 'Concluído' : 'Em andamento' ?></span>
                                    <span class="badge bg-primary-subtle text-primary border border-primary" style="font-size:1.2rem;padding:8px 12px;">
                                        <i class="bi bi-journal-bookmark-fill"></i>
                                        <?= $totalCapitulos ?> capítulo<?= $totalCapitulos == 1 ? '' : 's' ?>
                                    </span>
                                    <span class="badge bg-info-subtle text-info border border-info" style="font-size:1.2rem;padding:8px 12px;">
                                        <i class="bi bi-list-task"></i>
                                        <?= $totalTopicos ?> tópico<?= $totalTopicos == 1 ? '' : 's' ?>
                                    </span>
                                    <?php if ($totalCapitulos > 0): ?>
                                        <span class="badge bg-secondary-subtle text-dark border border-secondary" style="font-size:1.2rem;padding:8px 12px;">
                                            <i class="bi bi-eye"></i>
                                            Capítulo <span id="capitulo-atual-<?= $plano['id'] ?>">1</span> de
                                            <?= $totalCapitulos ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="turma-actions">
                                    <?php if (isset($planos[$disc['id']])): $plano = $planos[$disc['id']]; ?>
                                    <button class="btn btn-success btn-sm" style="font-size:1.2rem;padding:8px 16px;"
                                        onclick="abrirModalCapitulo(<?= $plano['id'] ?>)"><i
                                            class="bi bi-plus-circle"></i> Adicionar Capítulo</button>
                                    <button class="btn btn-primary btn-sm" title="Editar Plano" style="font-size:1.2rem;padding:8px 16px;"
                                        onclick="abrirModalEditarPlano(<?= $plano['id'] ?>, '<?= htmlspecialchars(addslashes($plano['titulo'])) ?>', <?= $plano['disciplina_id'] ?>, '<?= htmlspecialchars(addslashes($plano['descricao'])) ?>')">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" title="Excluir Plano" style="font-size:1.2rem;padding:8px 16px;"
                                        onclick="abrirModalExcluirPlano(<?= $plano['id'] ?>, '<?= htmlspecialchars(addslashes($plano['titulo'])) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Cards dos capítulos -->
                            <?php if ($totalCapitulos > 0): ?>
                            <!-- Stepper wizard de capítulos -->
                            <div class="wizard-stepper-capitulos mb-4" id="wizard-stepper-<?= $plano['id'] ?>">
                                <div class="d-flex flex-row align-items-center justify-content-center gap-4 mb-3" style="gap: 48px !important;">
                                    <button class="btn btn-outline-primary me-2" id="wizard-prev-top-<?= $plano['id'] ?>" style="min-width:90px;"><i class="bi bi-arrow-left"></i> Anterior</button>
                                    <div class="d-flex flex-row align-items-center gap-4" style="gap: 48px !important;">
                                        <?php foreach ($capitulos as $idx => $cap): ?>
                                        <div class="wizard-step-circle position-relative <?= $idx === 0 ? 'active' : '' ?>"
                                            data-step="<?= $idx ?>"
                                            style="width:54px;height:54px;border-radius:50%;background:<?= $cap['status']==='concluido'?'#28a745':($cap['status']==='cancelado'?'#6c757d':'#0d6efd') ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.7rem;font-weight:bold;cursor:pointer;transition:box-shadow .2s;box-shadow:0 2px 8px #0001;">
                                            <i class="bi bi-journal-bookmark-fill"></i>
                                            <?php if ($idx < $totalCapitulos-1): ?>
                                            <div class="wizard-step-line position-absolute top-50 start-100 translate-middle-y" style="height:5px;width:60px;background:#b6d4fe;z-index:0;"></div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button class="btn btn-outline-primary ms-2" id="wizard-next-top-<?= $plano['id'] ?>" style="min-width:90px;">Próximo <i class="bi bi-arrow-right"></i></button>
                                </div>
                                <div class="wizard-step-content position-relative" style="min-height:340px;">
                                    <?php foreach ($capitulos as $idx => $cap): ?>
                                    <div class="wizard-step-card" data-step="<?= $idx ?>" style="display:<?= $idx===0?'block':'none' ?>;">
                                        <div class="row g-4 justify-content-center">
                                            <div class="col-12 capitulo-card">
                                                <?php
                                                $capStatus = $cap['status'];
                                                $capClass = '';
                                                $capMsg = '';
                                                if ($capStatus === 'cancelado') {
                                                    $capClass = 'bg-secondary text-white position-relative';
                                                    $capMsg = '<div class=\'cap-status-msg text-center fw-bold py-2\' style=\'background:#6c757d;color:#fff;border-radius:10px 10px 0 0;\'>Cancelado</div>';
                                                } elseif ($capStatus === 'concluido') {
                                                    $capClass = 'bg-secondary-subtle text-dark position-relative';
                                                    $capMsg = '<div class=\'cap-status-msg text-center fw-bold py-2\' style=\'background:#adb5bd;color:#222;border-radius:10px 10px 0 0;\'>Concluído</div>';
                                                }
                                                ?>
                                                <div class="card card-turma h-100 <?= $capClass ?>"
                                                    style="border-radius:18px; position:relative;
                                                    <?php if ($capStatus === 'cancelado'): ?>
                                                        border: 3px solid #6c757d;
                                                    <?php elseif ($capStatus === 'concluido'): ?>
                                                        border: 3px solid #28a745;
                                                    <?php endif; ?>
                                                    ">
                                                    <?php if ($capStatus === 'cancelado' || $capStatus === 'concluido'): ?>
                                                        <div class="status-overlay d-flex flex-column justify-content-center align-items-center"
                                                            style="position:absolute;top:0;left:0;width:100%;height:100%;
                                                            background:<?= $capStatus === 'cancelado' ? 'rgba(108,117,125,0.13)' : 'rgba(40,167,69,0.10)' ?>;
                                                            z-index:1;border-radius:18px;
                                                            color:<?= $capStatus === 'cancelado' ? '#444' : '#155724' ?>;
                                                            font-size:1.5em;font-weight:bold;text-shadow:0 2px 8px #fff;
                                                            pointer-events:none;">
                                                            <i class="bi <?= $capStatus === 'cancelado' ? 'bi-x-circle-fill' : 'bi-check-circle-fill' ?> mb-2"
                                                                style="font-size:2.5em;"></i>
                                                            <?= $capStatus === 'cancelado' ? 'Capítulo Cancelado' : 'Capítulo Concluído' ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="card-body d-flex flex-column <?= ($capStatus === 'cancelado' || $capStatus === 'concluido') ? 'opacity-50' : '' ?>" style="position:relative;z-index:2;">
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
                                                            <div class="ms-auto d-flex gap-2 action-btns-on-top" style="position:absolute;top:10px;right:10px;z-index:1000;">
                                                                <button class="btn btn-primary btn-sm"
                                                                    title="Editar Capítulo" style="font-size:1.15rem;"
                                                                    onclick="abrirModalEditarCapitulo(<?= $cap['id'] ?>, '<?= htmlspecialchars(addslashes($cap['titulo'])) ?>', <?= $cap['ordem'] ?>, '<?= $cap['status'] ?>', '<?= htmlspecialchars(addslashes($cap['descricao'])) ?>')">
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
                                                                <?php if ($cap['status'] !== 'concluido' && $cap['status'] !== 'pendente'): ?>
                                                                <span style="display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;background:rgba(255,255,255,0.7);border-radius:50%;box-shadow:0 2px 8px #0002;z-index:51;">
                                                                    <button class="btn btn-toggle-no-border btn-outline-secondary btn-sm"
                                                                        title="Ativar/Cancelar Capítulo"
                                                                        style="font-size:2.2rem;background:none;border:none;box-shadow:none;outline:none;padding:0;margin:0;z-index:1000;pointer-events:auto;position:relative;"
                                                                        onclick="abrirModalToggleCapitulo(<?= $cap['id'] ?>, '<?= addslashes($cap['titulo']) ?>', '<?= $cap['status'] ?>', this)">
                                                                        <i class="bi <?= $cap['status'] === 'cancelado' ? 'bi-toggle-on' : 'bi-toggle-off' ?>"></i>
                                                                    </button>
                                                                </span>
                                                                <?php elseif ($cap['status'] === 'cancelado'): ?>
                                                                <span style="display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;background:rgba(255,255,255,0.7);border-radius:50%;box-shadow:0 2px 8px #0002;z-index:51;">
                                                                    <button class="btn btn-toggle-no-border btn-outline-secondary btn-sm"
                                                                        title="Ativar Capítulo"
                                                                        style="font-size:2.2rem;background:none;border:none;box-shadow:none;outline:none;padding:0;margin:0;z-index:100;pointer-events:auto;"
                                                                        onclick="abrirModalToggleCapitulo(<?= $cap['id'] ?>, '<?= addslashes($cap['titulo']) ?>', '<?= $cap['status'] ?>', this)">
                                                                        <i class="bi bi-toggle-on"></i>
                                                                    </button>
                                                                </span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <div style="background: linear-gradient(90deg, #e3f0ff 80%, #f8fafc 100%); border: 1.5px solid #b6d4fe; border-radius: 12px; box-shadow: 0 2px 8px #0d6efd11; padding: 15px 20px; display: flex; align-items: center; gap: 12px; min-height: 45px;">
                                                                <i class="bi bi-card-text text-primary" style="font-size:1.4rem;"></i>
                                                                <span style="color:#222; font-size:1.25rem; line-height:1.6; white-space:pre-line; word-break:break-word;">
                                                                    <?= nl2br(htmlspecialchars($cap['descricao'])) ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="ms-3">
                                                            <?php if (!empty($topicosPorCapitulo[$cap['id']])): foreach ($topicosPorCapitulo[$cap['id']] as $top): ?>
                                                            <?php
                                                            $topStatus = $top['status'];
                                                            $topClass = '';
                                                            $topBorder = '';
                                                            if ($topStatus === 'cancelado') {
                                                                $topClass = 'position-relative';
                                                                $topBorder = 'border: 3px solid #6c757d;';
                                                            } elseif ($topStatus === 'concluido') {
                                                                $topClass = 'position-relative';
                                                                $topBorder = 'border: 3px solid #28a745;';
                                                            }
                                                            ?>
                                                            <div class="mb-3 p-3 rounded shadow-sm <?= $topClass ?>"
                                                                style="background:linear-gradient(90deg,#f8fafc 80%,#e3f0ff 100%);border-radius:12px;position:relative;font-size:1.13rem;<?= $topBorder ?>">
                                                                <?php if ($topStatus === 'cancelado' || $topStatus === 'concluido'): ?>
                                                                    <div class="status-overlay d-flex flex-column justify-content-center align-items-center"
                                                                        style="position:absolute;top:0;left:0;width:100%;height:100%;
                                                                        background:<?= $topStatus === 'cancelado' ? 'rgba(108,117,125,0.13)' : 'rgba(40,167,69,0.10)' ?>;
                                                                        z-index:1;border-radius:12px;
                                                                        color:<?= $topStatus === 'cancelado' ? '#444' : '#155724' ?>;
                                                                        font-size:1.2em;font-weight:bold;text-shadow:0 2px 8px #fff;
                                                                        pointer-events:none;">
                                                                        <i class="bi <?= $topStatus === 'cancelado' ? 'bi-x-circle-fill' : 'bi-check-circle-fill' ?> mb-2"
                                                                            style="font-size:2em;"></i>
                                                                        <?= $topStatus === 'cancelado' ? 'Tópico Cancelado' : 'Tópico Concluído' ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <div class="<?= ($topStatus === 'cancelado' || $topStatus === 'concluido') ? 'opacity-50' : '' ?>" style="position:relative;z-index:2;">
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
                                                                        <div class="ms-auto d-flex gap-2 action-btns-on-top" >
                                                                            <button class="btn btn-primary btn-sm"
                                                                                title="Editar Tópico"
                                                                                style="font-size:1.08rem;"
                                                                                onclick="abrirModalEditarTopico(<?= $top['id'] ?>, <?= $cap['id'] ?>, '<?= htmlspecialchars(addslashes($top['titulo'])) ?>', '<?= htmlspecialchars(addslashes($top['descricao'])) ?>', '<?= $top['status'] ?>')">
                                                                                <i class="bi bi-pencil-square"></i>
                                                                            </button>
                                                                            <button class="btn btn-danger btn-sm"
                                                                                title="Excluir Tópico"
                                                                                style="font-size:1.08rem;"
                                                                                onclick="abrirModalExcluirTopico(<?= $top['id'] ?>, '<?= htmlspecialchars(addslashes($top['titulo'])) ?>')">
                                                                                <i class="bi bi-trash"></i>
                                                                            </button>
                                                                            <?php if ($top['status'] !== 'concluido' && $top['status'] !== 'pendente'): ?>
                                                                            <span style="display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:50%;box-shadow:0 2px 8px #0002;z-index:51;">
                                                                                <button class="btn btn-toggle-no-border btn-outline-secondary btn-sm"
                                                                                    title="Ativar/Cancelar Tópico"
                                                                                    style="font-size:2rem;background:none;border:none;box-shadow:none;outline:none;padding:0;margin:0;pointer-events:auto;position:relative;"
                                                                                    onclick="abrirModalToggleTopico(<?= $top['id'] ?>, '<?= addslashes($top['titulo']) ?>', '<?= $top['status'] ?>', this)">
                                                                                    <i class="bi <?= $top['status'] === 'cancelado' ? 'bi-toggle-on' : 'bi-toggle-off' ?>"></i>
                                                                                </button>
                                                                            </span>
                                                                            <?php elseif ($top['status'] === 'cancelado'): ?>
                                                                            <span style="display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:50%;box-shadow:0 2px 8px #0002;z-index:51;">
                                                                                <button class="btn btn-toggle-no-border btn-outline-secondary btn-sm"
                                                                                    title="Ativar Tópico"
                                                                                    style="font-size:2rem;background:none;border:none;box-shadow:none;outline:none;padding:0;margin:0;z-index:100;pointer-events:auto;"
                                                                                    onclick="abrirModalToggleTopico(<?= $top['id'] ?>, '<?= addslashes($top['titulo']) ?>', '<?= $top['status'] ?>', this)">
                                                                                    <i class="bi bi-toggle-on"></i>
                                                                                </button>
                                                                            </span>
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
                                        <!-- Navegação -->
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <button class="btn btn-outline-primary" id="wizard-prev-<?= $plano['id'] ?>" style="min-width:100px;"><i class="bi bi-arrow-left"></i> Anterior</button>
                                            <button class="btn btn-outline-primary" id="wizard-next-<?= $plano['id'] ?>" style="min-width:100px;">Próximo <i class="bi bi-arrow-right"></i></button>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
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
                            <div class="d-flex justify-content-center align-items-center w-100 h-100" style="min-height:320px;">
                            <div class="w-100 mx-auto" style="max-width:540px;">
                            <div class="p-4 rounded-4 shadow-sm border border-2 border-danger bg-white text-center"
                            style="background:linear-gradient(90deg,#fff 80%,#ffeaea 100%);">
                            <div class="mb-3">
                            <i class="bi bi-exclamation-octagon-fill text-danger"
                            style="font-size:3em;"></i>
                            </div>
                            <h4 class="fw-bold text-danger mb-2">Nenhum plano cadastrado para esta disciplina!</h4>
                            <div class="text-muted mb-3" style="font-size:1.13em;">
                            O coordenador pode criar um plano para esta disciplina clicando no botão abaixo.
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
    <?php include __DIR__ . '/modais_planos/modalPlanoNovo.php'; ?>

    <!-- Modal de Criar/Editar Capítulo (novo estilo) -->
    <?php include __DIR__ . '/modais_planos/modalCapituloNovo.php'; ?>

    <!-- Modal de Criar/Editar Tópico (novo estilo) -->
    <?php include __DIR__ . '/modais_planos/modalTopicoNovo.php'; ?>

    <!-- Modal de Confirmar Exclusão de Plano -->
    <?php include __DIR__ . '/modais_planos/modalExcluirPlano.php'; ?>

    <!-- Modal de Confirmar Exclusão de Capítulo -->
    <?php include __DIR__ . '/modais_planos/modalExcluirCapitulo.php'; ?>

    <!-- Modal de Confirmar Exclusão de Tópico -->
    <?php include __DIR__ . '/modais_planos/modalExcluirTopico.php'; ?>

    <!-- Modal de Confirmar Toggle de Capítulo -->
    <?php include __DIR__ . '/modais_planos/modalToggleCapitulo.php'; ?>

    <!-- Modal de Confirmar Toggle de Tópico -->
    <?php include __DIR__ . '/modais_planos/modalToggleTopico.php'; ?>

    <script>
    let abaAtual = 0;
let stepAtualPorPlano = {};

// --- Limpa localStorage de aba/step ao carregar a página (F5 ou acesso direto) ---
if (performance.navigation.type === 1 || performance.getEntriesByType("navigation")[0]?.type === "reload") {
    localStorage.removeItem('abaAtualPlanos');
    Object.keys(localStorage).forEach(function(k){
        if (k.startsWith('stepAtualPlano_')) localStorage.removeItem(k);
    });
}

// Detecta aba ativa ao carregar e restaura (só restaura se não for reload/F5)
document.addEventListener('DOMContentLoaded', function() {
    const abas = document.querySelectorAll('#disciplinasTab .nav-link');
    if (localStorage.getItem('abaAtualPlanos')) {
        abaAtual = parseInt(localStorage.getItem('abaAtualPlanos'));
        if (abas[abaAtual]) abas[abaAtual].click();
    }
    abas.forEach((aba, idx) => {
        aba.addEventListener('shown.bs.tab', function() {
            abaAtual = idx;
            localStorage.setItem('abaAtualPlanos', abaAtual);
        });
    });
    // Remover a linha abaixo para não restaurar step/aba no carregamento
});

// Funções para abrir/fechar modais de capítulo/tópico
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
window.abrirModalCapitulo = abrirModalCapitulo;

function fecharModalCapituloNovo() {
    document.getElementById('modalCapituloNovo').style.display = 'none';
}
window.fecharModalCapituloNovo = fecharModalCapituloNovo;

function abrirModalEditarCapitulo(id, titulo, ordem, status, descricao, duracao) {
    document.getElementById('formCapituloNovo').reset();
    document.getElementById('formCapituloNovo').action = '../controllers/editar_capitulo_ajax.php';
    document.getElementById('tituloModalCapituloNovo').innerText = 'Editar Capítulo';
    document.getElementById('id_capitulo_novo').value = id;
    document.getElementById('plano_id_capitulo_novo').value = '';
    document.getElementById('titulo_capitulo_novo').value = titulo || '';
    document.getElementById('descricao_capitulo_novo').value = descricao || '';
    document.getElementById('status_capitulo_novo').value = 'em_andamento';
    document.getElementById('modalCapituloNovo').style.display = 'block';
}
window.abrirModalEditarCapitulo = abrirModalEditarCapitulo;

function abrirModalExcluirCapitulo(id, titulo) {
    document.getElementById('excluir_id_capitulo').value = id;
    document.getElementById('excluir_nome_capitulo').innerHTML = 'Tem certeza que deseja excluir o capítulo <b>' + titulo + '</b>?';
    document.getElementById('modalExcluirCapitulo').style.display = 'block';
}
window.abrirModalExcluirCapitulo = abrirModalExcluirCapitulo;

function fecharModalExcluirCapitulo() {
    document.getElementById('modalExcluirCapitulo').style.display = 'none';
}
window.fecharModalExcluirCapitulo = fecharModalExcluirCapitulo;

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
window.abrirModalTopico = abrirModalTopico;

function fecharModalTopicoNovo() {
    document.getElementById('modalTopicoNovo').style.display = 'none';
}
window.fecharModalTopicoNovo = fecharModalTopicoNovo;

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
window.abrirModalEditarTopico = abrirModalEditarTopico;

function abrirModalExcluirTopico(id, nome) {
    document.getElementById('excluir_id_topico').value = id;
    document.getElementById('excluir_nome_topico').innerHTML = '<b>' + nome + '</b>';
    document.getElementById('modalExcluirTopico').style.display = 'block';
}
window.abrirModalExcluirTopico = abrirModalExcluirTopico;

function fecharModalExcluirTopico() {
    document.getElementById('modalExcluirTopico').style.display = 'none';
}
window.fecharModalExcluirTopico = fecharModalExcluirTopico;

function abrirModalToggleCapitulo(id, nome, status, btn) {
    document.getElementById('toggle_id_capitulo').value = id;
    document.getElementById('toggle_nome_capitulo').innerHTML = '<b>' + nome + '</b>';
    document.getElementById('toggle_status_capitulo').value = status === 'cancelado' ? 'em_andamento' : 'cancelado';
    document.getElementById('modalToggleCapitulo').style.display = 'block';
}
window.abrirModalToggleCapitulo = abrirModalToggleCapitulo;

function fecharModalToggleCapitulo() {
    document.getElementById('modalToggleCapitulo').style.display = 'none';
}
window.fecharModalToggleCapitulo = fecharModalToggleCapitulo;

function abrirModalToggleTopico(id, nome, status, btn) {
    document.getElementById('toggle_id_topico').value = id;
    document.getElementById('toggle_nome_topico').innerHTML = '<b>' + nome + '</b>';
    document.getElementById('toggle_status_topico').value = status === 'cancelado' ? 'em_andamento' : 'cancelado';
    document.getElementById('modalToggleTopico').style.display = 'block';
}
window.abrirModalToggleTopico = abrirModalToggleTopico;

function fecharModalToggleTopico() {
    document.getElementById('modalToggleTopico').style.display = 'none';
}
window.fecharModalToggleTopico = fecharModalToggleTopico;

// Funções para abrir/fechar modais de plano
function abrirModalPlanoDisciplina(disc_id, disc_nome, turma_id) {
    document.getElementById('formPlanoNovo').reset();
    document.getElementById('formPlanoNovo').action = '../controllers/criar_plano_ajax.php';
    document.getElementById('tituloModalPlanoNovo').innerText = 'Criar Plano de Aula';
    document.getElementById('id_plano_novo').value = '';
    // Corrigir: só seta se o campo existir
    var disciplinaIdInput = document.getElementById('disciplina_id_plano_novo');
    if (disciplinaIdInput) disciplinaIdInput.value = disc_id || '';
    var disciplinaNomeInput = document.getElementById('disciplina_nome_plano_novo');
    if (disciplinaNomeInput) disciplinaNomeInput.value = disc_nome || '';
    var tituloPlanoInput = document.getElementById('titulo_plano_novo');
    if (tituloPlanoInput) tituloPlanoInput.value = '';
    var descricaoPlanoInput = document.getElementById('descricao_plano_novo');
    if (descricaoPlanoInput) descricaoPlanoInput.value = '';
    var dataInicioPlanoInput = document.getElementById('data_inicio_plano_novo');
    if (dataInicioPlanoInput) dataInicioPlanoInput.value = '';
    var dataFimPlanoInput = document.getElementById('data_fim_plano_novo');
    if (dataFimPlanoInput) dataFimPlanoInput.value = '';
    var statusPlanoInput = document.getElementById('status_plano_novo');
    if (statusPlanoInput) statusPlanoInput.value = 'em_andamento';
    document.getElementById('modalPlanoNovo').style.display = 'block';
}
window.abrirModalPlanoDisciplina = abrirModalPlanoDisciplina;

function abrirModalEditarPlano(id, titulo, disciplina_id, descricao, status, turma_id, data_inicio, data_fim, objetivo_geral) {
    document.getElementById('formPlanoNovo').reset();
    document.getElementById('formPlanoNovo').action = '../controllers/editar_plano_ajax.php';
    document.getElementById('tituloModalPlanoNovo').innerText = 'Editar Plano de Aula';
    document.getElementById('id_plano_novo').value = id;
    // Corrigir: só seta se o campo existir
    var disciplinaIdInput = document.getElementById('disciplina_id_plano_novo');
    if (disciplinaIdInput) disciplinaIdInput.value = disciplina_id || '';
    var tituloPlanoInput = document.getElementById('titulo_plano_novo');
    if (tituloPlanoInput) tituloPlanoInput.value = titulo || '';
    var descricaoPlanoInput = document.getElementById('descricao_plano_novo');
    if (descricaoPlanoInput) descricaoPlanoInput.value = descricao || '';
    var dataInicioPlanoInput = document.getElementById('data_inicio_plano_novo');
    if (dataInicioPlanoInput) dataInicioPlanoInput.value = data_inicio || '';
    var dataFimPlanoInput = document.getElementById('data_fim_plano_novo');
    if (dataFimPlanoInput) dataFimPlanoInput.value = data_fim || '';
    var statusPlanoInput = document.getElementById('status_plano_novo');
    if (statusPlanoInput) statusPlanoInput.value = status || 'em_andamento';
    document.getElementById('modalPlanoNovo').style.display = 'block';
}
window.abrirModalEditarPlano = abrirModalEditarPlano;

function fecharModalPlanoNovo() {
    document.getElementById('modalPlanoNovo').style.display = 'none';
}
window.fecharModalPlanoNovo = fecharModalPlanoNovo;

function abrirModalExcluirPlano(id, nome) {
    document.getElementById('excluir_id_plano').value = id;
    document.getElementById('excluir_nome_plano').innerHTML = '<b>' + nome + '</b>';
    // Redirecionamento após exclusão
    var urlParams = new URLSearchParams(window.location.search);
    var redirect = 'planos.php' + (urlParams.has('turma_id') ? '?turma_id=' + urlParams.get('turma_id') : '');
    document.querySelector('#formExcluirPlano input[name="redirect"]').value = redirect;
    document.getElementById('modalExcluirPlano').style.display = 'block';
}
window.abrirModalExcluirPlano = abrirModalExcluirPlano;

function fecharModalExcluirPlano() {
    document.getElementById('modalExcluirPlano').style.display = 'none';
}
window.fecharModalExcluirPlano = fecharModalExcluirPlano;



// Corrige: atualização dinâmica dos capítulos/tópicos SEM PERDER OS ANTIGOS
async function atualizarCapitulosTopicos(plano_id) {
    let currentStep = typeof stepAtualPorPlano[plano_id] === 'undefined' ? 0 : stepAtualPorPlano[plano_id];
    const resp = await fetch('../controllers/planos_capitulos_topicos_ajax.php?plano_id=' + plano_id);
    const html = await resp.text();
    const wizard = document.getElementById('wizard-stepper-' + plano_id);
    if (wizard) {
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html.trim();
        const novoWizard = tempDiv.querySelector('.wizard-stepper-capitulos');
        if (novoWizard) {
            wizard.replaceWith(novoWizard);
            const totalSteps = novoWizard.querySelectorAll('.wizard-step-card').length;
            if (totalSteps === 1) currentStep = 0;
            inicializarWizardCapitulos(plano_id, currentStep);
            // Reatribui funções globais para HTML dinâmico
            window.abrirModalCapitulo = abrirModalCapitulo;
            window.abrirModalEditarCapitulo = abrirModalEditarCapitulo;
            window.abrirModalExcluirCapitulo = abrirModalExcluirCapitulo;
            window.abrirModalTopico = abrirModalTopico;
            window.abrirModalEditarTopico = abrirModalEditarTopico;
            window.abrirModalExcluirTopico = abrirModalExcluirTopico;
            window.abrirModalToggleCapitulo = abrirModalToggleCapitulo;
            window.abrirModalToggleTopico = abrirModalToggleTopico;
        }
    } else {
        // Sempre remover stepper e mensagem de vazio antes de inserir novo conteúdo
        const msgVazio = document.getElementById('mensagem-sem-capitulo-' + plano_id);
        if (msgVazio) msgVazio.remove();
        const wizard = document.getElementById('wizard-stepper-' + plano_id);
        if (wizard) wizard.remove();
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html.trim();
        const novoWizard = tempDiv.querySelector('.wizard-stepper-capitulos');
        const novaMsgVazio = tempDiv.querySelector('#mensagem-sem-capitulo-' + plano_id);
        // --- CORREÇÃO ABA: ativa a aba correta antes de inserir o stepper/mensagem ---
        const abaBtn = document.querySelector(`#tab-${plano_id}`);
        if (abaBtn && !abaBtn.classList.contains('active')) {
            abaBtn.click();
        }
        setTimeout(() => {
            const planoTab = document.querySelector(`#disciplina-${plano_id} .col-12`);
            if (planoTab) {
                const turmaActions = planoTab.querySelector('.turma-actions');
                if (novoWizard) {
                    if (turmaActions && turmaActions.parentNode) {
                        turmaActions.parentNode.insertBefore(novoWizard, turmaActions.nextSibling);
                    } else if (planoTab.firstChild) {
                        planoTab.insertBefore(novoWizard, planoTab.firstChild);
                    } else {
                        planoTab.appendChild(novoWizard);
                    }
                    const totalSteps = novoWizard.querySelectorAll('.wizard-step-card').length;
                    if (totalSteps === 1) currentStep = 0;
                    inicializarWizardCapitulos(plano_id, currentStep);
                    window.abrirModalCapitulo = abrirModalCapitulo;
                    window.abrirModalEditarCapitulo = abrirModalEditarCapitulo;
                    window.abrirModalExcluirCapitulo = abrirModalExcluirCapitulo;
                    window.abrirModalTopico = abrirModalTopico;
                    window.abrirModalEditarTopico = abrirModalEditarTopico;
                    window.abrirModalExcluirTopico = abrirModalExcluirTopico;
                    window.abrirModalToggleCapitulo = abrirModalToggleCapitulo;
                    window.abrirModalToggleTopico = abrirModalToggleTopico;
                } else if (novaMsgVazio) {
                    if (turmaActions && turmaActions.parentNode) {
                        turmaActions.parentNode.insertBefore(novaMsgVazio, turmaActions.nextSibling);
                    } else if (planoTab.firstChild) {
                        planoTab.insertBefore(novaMsgVazio, planoTab.firstChild);
                    } else {
                        planoTab.appendChild(novaMsgVazio);
                    }
                }
            }
        }, 100);
    }
}

// Função para inicializar wizard de capítulos (deve ser chamada após atualizar HTML)
function inicializarWizardCapitulos(planoId, stepToRestore = null) {
    const stepper = document.getElementById('wizard-stepper-' + planoId);
    if (!stepper) return;
    const stepCircles = stepper.querySelectorAll('.wizard-step-circle');
    const stepCards = stepper.querySelectorAll('.wizard-step-card');
    const btnPrevTop = document.getElementById('wizard-prev-top-' + planoId);
    const btnNextTop = document.getElementById('wizard-next-top-' + planoId);
    const btnPrev = document.getElementById('wizard-prev-' + planoId);
    const btnNext = document.getElementById('wizard-next-' + planoId);
    let currentStep = stepToRestore !== null ? stepToRestore : (stepAtualPorPlano[planoId] || 0);
    const totalSteps = stepCards.length;

    function updateWizard() {
        stepCards.forEach((card, idx) => {
            card.style.display = (idx === currentStep) ? 'block' : 'none';
        });
        stepCircles.forEach((circle, idx) => {
            if (idx === currentStep) circle.classList.add('active');
            else circle.classList.remove('active');
        });
        const badgeAtual = document.getElementById('capitulo-atual-' + planoId);
        if (badgeAtual) badgeAtual.textContent = (currentStep + 1);
        [btnPrevTop, btnPrev].forEach(btn => {
            if (btn) btn.style.display = currentStep === 0 ? 'none' : '';
        });
        [btnNextTop, btnNext].forEach(btn => {
            if (btn) btn.style.display = currentStep === totalSteps - 1 ? 'none' : '';
        });
        stepAtualPorPlano[planoId] = currentStep;
        localStorage.setItem('stepAtualPlano_' + planoId, currentStep);
    }
    stepCircles.forEach((circle, idx) => {
        circle.style.cursor = 'pointer';
        circle.onclick = function() {
            currentStep = idx;
            updateWizard();
        };
    });
    if (btnPrevTop) btnPrevTop.onclick = function() {
        if (currentStep > 0) { currentStep--; updateWizard(); }
    };
    if (btnNextTop) btnNextTop.onclick = function() {
        if (currentStep < totalSteps - 1) { currentStep++; updateWizard(); }
    };
    if (btnPrev) btnPrev.onclick = function() {
        if (currentStep > 0) { currentStep--; updateWizard(); }
    };
    if (btnNext) btnNext.onclick = function() {
        if (currentStep < totalSteps - 1) { currentStep++; updateWizard(); }
    };
    if (localStorage.getItem('stepAtualPlano_' + planoId)) {
        currentStep = parseInt(localStorage.getItem('stepAtualPlano_' + planoId));
        if (currentStep >= totalSteps) currentStep = 0;
    }
    updateWizard();
}

// Inicializa todos os wizards ao carregar
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.wizard-stepper-capitulos').forEach(function(stepper) {
        const planoId = stepper.id.replace('wizard-stepper-', '');
        inicializarWizardCapitulos(planoId);
    });
});
// --- AJAX dinâmico para capítulo (criar/editar) ---
document.getElementById('formCapituloNovo').onsubmit = async function(e) {
    e.preventDefault();
    const form = this;
    const formData = new FormData(form);
    const url = form.action;
    const plano_id = formData.get('plano_id') || formData.get('plano_id_capitulo_novo');
    const resp = await fetch(url.includes('editar_capitulo_ajax.php') ?
        '../controllers/editar_capitulo_ajax.php' : '../controllers/criar_capitulo_ajax.php', {
            method: 'POST',
            body: formData
        });
    const data = await resp.json();
    if (data.success) {
        fecharModalCapituloNovo();
        mostrarNotificacao('Capítulo salvo com sucesso!', 'success');
        if (plano_id) await atualizarCapitulosTopicos(plano_id);
    } else {
        mostrarNotificacao(data.error || 'Erro ao salvar capítulo', 'danger');
    }
};

// --- AJAX dinâmico para tópico (criar/editar) ---
document.getElementById('formTopicoNovo').onsubmit = async function(e) {
    e.preventDefault();
    const form = this;
    const formData = new FormData(form);
    const url = form.action;
    const capitulo_id = formData.get('capitulo_id') || formData.get('capitulo_id_topico_novo');
    // Buscar plano_id do capítulo via atributo data ou AJAX rápido
    let plano_id = form.getAttribute('data-plano-id');
    if (!plano_id && capitulo_id) {
        // Busca plano_id via AJAX rápido
        const resp = await fetch('../controllers/get_plano_id_by_capitulo.php?id=' + capitulo_id);
        const d = await resp.json();
        plano_id = d.plano_id;
    }
    const resp = await fetch(url.includes('editar_topico_ajax.php') ?
        '../controllers/editar_topico_ajax.php' : '../controllers/criar_topico_ajax.php', {
            method: 'POST',
            body: formData
        });
    const data = await resp.json();
    if (data.success) {
        fecharModalTopicoNovo();
        mostrarNotificacao('Tópico salvo com sucesso!', 'success');
        if (plano_id) await atualizarCapitulosTopicos(plano_id);
    } else {
        mostrarNotificacao(data.error || 'Erro ao salvar tópico', 'danger');
    }
};

// --- Exclusão AJAX dinâmica de Capítulo ---
document.getElementById('formExcluirCapitulo').onsubmit = async function(e) {
    e.preventDefault();
    const form = this;
    const formData = new FormData(form);
    const capitulo_id = formData.get('id_capitulo');
    // Buscar plano_id do capítulo via AJAX rápido
    let plano_id = null;
    if (capitulo_id) {
        const respPlano = await fetch('../controllers/get_plano_id_by_capitulo.php?id=' + capitulo_id);
        const d = await respPlano.json();
        plano_id = d.plano_id;
    }
    const resp = await fetch(form.action, {
        method: 'POST',
        body: formData
    });
    const data = await resp.json();
    if (data.success) {
        fecharModalExcluirCapitulo();
        mostrarNotificacao('Capítulo excluído com sucesso!', 'success');
        if (plano_id) await atualizarCapitulosTopicos(plano_id);
    } else {
        mostrarNotificacao(data.error || 'Erro ao excluir capítulo', 'danger');
    }
};

// --- Exclusão AJAX dinâmica de Tópico ---
document.getElementById('formExcluirTopico').onsubmit = async function(e) {
    e.preventDefault();
    const form = this;
    const formData = new FormData(form);
    const topico_id = formData.get('id_topico');
    // Buscar plano_id via capitulo_id do tópico
    let plano_id = null;
    if (topico_id) {
        const respPlano = await fetch('../controllers/get_plano_id_by_topico.php?id=' + topico_id);
        const d = await respPlano.json();
        plano_id = d.plano_id;
    }
    const resp = await fetch(form.action, {
        method: 'POST',
        body: formData
    });
    const data = await resp.json();
    if (data.success) {
        fecharModalExcluirTopico();
        mostrarNotificacao('Tópico excluído com sucesso!', 'success');
        if (plano_id) await atualizarCapitulosTopicos(plano_id);
    } else {
        mostrarNotificacao(data.error || 'Erro ao excluir tópico', 'danger');
    }
};

// --- Toggle AJAX dinâmico de Capítulo ---
document.getElementById('formToggleCapitulo').onsubmit = async function(e) {
    e.preventDefault();
    const form = this;
    const formData = new FormData(form);
    const capitulo_id = formData.get('id_capitulo');
    // Buscar plano_id do capítulo via AJAX rápido
    let plano_id = null;
    if (capitulo_id) {
        const respPlano = await fetch('../controllers/get_plano_id_by_capitulo.php?id=' + capitulo_id);
        const d = await respPlano.json();
        plano_id = d.plano_id;
    }
    const resp = await fetch(form.action, {
        method: 'POST',
        body: formData
    });
    const data = await resp.json();
    if (data.success) {
        fecharModalToggleCapitulo();
        mostrarNotificacao('Status do capítulo alterado com sucesso!', 'success');
        if (plano_id) await atualizarCapitulosTopicos(plano_id);
    } else {
        mostrarNotificacao(data.error || 'Erro ao alterar status do capítulo', 'danger');
    }
};

// --- Toggle AJAX dinâmico de Tópico ---
document.getElementById('formToggleTopico').onsubmit = async function(e) {
    e.preventDefault();
    const form = this;
    const formData = new FormData(form);
    const topico_id = formData.get('id_topico');
    // Buscar plano_id via capitulo_id do tópico
    let plano_id = null;
    if (topico_id) {
        const respPlano = await fetch('../controllers/get_plano_id_by_topico.php?id=' + topico_id);
        const d = await respPlano.json();
        plano_id = d.plano_id;
    }
    const resp = await fetch(form.action, {
        method: 'POST',
        body: formData
    });
    const data = await resp.json();
    if (data.success) {
        fecharModalToggleTopico();
        mostrarNotificacao('Status do tópico alterado com sucesso!', 'success');
        if (plano_id) await atualizarCapitulosTopicos(plano_id);
    } else {
        mostrarNotificacao(data.error || 'Erro ao alterar status do tópico', 'danger');
    }
};

// --- AJAX dinâmico para plano (criar/editar) ---
document.getElementById('formPlanoNovo').onsubmit = async function(e) {
    e.preventDefault();
    const form = this;
    const formData = new FormData(form);
    const url = form.action;
    const resp = await fetch(url, {
        method: 'POST',
        body: formData
    });
    const data = await resp.json();
    if (data.success) {
        fecharModalPlanoNovo();
        mostrarNotificacao('Plano salvo com sucesso!', 'success');
        setTimeout(() => location.reload(), 1200);
    } else {
        mostrarNotificacao(data.error || 'Erro ao salvar plano', 'danger');
    }
};
    </script>
    <?php include 'footer.php'; ?>
</body>

</html>