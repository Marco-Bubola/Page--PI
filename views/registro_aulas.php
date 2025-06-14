<?php
session_start();
if (!isset($_SESSION['usuario_nome']) || $_SESSION['usuario_tipo'] !== 'professor') {
    header('Location: index.php');
    exit();
}
require_once '../config/conexao.php';
include 'navbar.php';
include 'notificacao.php';

// Inicializar $turma_id antes de usá-lo
$turma_id = isset($_GET['turma_id']) ? intval($_GET['turma_id']) : null;

// Adicione esta linha para garantir que $professor_id está definido
$professor_id = $_SESSION['usuario_id'];

// Certifique-se de inicializar as variáveis antes de usá-las
$disciplinas = [];
$planos = [];
$capitulos = [];
$topicos = [];
$ultimas_aulas = [];
$topicos_aula = [];
$topicos_personalizados_aula = [];

// Buscar nome da turma
$turma_nome = '';
if ($turma_id) {
    $stmt = $conn->prepare('SELECT nome FROM turmas WHERE id = ?');
    $stmt->bind_param('i', $turma_id);
    $stmt->execute();
    $stmt->bind_result($turma_nome);
    $stmt->fetch();
    $stmt->close();
}

// Buscar disciplinas da turma
$disciplinas = [];
$stmt = $conn->prepare('SELECT d.id, d.nome FROM turma_disciplinas td JOIN disciplinas d ON td.disciplina_id = d.id WHERE td.turma_id = ?');
$stmt->bind_param('i', $turma_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $disciplinas[] = $row;
$stmt->close();

// Buscar planos, capítulos e tópicos para cada disciplina
$planos = []; $capitulos = []; $topicos = [];
foreach ($disciplinas as $disc) {
    $stmt = $conn->prepare('SELECT * FROM planos WHERE turma_id = ? AND disciplina_id = ?');
    $stmt->bind_param('ii', $turma_id, $disc['id']);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($plano = $res->fetch_assoc()) {
        $planos[$disc['id']] = $plano;
        $stmt2 = $conn->prepare('SELECT * FROM capitulos WHERE plano_id = ? ORDER BY ordem, id');
        $stmt2->bind_param('i', $plano['id']);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        while ($cap = $res2->fetch_assoc()) {
            $capitulos[$plano['id']][] = $cap;
            $stmt3 = $conn->prepare('SELECT * FROM topicos WHERE capitulo_id = ? ORDER BY ordem, id');
            $stmt3->bind_param('i', $cap['id']);
            $stmt3->execute();
            $res3 = $stmt3->get_result();
            while ($top = $res3->fetch_assoc()) {
                $topicos[$cap['id']][] = $top;
            }
            $stmt3->close();
        }
        $stmt2->close();
    }
    $stmt->close();
}

// Buscar últimos registros de aula da turma (todos os professores)
$ultimas_aulas = [];
$stmt = $conn->prepare("
    SELECT a.id, a.data, a.comentario, d.nome AS disciplina_nome
    FROM aulas a
    JOIN disciplinas d ON a.disciplina_id = d.id
    WHERE a.turma_id = ?
    ORDER BY a.data DESC, a.id DESC
    LIMIT 5
");
$stmt->bind_param('i', $turma_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $ultimas_aulas[] = $row;
$stmt->close();

// Buscar tópicos ministrados para cada aula
$topicos_aula = [];
if ($ultimas_aulas) {
    $aula_ids = array_column($ultimas_aulas, 'id');
    $in_aulas = implode(',', array_map('intval', $aula_ids));
    // Tópicos planejados
    $sql = "SELECT tm.aula_id, t.titulo FROM topicos_ministrados tm JOIN topicos t ON tm.topico_id = t.id WHERE tm.aula_id IN ($in_aulas)";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $topicos_aula[$row['aula_id']][] = $row['titulo'];
    }
    // Tópicos personalizados
    $topicos_personalizados_aula = [];
    $sql = "SELECT aula_id, descricao FROM topicos_personalizados WHERE aula_id IN ($in_aulas)";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $topicos_personalizados_aula[$row['aula_id']][] = $row['descricao'];
    }
}

// Buscar tópicos personalizados por plano (por disciplina/turma)
$topicosPersonalizadosPorPlano = [];
if ($turma_id && !empty($planos)) {
    foreach ($planos as $disc_id => $plano) {
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
    <title>
        Registro de Aulas<?= isset($turma_nome) && $turma_nome ? ' - ' . htmlspecialchars($turma_nome) : '' ?> - PI Page
    </title>
    <link rel="icon" type="image/png" href="../assets/img/LOGO_PAGE.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
    body {
        background: #f5f5f5;
    }

    .main-title {
        font-size: 2.3rem;
        font-weight: 800;
        color: #112130;
        margin-bottom: 0.7rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .section-title {
        font-size: 1.7rem;
        font-weight: 700;
        color: #0d6efd;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .section-desc {
        color: #666;
        font-size: 1.18rem;
        margin-bottom: 1.7rem;
    }

    .card-disciplina {
        border-radius: 18px;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.10);
        border: none;
    }

    .card-header.bg-primary {
        border-radius: 18px 18px 0 0;
        font-size: 1.25rem;
        font-weight: 700;
    }

    .capitulo-title {
        font-weight: 700;
        color: #0d6efd;
        font-size: 1.15rem;
    }

    .topico-checkbox {
        margin-right: 8px;
    }

    .topico-label {
        font-weight: 600;
    }

    .modal-lg {
        max-width: 800px;
    }

    .icon-concluido {
        color: #198754;
        margin-right: 4px;
    }

    .icon-nao-concluido {
        color: #adb5bd;
        margin-right: 4px;
    }

    .custom-divider {
        border-top: 2px solid #e9ecef;
        margin: 2.5rem 0 2rem 0;
    }

    .list-group-item-personalizado {
        background: #f8f9fa;
        color: #0d6efd;
    }

    .card-historico {
        border-radius: 18px;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.10);
        border: none;
    }

    .badge.bg-primary {
        background: #0d6efd !important;
        font-size: 1.08em;
        padding: 7px 12px;
        border-radius: 8px;
    }

    .btn-outline-primary,
    .btn-outline-danger,
    .btn,
    .btn-sm {
        border-radius: 8px;
        font-size: 1.13rem;
        padding: 10px 18px;
    }

    .form-check-input:checked {
        background-color: #198754;
        border-color: #198754;
    }

    .topico-desc {
        font-size: 1.08em;
        color: #555;
        margin-left: 1.2em;
        margin-bottom: 0.2em;
    }

    .topico-personalizado-box {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 8px 14px;
        margin-bottom: 8px;
    }

    .topico-personalizado-title {
        color: #0d6efd;
        font-weight: 700;
        font-size: 1.08em;
    }

    .topico-personalizado-desc {
        font-size: 1.08em;
        color: #444;
        margin-left: 1.2em;
    }

    .list-group-item {
        font-size: 1.13em;
        border-radius: 10px;
        margin-bottom: 8px;
    }

    @media (max-width: 767px) {
        .main-title {
            font-size: 1.4rem;
        }

        .section-title {
            font-size: 1.1rem;
        }
    }
    </style>
</head>

<body>
    <div class="container-fluid  py-4">
        <!-- Cabeçalho estilizado adicionado -->
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
                                        <h2 class="mb-0 fw-bold text-primary">
                                            Planos de
                                            Aula<?= $turma_id ? ' <span class="text-primary">da Turma: ' . (isset($turma_nome) && $turma_nome ? htmlspecialchars($turma_nome) : '') . '</span>' : '' ?>
                                        </h2>
                                        <button type="button"
                                            class="btn btn-gradient-dicas shadow-sm px-3 py-2 d-flex align-items-center gap-2 fw-bold"
                                            id="btnDicasPlanos" title="Dicas da página"
                                            style="border-radius: 14px; font-size:1.13em; background:#0d6efd; box-shadow: 0 2px 8px #0d6efd33; color: #fff;">
                                            <i class="bi bi-lightbulb-fill" style="font-size:1.35em; color: #fff;"></i>
                                            Dicas
                                        </button>
                                    </div>
                                    <!-- Modal de Dicas de Funcionamento -->
                                    <?php include __DIR__ . '/modais/modalDicasaulas.php'; ?>
                                    <!-- Fim do modal de dicas -->
                                    <div class="text-muted" style="font-size:1.08em;">
                                        <i class="bi bi-info-circle"></i>
                                        Gerencie e registre os planos de aula das suas disciplinas.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Abas de disciplinas -->
        <div class="row">
            <div class="col-12">
                <?php if ($disciplinas): ?>
                <ul class="nav nav-tabs" id="disciplinasTab" role="tablist">
                    <?php foreach ($disciplinas as $i => $disc): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link<?= $i === 0 ? ' active' : '' ?>" id="tab-<?= $disc['id'] ?>"
                            data-bs-toggle="tab" data-bs-target="#disciplina-<?= $disc['id'] ?>" type="button"
                            role="tab" aria-controls="disciplina-<?= $disc['id'] ?>"
                            aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"
                            style="font-weight:600;font-size:1.1em;display:flex;align-items:center;gap:7px;">
                            <i class="fa-solid fa-book-open text-primary"></i>
                            <?= htmlspecialchars($disc['nome']) ?>
                        </button>
                    </li>
                    <?php endforeach; ?>
                    <!-- Nova aba para Último Registro -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-ultimoregistro" data-bs-toggle="tab"
                            data-bs-target="#ultimoregistro" type="button" role="tab" aria-controls="ultimoregistro"
                            aria-selected="false"
                            style="font-weight:600;font-size:1.1em;display:flex;align-items:center;gap:7px;">
                            <i class="fa-solid fa-clock-rotate-left text-secondary"></i>
                            Último Registro
                        </button>
                    </li>
                </ul>
                <div class="tab-content p-3 bg-white rounded-bottom shadow-sm border border-top-0"
                    id="disciplinasTabContent">
                    <?php foreach ($disciplinas as $i => $disc): ?>
                    <div class="tab-pane fade<?= $i === 0 ? ' show active' : '' ?>" id="disciplina-<?= $disc['id'] ?>"
                        role="tabpanel" aria-labelledby="tab-<?= $disc['id'] ?>">
                        <div class="col-12">
                            <!-- Texto explicativo e botão de registrar aula na mesma linha -->
                            <div class="mb-4">
                                <div class="d-flex align-items-center justify-content-between gap-3">
                                    <div class="alert alert-info d-flex align-items-center gap-2 mb-0 py-2 px-3"
                                        style="font-size:1.13em; flex:1;">
                                        <i class="fa-solid fa-circle-info me-2"></i>
                                        Registre uma nova aula para esta disciplina, marcando os tópicos ministrados e
                                        adicionando observações se necessário.
                                    </div>
                                    <button
                                        class="btn btn-success btn-lg fw-bold shadow-sm d-flex align-items-center gap-2 px-4 py-2"
                                        style="border-radius: 14px; font-size:1.18em; box-shadow: 0 2px 8px #19875433;"
                                        onclick="abrirModalAula(<?= $disc['id'] ?>, '<?= htmlspecialchars(addslashes($disc['nome'])) ?>')">
                                        <i class="fa-solid fa-plus"></i> Registrar Aula
                                    </button>
                                </div>
                            </div>
                            <?php if (isset($planos[$disc['id']])): $plano = $planos[$disc['id']]; ?>
                            <?php
            $capitulosDisc = $capitulos[$plano['id']] ?? [];
            ?>
                            <?php if ($capitulosDisc): ?>
                            <!-- Stepper de capítulos -->
                            <div class="wizard-stepper-capitulos mb-4" id="wizard-stepper-<?= $plano['id'] ?>">
                                <div class="d-flex flex-row align-items-center justify-content-center gap-4 mb-3"
                                    style="gap: 48px !important;">
                                    <button class="btn btn-outline-primary me-2" id="wizard-prev-<?= $plano['id'] ?>"
                                        style="min-width:90px;"><i class="fa fa-arrow-left"></i> Anterior</button>
                                    <div class="d-flex flex-row align-items-center gap-4" style="gap: 48px !important;">
                                        <?php foreach ($capitulosDisc as $idx => $cap): ?>
                                        <div class="wizard-step-circle position-relative <?= $idx === 0 ? 'active' : '' ?>"
                                            data-step="<?= $idx ?>"
                                            style="width:54px;height:54px;border-radius:50%;background:<?= $cap['status']==='concluido'?'#28a745':'#0d6efd' ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.7rem;font-weight:bold;cursor:pointer;transition:box-shadow .2s;box-shadow:0 2px 8px #0001;">
                                            <i class="fa-solid fa-book"></i>
                                            <?php if ($idx < count($capitulosDisc)-1): ?>
                                            <div class="wizard-step-line position-absolute top-50 start-100 translate-middle-y"
                                                style="height:5px;width:60px;background:#b6d4fe;z-index:0;"></div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button class="btn btn-outline-primary ms-2" id="wizard-next-<?= $plano['id'] ?>"
                                        style="min-width:90px;">Próximo <i class="fa fa-arrow-right"></i></button>
                                </div>
                                <div class="wizard-step-content position-relative" style="min-height:200px;">
                                    <?php foreach ($capitulosDisc as $idx => $cap): ?>
                                    <div class="wizard-step-card" data-step="<?= $idx ?>"
                                        style="display:<?= $idx===0?'block':'none' ?>;">
                                        <?php
                            $capStatus = $cap['status'];
                            $capIcon = $capStatus === 'concluido' ? 'fa-circle-check text-success' : 'fa-book-open text-primary';
                            $capBadge = $capStatus === 'concluido'
                                ? '<span class="badge bg-success ms-2"><i class="fa-solid fa-check"></i> Concluído</span>'
                                : '<span class="badge bg-primary ms-2"><i class="fa-solid fa-hourglass-half"></i> Em andamento</span>';
                        ?>
                                        <div class="card bg-white shadow-sm mb-3 border border-2 border-primary"
                                            style="border-radius:18px;">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center mb-2 gap-3">
                                                    <span
                                                        class="d-flex align-items-center justify-content-center rounded-circle"
                                                        style="width:48px;height:48px;font-size:2rem;background:#f8f9fa;box-shadow:0 2px 8px #0001;">
                                                        <i class="fa-solid <?= $capIcon ?>"></i>
                                                    </span>
                                                    <div>
                                                        <div class="capitulo-title mb-1" style="font-size:1.3rem;">
                                                            <i class="fa-solid fa-bookmark text-warning me-1"></i>
                                                            <?= htmlspecialchars($cap['titulo']) ?>
                                                            <?= $capBadge ?>
                                                        </div>
                                                        <div class="text-muted" style="font-size:1.08em;">
                                                            <i class="fa-solid fa-align-left me-1 text-secondary"></i>
                                                            <?= nl2br(htmlspecialchars($cap['descricao'])) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <hr>
                                                <div>
                                                    <div class="fw-bold mb-2" style="font-size:1.13em;">
                                                        <i class="fa-solid fa-list-check text-primary"></i> Tópicos
                                                        deste capítulo:
                                                    </div>
                                                    <?php if (!empty($topicos[$cap['id']])): ?>
                                                    <div class="row g-3">
                                                        <?php foreach ($topicos[$cap['id']] as $top): ?>
                                                        <?php
                                                $topStatus = $top['status'];
                                                $topBg = $topStatus === 'concluido' ? 'bg-success-subtle' : 'bg-light';
                                                $topIcon = $topStatus === 'concluido' ? 'fa-circle-check text-success' : 'fa-circle text-primary';
                                                $topBorder = $topStatus === 'concluido' ? 'border-success' : 'border-primary';
                                            ?>
                                                        <div class="col-12 col-md-6">
                                                            <div class="p-3 rounded <?= $topBg ?> <?= $topBorder ?>"
                                                                style="border:2px solid; border-radius:12px; min-height:70px;">
                                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                                    <i class="fa-solid <?= $topIcon ?>"
                                                                        style="font-size:1.3em;"></i>
                                                                    <span class="fw-semibold" style="font-size:1.13em;">
                                                                        <i
                                                                            class="fa-solid fa-lightbulb text-warning me-1"></i>
                                                                        <?= htmlspecialchars($top['titulo']) ?>
                                                                    </span>
                                                                    <?php if ($topStatus === 'concluido'): ?>
                                                                    <span class="badge bg-success ms-2"><i
                                                                            class="fa-solid fa-check"></i>
                                                                        Concluído</span>
                                                                    <?php else: ?>
                                                                    <span class="badge bg-primary ms-2"><i
                                                                            class="fa-solid fa-hourglass-half"></i> Em
                                                                        andamento</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <?php if (!empty($top['descricao'])): ?>
                                                                <div class="topico-desc">
                                                                    <i
                                                                        class="fa-solid fa-align-left me-1 text-secondary"></i>
                                                                    <?= nl2br(htmlspecialchars($top['descricao'])) ?>
                                                                </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <?php else: ?>
                                                    <div class="text-muted"><i
                                                            class="fa-solid fa-circle-exclamation text-warning"></i>
                                                        Nenhum tópico cadastrado neste capítulo.</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="text-muted">Nenhum capítulo cadastrado.</div>
                            <?php endif; ?>
                            <?php
// Exibir tópicos personalizados no mesmo estilo dos tópicos planejados
if (!empty($topicosPersonalizadosPorPlano[$plano['id']])): ?>
                            <div class="mt-2">
                                <div class="d-flex justify-content-center align-items-center mb-3"
                                    style="background: linear-gradient(90deg,#fffbe6 60%,#fff8dc 100%); border-radius: 12px; padding: 12px 0;">
                                    <span class="me-2" style="font-size:2rem; color:#ffc107;">
                                        <i class="fa-solid fa-lightbulb"></i>
                                    </span>
                                    <span class="fw-bold" style="font-size:1.25rem; color:#b8860b;">
                                        Tópicos personalizados ministrados
                                    </span>
                                </div>
                                <div class="row g-3 mt-1">
                                    <?php foreach ($topicosPersonalizadosPorPlano[$plano['id']] as $desc): ?>
                                    <div class="col-12 col-md-6">
                                        <div class="p-3 rounded bg-warning-subtle border-warning mb-2"
                                            style="border:2px solid; border-radius:12px; min-height:70px;">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <i class="fa-solid fa-lightbulb text-warning"
                                                    style="font-size:1.3em;"></i>
                                                <?= nl2br(htmlspecialchars($desc)) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php else: ?>
                            <div class="text-muted">Nenhum plano cadastrado para esta disciplina.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <!-- Conteúdo da aba Último Registro -->
                    <div class="tab-pane fade" id="ultimoregistro" role="tabpanel" aria-labelledby="tab-ultimoregistro">
                        <div class="mb-4">
                            <div class="alert alert-info d-flex align-items-center gap-2 mb-0 py-2 px-3"
                                style="font-size:1.13em;">
                                <i class="fa-solid fa-circle-info me-2"></i>
                                Veja o histórico das últimas aulas registradas nesta turma, incluindo tópicos planejados
                                e personalizados.
                            </div>
                        </div>
                        <div class="card card-historico shadow-lg border-3 border-primary mb-5"
                            style="background: linear-gradient(90deg, #eaf1fb 60%, #f8fafc 100%);">
                            <div class="card-body">
                                <?php if ($ultimas_aulas): ?>
                                <div class="row">
                                    <?php foreach ($ultimas_aulas as $aula): ?>
                                    <div class="col-md-6">
                                        <div class="list-group-item py-4 px-3 mb-3 rounded-4 shadow-sm border-2 border-primary"
                                            style="background: #fff;">
                                            <div class="d-flex align-items-center mb-2 gap-3">
                                                <span
                                                    class="badge bg-primary fs-6 d-flex align-items-center gap-2 px-3 py-2"
                                                    style="border-radius: 12px;">
                                                    <i class="fa-solid fa-book"></i>
                                                    <?= htmlspecialchars($aula['disciplina_nome']) ?>
                                                </span>
                                                <span
                                                    class="badge bg-info text-dark fs-6 d-flex align-items-center gap-2 px-3 py-2"
                                                    style="border-radius: 12px;">
                                                    <i class="fa-solid fa-calendar-day"></i>
                                                    <?= date('d/m/Y', strtotime($aula['data'])) ?>
                                                </span>
                                            </div>
                                            <div class="mb-2">
                                                <b class="text-primary"><i
                                                        class="fa-solid fa-list-check me-1"></i>Tópicos ministrados:</b>
                                                <?php if (!empty($topicos_aula[$aula['id']])): ?>
                                                <span class="ms-1">
                                                    <?=
                                                                implode(
                                                                    ', ',
                                                                    array_map(
                                                                        fn($t) => '<span class="badge bg-success-subtle text-success border border-success me-1"><i class="fa-solid fa-circle-check"></i> ' . htmlspecialchars($t) . '</span>',
                                                                        $topicos_aula[$aula['id']]
                                                                    )
                                                                )
                                                            ?>
                                                </span>
                                                <?php else: ?>
                                                <span class="text-muted ms-1">Nenhum tópico registrado</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($topicos_personalizados_aula[$aula['id']])): ?>
                                            <div class="mb-2">
                                                <b class="text-warning"><i class="fa-solid fa-lightbulb"></i> Tópicos
                                                    personalizados:</b>
                                                <ul class="list-group list-group-flush ms-2">
                                                    <?php foreach ($topicos_personalizados_aula[$aula['id']] as $desc): ?>
                                                    <li
                                                        class="list-group-item list-group-item-personalizado border-0 ps-0">
                                                        <span
                                                            class="badge bg-warning-subtle text-warning border border-warning">
                                                            <i class="fa-solid fa-lightbulb"></i>
                                                            <?= htmlspecialchars($desc) ?>
                                                        </span>
                                                    </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                            <?php endif; ?>
                                            <?php if (!empty($aula['comentario'])): ?>
                                            <div class="mt-2">
                                                <span
                                                    class="badge bg-secondary-subtle text-dark border border-secondary px-3 py-2"
                                                    style="font-size:1.08em;">
                                                    <i class="fa-solid fa-comment-dots me-1"></i>
                                                    <?= nl2br(htmlspecialchars($aula['comentario'])) ?>
                                                </span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <div class="text-muted"><i class="fa-solid fa-circle-exclamation me-1"></i>Nenhum
                                    registro de aula encontrado.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>


    </div>

    <!-- Modal de Registro de Aula -->
    <?php include __DIR__ . '/modais/modal_registrar_aula.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.js"></script>
    <script>
    const planos = <?= json_encode($planos) ?>;
    const capitulos = <?= json_encode($capitulos) ?>;
    const topicos = <?= json_encode($topicos) ?>;

    function adicionarTopicoPersonalizado() {
        const box = document.getElementById('topicos_personalizados_box');
        const div = document.createElement('div');
        div.className = 'input-group mb-2 topico-personalizado';
        div.innerHTML = `
        <input type="text" name="topicos_personalizados[]" class="form-control" placeholder="Descreva o tópico ministrado" required>
        <button type="button" class="btn btn-outline-danger" onclick="this.parentNode.remove()">Remover</button>
    `;
        box.appendChild(div);
    }

    function abrirModalAula(disc_id, disc_nome) {
        document.getElementById('formAula').reset();
        document.getElementById('aula_disciplina_id').value = disc_id;
        document.getElementById('modalAulaLabel').innerText = 'Registrar Aula - ' + disc_nome;
        document.getElementById('modalDisciplinaNome').innerText = disc_nome;
        document.getElementById('topicos_personalizados_box').innerHTML = '';
        // Steps do modal
        let modalStep = 1;
        document.getElementById('modal-step-1').style.display = '';
        document.getElementById('modal-step-2').style.display = 'none';
        document.getElementById('btn-modal-prev').style.display = 'none';
        document.getElementById('btn-modal-next').style.display = '';
        document.getElementById('btn-modal-save').style.display = 'none';
        setModalProgressStep(1);
        // Corrigido: só troca classe se o elemento existir
        const step1Ind = document.getElementById('modal-step-1-ind');
        const step2Ind = document.getElementById('modal-step-2-ind');
        if (step1Ind && step2Ind) {
            step1Ind.classList.remove('bg-secondary');
            step1Ind.classList.add('bg-primary');
            step2Ind.classList.remove('bg-primary');
            step2Ind.classList.add('bg-secondary');
        }

        const plano = planos[disc_id];
        if (plano && capitulos[plano.id]) {
            const capitulosArr = capitulos[plano.id];
            let currentStep = 0;
            renderStepperCapitulos(plano, capitulosArr, currentStep);

            function renderCapituloStep(idx) {
                const cap = capitulosArr[idx];
                // Atualiza nome do capítulo sempre que trocar
                document.getElementById('nome_capitulo_modal').innerText = cap.titulo;
                let html = `<div class="row g-3">`;
                (topicos[cap.id] || []).forEach(top => {
                    const topStatus = top.status;
                    const topBg = topStatus === 'concluido' ? 'bg-success-subtle' : 'bg-light';
                    const topIcon = topStatus === 'concluido' ? 'fa-circle-check text-success' :
                        'fa-circle text-primary';
                    const topBorder = topStatus === 'concluido' ? 'border-success' : 'border-primary';
                    // Toggle switch para seleção
                    html += `
                    <div class="col-12 col-md-6">
                        <div class="p-3 rounded ${topBg} ${topBorder} mb-2" style="border:2px solid; border-radius:12px; min-height:90px;">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="fa-solid ${topIcon}" style="font-size:1.3em;"></i>
                                <span class="fw-semibold" style="font-size:1.13em;">
                                    <i class="fa-solid fa-lightbulb text-warning me-1"></i>
                                    ${top.titulo}
                                </span>
                                ${topStatus === 'concluido'
                                    ? '<span class="badge bg-success ms-2"><i class="fa-solid fa-check"></i> Concluído</span>'
                                    : '<span class="badge bg-primary ms-2"><i class="fa-solid fa-hourglass-half"></i> Em andamento</span>'}
                            </div>
                            ${top.descricao ? `<div class="topico-desc"><i class="fa-solid fa-align-left me-1 text-secondary"></i> ${top.descricao.replace(/\n/g, '<br>')}</div>` : ''}
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="topicos[]" value="${top.id}" id="topico_${top.id}" ${topStatus === 'concluido' ? 'checked disabled' : ''}>
                                <label class="form-check-label" for="topico_${top.id}">Selecionar</label>
                            </div>
                        </div>
                    </div>
                `;
                });
                html += '</div>';
                document.getElementById('topicos_aula_box').innerHTML = html;
            }
            renderCapituloStep(currentStep);
            setTimeout(() => {
                const steps = document.querySelectorAll('#stepper_capitulos_modal .wizard-step-circle');
                const btnPrev = document.getElementById('modal-stepper-prev');
                const btnNext = document.getElementById('modal-stepper-next');

                function updateStep(idx) {
                    steps.forEach((el, i) => el.classList.toggle('border-3', i === idx));
                    renderCapituloStep(idx);
                    if (btnPrev) btnPrev.disabled = idx === 0;
                    if (btnNext) btnNext.disabled = idx === steps.length - 1;
                }
                steps.forEach((el, idx) => {
                    el.onclick = () => {
                        currentStep = idx;
                        updateStep(currentStep);
                    };
                });
                if (btnPrev) btnPrev.onclick = () => {
                    if (currentStep > 0) {
                        currentStep--;
                        updateStep(currentStep);
                    }
                };
                if (btnNext) btnNext.onclick = () => {
                    if (currentStep < steps.length - 1) {
                        currentStep++;
                        updateStep(currentStep);
                    }
                };
                updateStep(currentStep);
            }, 100);
        } else {
            document.getElementById('stepper_capitulos_modal').innerHTML = '';
            document.getElementById('topicos_aula_box').innerHTML =
                '<div class="text-muted">Nenhum capítulo/tópico disponível.</div>';
            document.getElementById('nome_capitulo_modal').innerText = '';
        }
        new bootstrap.Modal(document.getElementById('modalAula')).show();

        // Navegação dos steps do modal
        document.getElementById('btn-modal-next').onclick = function() {
            modalStep = 2;
            document.getElementById('modal-step-1').style.display = 'none';
            document.getElementById('modal-step-2').style.display = '';
            document.getElementById('btn-modal-prev').style.display = '';
            document.getElementById('btn-modal-next').style.display = 'none';
            document.getElementById('btn-modal-save').style.display = '';
            setModalProgressStep(2);
        };
        document.getElementById('btn-modal-prev').onclick = function() {
            modalStep = 1;
            document.getElementById('modal-step-1').style.display = '';
            document.getElementById('modal-step-2').style.display = 'none';
            document.getElementById('btn-modal-prev').style.display = 'none';
            document.getElementById('btn-modal-next').style.display = '';
            document.getElementById('btn-modal-save').style.display = 'none';
            setModalProgressStep(1);
        };
    }
    // Validação Bootstrap
    (function() {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })();
    // Envio do formulário via AJAX
    document.getElementById('formAula').onsubmit = async function(e) {
        e.preventDefault();
        if (!this.checkValidity()) return;
        const formData = new FormData(this);
        const resp = await fetch('../controllers/registrar_aula.php', {
            method: 'POST',
            body: formData
        });
        const data = await resp.json();
        if (data.success) {
            mostrarNotificacao('Aula registrada com sucesso!', 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            mostrarNotificacao(data.error || 'Erro ao registrar aula', 'danger');
        }
    };

    // Stepper navegação (wizard) para capítulos
    document.addEventListener('DOMContentLoaded', function() {
        <?php foreach ($planos as $plano): ?>
            (function() {
                const planoId = <?= $plano['id'] ?>;
                const stepper = document.getElementById('wizard-stepper-' + planoId);
                if (!stepper) return;
                const stepCircles = stepper.querySelectorAll('.wizard-step-circle');
                const stepCards = stepper.querySelectorAll('.wizard-step-card');
                const btnPrev = document.getElementById('wizard-prev-' + planoId);
                const btnNext = document.getElementById('wizard-next-' + planoId);
                let currentStep = 0;

                function updateWizard() {
                    stepCards.forEach((card, idx) => {
                        card.style.display = (idx === currentStep) ? 'block' : 'none';
                    });
                    stepCircles.forEach((circle, idx) => {
                        if (idx === currentStep) circle.classList.add('active');
                        else circle.classList.remove('active');
                    });
                    if (btnPrev) btnPrev.style.display = currentStep === 0 ? 'none' : '';
                    if (btnNext) btnNext.style.display = currentStep === stepCards.length - 1 ? 'none' : '';
                }
                stepCircles.forEach((circle, idx) => {
                    circle.style.cursor = 'pointer';
                    circle.onclick = function() {
                        currentStep = idx;
                        updateWizard();
                    };
                });
                if (btnPrev) btnPrev.onclick = function() {
                    if (currentStep > 0) {
                        currentStep--;
                        updateWizard();
                    }
                };
                if (btnNext) btnNext.onclick = function() {
                    if (currentStep < stepCards.length - 1) {
                        currentStep++;
                        updateWizard();
                    }
                };
                updateWizard();
            })();
        <?php endforeach; ?>
    });

    // Abrir modal de dicas ao clicar no botão
    document.getElementById('btnDicasPlanos').onclick = function() {
        var modal = new bootstrap.Modal(document.getElementById('modalDicasAulas'));
        modal.show();
    };
    </script>
    <?php include 'footer.php'; ?>
</body>

</html>