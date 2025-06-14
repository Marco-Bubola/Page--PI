<?php
session_start();
if (!isset($_SESSION['usuario_nome']) || ($_SESSION['usuario_tipo'] !== 'coordenador' && $_SESSION['usuario_tipo'] !== 'admin')) {
    header('Location: index.php');
    exit();
}
$nome = $_SESSION['usuario_nome'];
include 'navbar.php';
include 'notificacao.php';
require_once '../config/conexao.php';
// Buscar até 12 disciplinas para o carrossel
$disciplinas = [];
$sql = 'SELECT * FROM disciplinas ORDER BY nome LIMIT 12';
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $disciplinas[] = $row;
    }
}
// Buscar turmas
$turmas = [];
$sql = 'SELECT * FROM turmas ORDER BY ano_letivo DESC, nome';
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $turmas[] = $row;
    }
}
// Buscar disciplinas vinculadas para cada turma
$turmaDisciplinas = [];
$sql = 'SELECT turma_id, disciplina_id FROM turma_disciplinas';
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $turmaDisciplinas[$row['turma_id']][] = $row['disciplina_id'];
    }
}
// Buscar planos de aula por turma e disciplina
$planosTurmaDisc = [];
$sql = 'SELECT p.id, p.turma_id, p.disciplina_id, p.titulo, p.status FROM planos p';
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $planosTurmaDisc[$row['turma_id']][$row['disciplina_id']] = $row;
    }
}
// Buscar nomes das disciplinas (para exibir nas turmas)
$disciplinasNomes = [];
$sql = 'SELECT id, nome FROM disciplinas';
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $disciplinasNomes[$row['id']] = $row['nome'];
    }
}
// Buscar total de usuários
$total_usuarios = 0;
$res = $conn->query('SELECT COUNT(*) as total FROM usuarios');
if ($res && $row = $res->fetch_assoc()) $total_usuarios = $row['total'];
// Buscar total de planos
$total_planos = 0;
$res = $conn->query('SELECT COUNT(*) as total FROM planos');
if ($res && $row = $res->fetch_assoc()) $total_planos = $row['total'];
// Buscar últimos 6 planos
$ultimos_planos = [];
$res = $conn->query('SELECT p.id, p.titulo, p.status, p.criado_em, d.nome AS disciplina_nome, t.nome AS turma_nome FROM planos p JOIN disciplinas d ON p.disciplina_id = d.id JOIN turmas t ON p.turma_id = t.id ORDER BY p.criado_em DESC LIMIT 12');
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) $ultimos_planos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Home Coordenador - PI Page</title>
    <link rel="stylesheet" href="../assets/css/css_base_page.css">
    <link rel="icon" type="image/png" href="../assets/img/LOGO_PAGE.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
 
    /* Adicione ajustes específicos se necessário, mas a base já está no css_base_page.css */
    .card-home {
        border-radius: 18px;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.10);
    }
    .section-title {
        font-size: 2rem;
        font-weight: 700;
        color: #222;
        margin-bottom: 1.2rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .section-desc {
        color: #666;
        font-size: 1.15rem;
        margin-bottom: 1.5rem;
    }
    .badge {
        font-size: 1em;
        padding: 7px 12px;
    }
    .turma-status-badge {
        font-size: 1em;
        font-weight: 600;
        border-radius: 8px;
        margin-left: 0.2em;
    }
    .card-turma {
        border-radius: 18px;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.13);
        background: linear-gradient(120deg, #f8fafc 80%, #e3f0ff 100%);
        border: none;
        transition: transform 0.13s, box-shadow 0.13s;
        position: relative;
        margin-bottom: 24px;
    }
    .card-turma:hover {
        transform: translateY(-4px) scale(1.01);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.16);
    }
    .card-turma .card-title {
        font-size: 1.18rem;
        font-weight: 700;
        color: #222;
    }
    .card-turma .disciplina-status {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 7px 10px;
        margin-bottom: 6px;
        font-size: 1.08em;
        box-shadow: 0 1px 4px rgba(13, 110, 253, 0.04);
    }
    .card-turma .badge.bg-success {
        background: #e6f9ea;
        color: #198754;
        border: 1.5px solid #19875433;
    }
    .card-turma .badge.bg-warning.text-dark {
        background: #fffbe6;
        color: #ffc107;
        border: 1.5px solid #ffc10733;
    }
    .card-turma .badge.bg-light.text-dark {
        background: #f8f9fa;
        color: #333;
        border: 1.5px solid #adb5bd33;
    }
    .status-overlay {
        font-size: 1.2rem;
        padding: 0.5em 1.5em;
        border-radius: 1em;
        font-weight: bold;
        z-index: 2;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
        position: absolute;
        top: 12px;
        left: 12px;
        background: #e9ecef;
        color: #333;
    }
    .barra-turmas {
        background: var(--cor-primaria);
        border-radius: 10px;
    }
    .barra-turmas .btn {
        background: #fff;
        color: var(--cor-primaria);
        font-weight: 600;
    }
    .barra-turmas .btn:hover {
        background: var(--cor-destaque);
        color: #fff;
    }
    #turmas-pagination-nav .pagination .page-link {
        color: var(--cor-primaria);
        background: #fff;
        border: 1px solid var(--cor-primaria);
        border-radius: 6px;
        margin-left: 2px;
        margin-right: 2px;
        transition: background 0.2s, color 0.2s;
    }
    #turmas-pagination-nav .pagination .page-item.active .page-link,
    #turmas-pagination-nav .pagination .page-link:hover {
        background: var(--cor-primaria);
        color: #fff;
        border-color: var(--cor-primaria);
    }
    #turmas-pagination-nav .pagination {
        margin-bottom: 0;
    }
    /* Disciplinas em destaque */
    .disciplinas-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 18px;
    }
    .disc-card {
        background: linear-gradient(120deg, #f8fafc 80%, #e3f0ff 100%);
        border-radius: 14px;
        box-shadow: 0 2px 12px rgba(17,33,48,0.07);
        min-width: 0;
        flex: 1 1 30%;
        max-width: 32%;
        padding: 22px 10px 16px 10px;
        text-align: center;
        font-weight: 600;
        color: var(--cor-primaria);
        position: relative;
        transition: box-shadow 0.2s, transform 0.2s;
        border: 2px solid #e3f0ff;
    }
    .disc-card:hover {
        box-shadow: 0 6px 24px #2979ff22;
        transform: translateY(-2px) scale(1.03);
        border-color: var(--cor-destaque);
    }
    .disc-card .disc-icon {
        font-size: 2.2em;
        margin-bottom: 8px;
        color: var(--cor-destaque);
        display: block;
    }
    .disc-card .disc-nome {
        font-size: 1.13em;
        font-weight: 700;
        word-break: break-word;
    }
    /* Últimos planos grid */
    .planos-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 18px;
    }
    .plano-card {
        background: linear-gradient(120deg, #f8fafc 80%, #e3f0ff 100%);
        border-radius: 14px;
        box-shadow: 0 2px 12px rgba(17,33,48,0.07);
        flex: 1 1 22%;
        max-width: 23%;
        min-width: 220px;
        padding: 18px 14px 14px 14px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        border: 2px solid #e3f0ff;
        transition: box-shadow 0.2s, transform 0.2s;
        position: relative;
    }
    .plano-card:hover {
        box-shadow: 0 6px 24px #2979ff22;
        transform: translateY(-2px) scale(1.03);
        border-color: var(--cor-destaque);
    }
    .plano-card .plano-title {
        font-size: 1.12em;
        font-weight: 700;
        color: var(--cor-primaria);
        margin-bottom: 0.3em;
        display: flex;
        align-items: center;
        gap: 7px;
    }
    .plano-card .plano-info {
        font-size: 0.98em;
        color: #444;
        margin-bottom: 0.2em;
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }
    .plano-card .badge {
        font-size: 0.97em;
        padding: 5px 10px;
        border-radius: 7px;
        margin-right: 3px;
        margin-bottom: 2px;
    }
    .plano-card .plano-footer {
        font-size: 0.93em;
        color: #888;
        margin-top: 0.7em;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .plano-card .plano-icon {
        font-size: 1.5em;
        color: var(--cor-destaque);
        margin-right: 5px;
    }
    @media (max-width: 1200px) {
        .planos-grid .plano-card { max-width: 48%; flex-basis: 48%; }
        .disciplinas-grid .disc-card { max-width: 48%; flex-basis: 48%; }
    }
    @media (max-width: 768px) {
        .planos-grid, .disciplinas-grid { flex-direction: column; gap: 12px; }
        .planos-grid .plano-card, .disciplinas-grid .disc-card { max-width: 100%; flex-basis: 100%; }
    }
    </style>
</head>
<body>
    <div class="container-fluid py-2">
        <div class="row">
            <div class="col-12">
                <!-- Cabeçalho igual ao do professor -->
                <div class="col-12 mb-2">
                    <div class="bg-white rounded shadow-sm p-1 mb-1 border border-3 border-primary position-relative">
                        <div class="row align-items-end g-2 mb-2">
                            <div class="col-lg-7 col-md-7 col-12">
                                <div class="d-flex align-items-center gap-3 h-100">
                                    <span class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                                        style="width:56px;height:56px;font-size:2.2rem;box-shadow:0 2px 8px #0d6efd33;">
                                        <i class="bi bi-person-badge-fill"></i>
                                    </span>
                                    <div>
                                        <h2 class="mb-0 fw-bold text-primary">Bem-vindo, Coordenador</h2>
                                        <div class="text-muted" style="font-size:1.08em;">
                                            <i class="bi bi-info-circle"></i>
                                            Olá, <strong><?= htmlspecialchars($nome) ?></strong>!<br>
                                            Aqui você pode acompanhar as turmas, disciplinas, planos de aula e usuários do sistema.
                                        </div>
                                        <div class="mt-2 d-flex flex-wrap gap-2">
                                            <span class="badge bg-primary-subtle text-primary border border-primary d-flex align-items-center gap-1"
                                                style="font-size:1.08em;">
                                                <i class="bi bi-collection"></i> Turmas: <b><?= count($turmas) ?></b>
                                            </span>
                                            <span class="badge bg-info-subtle text-info border border-info d-flex align-items-center gap-1"
                                                style="font-size:1.08em;">
                                                <i class="bi bi-journal-text"></i> Disciplinas: <b><?= count($disciplinasNomes) ?></b>
                                            </span>
                                            <span class="badge bg-warning-subtle text-warning border border-warning d-flex align-items-center gap-1"
                                                style="font-size:1.08em;">
                                                <i class="bi bi-journal-bookmark-fill"></i> Planos: <b><?= $total_planos ?></b>
                                            </span>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary d-flex align-items-center gap-1"
                                                style="font-size:1.08em;">
                                                <i class="bi bi-people"></i> Usuários: <b><?= $total_usuarios ?></b>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5 col-md-5 col-12 d-flex justify-content-lg-end justify-content-start mt-2 mt-lg-0 align-items-end gap-2">
                                <?php if (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'admin'): ?>
                                    <a href="gerenciar_usuarios.php"
                                        class="btn btn-gradient-dicas shadow-sm px-3 py-2 d-flex align-items-center gap-2 fw-bold"
                                        style="border-radius: 14px; font-size:1.13em; box-shadow: 0 2px 8px #0d6efd33; min-width: 110px; max-width: 160px;">
                                        <i class="bi bi-people-fill" style="font-size:1.35em;"></i>
                                        <span>Gerenciar Usuários</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Grid de cards igual ao professor -->
                <div class="row g-4">
                    <div class="col-12 col-md-6">
                        <div class="card card-home p-4 h-100">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="section-title" style="color: var(--cor-destaque);">
                                    <i class="fa-solid fa-users-viewfinder me-2" style="color: var(--cor-destaque);"></i>
                                    Turmas, Disciplinas e Planos
                                </span>
                            </div>
                            <div class="section-desc">Veja as turmas cadastradas, suas disciplinas vinculadas e o status dos planos de aula.</div>
                            <!-- Barra com cor primária -->
                            <div class="barra-turmas d-flex align-items-center justify-content-between mb-3 px-3 py-2">
                                <a href="turmas.php"
                                    class="btn btn-light btn-sm d-flex align-items-center gap-2 px-3 py-2 shadow"
                                    style="font-weight:600; border-radius:10px; font-size:1.08rem;">
                                    <i class="fa-solid fa-users-viewfinder"></i> Ver Todas as Turmas
                                </a>
                                <div id="turmas-pagination-nav" class="mb-0"></div>
                            </div>
                            <div id="turmas-cards-container"></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <!-- Disciplinas em destaque estilizado -->
                        <div class="card card-home p-4 mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="section-title" style="color: var(--cor-destaque);">
                                    <i class="fa-solid fa-book me-2" style="color: var(--cor-destaque);"></i>
                                    Disciplinas em destaque
                                </span>
                                <a href="disciplinas.php" class="btn btn-outline-primary btn-sm ms-2">Ver Disciplinas</a>
                            </div>
                            <div class="section-desc">Veja as principais disciplinas cadastradas no sistema.</div>
                            <div class="disciplinas-grid">
                                <?php
                                // Mostra até 12 disciplinas e usa sempre o mesmo ícone
                                foreach ($disciplinas as $disc):
                                ?>
                                    <div class="disc-card">
                                        <span class="disc-icon"><i class="fa-solid fa-book"></i></span>
                                        <span class="disc-nome"><?= htmlspecialchars($disc['nome']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <!-- Últimos Planos em grid estiloso -->
                        <div class="card card-home p-4">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="section-title" style="color: var(--cor-destaque);">
                                    <i class="fa-solid fa-chalkboard me-2" style="color: var(--cor-destaque);"></i>
                                    Últimos Planos de Aula
                                </span>
                            </div>
                            <div class="section-desc">Acompanhe os planos de aula mais recentes criados no sistema.</div>
                            <div class="row g-3">
                                <?php if ($ultimos_planos): foreach ($ultimos_planos as $plano):
                                    // Buscar capítulos e tópicos deste plano
                                    $capCount = 0;
                                    $topCount = 0;
                                    $plano_id = intval($plano['id']);
                                    $sqlCap = "SELECT id FROM capitulos WHERE plano_id = $plano_id";
                                    $resCap = $conn->query($sqlCap);
                                    $capIds = [];
                                    if ($resCap && $resCap->num_rows > 0) {
                                        $capCount = $resCap->num_rows;
                                        while ($rowCap = $resCap->fetch_assoc()) $capIds[] = $rowCap['id'];
                                        if (count($capIds) > 0) {
                                            $capIdsStr = implode(',', $capIds);
                                            $sqlTop = "SELECT COUNT(*) as total FROM topicos WHERE capitulo_id IN ($capIdsStr)";
                                            $resTop = $conn->query($sqlTop);
                                            if ($resTop && $rowTop = $resTop->fetch_assoc()) {
                                                $topCount = intval($rowTop['total']);
                                            }
                                        }
                                    }
                                ?>
                                <div class="col-12 col-sm-6  col-lg-3 ">
                                    <div class="plano-card h-100">
                                        <div>
                                            <div class="plano-title" style="color: var(--cor-destaque);">
                                                <span class="plano-icon"><i class="fa-solid fa-file-lines"></i></span>
                                                <?= htmlspecialchars($plano['titulo']) ?>
                                            </div>
                                            <div class="plano-info">
                                                <span class="badge bg-primary"><i class="fa-solid fa-book"></i> <?= htmlspecialchars($plano['disciplina_nome']) ?></span>
                                                <span class="badge bg-info text-dark"><i class="fa-solid fa-users"></i> <?= htmlspecialchars($plano['turma_nome']) ?></span>
                                                <span class="badge <?= $plano['status'] === 'concluido' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                                    <i class="fa-solid <?= $plano['status'] === 'concluido' ? 'fa-check-circle' : 'fa-hourglass-half' ?>"></i>
                                                    <?= $plano['status'] === 'concluido' ? 'Concluído' : 'Em andamento' ?>
                                                </span>
                                            </div>
                                            <div class="plano-info">
                                                <span class="badge bg-secondary"><i class="fa-solid fa-layer-group"></i> <?= $capCount ?> capítulo(s)</span>
                                                <span class="badge bg-secondary"><i class="fa-solid fa-list-ul"></i> <?= $topCount ?> tópico(s)</span>
                                            </div>
                                        </div>
                                        <div class="plano-footer">
                                            <i class="fa-regular fa-calendar"></i>
                                            <?= date('d/m/Y H:i', strtotime($plano['criado_em'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; else: ?>
                                    <div class="text-muted w-100 text-center" style="padding: 2em 0;">Nenhum plano cadastrado recentemente.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Função para carregar turmas e paginação dinamicamente
function loadTurmasCoordenador(page = 1) {
    fetch('../controllers/turmas_paginacao_ajax.php?page=' + page)
        .then(resp => resp.text())
        .then(html => {
            document.getElementById('turmas-cards-container').innerHTML = html;
        });
    fetch('../controllers/turmas_pagination_nav_ajax.php?page=' + page)
        .then(resp => resp.text())
        .then(html => {
            document.getElementById('turmas-pagination-nav').innerHTML = html;
            bindTurmasPaginationCoordenador();
        });
}
function bindTurmasPaginationCoordenador() {
    document.querySelectorAll('#turmas-pagination-nav .pagination .page-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.getAttribute('href').replace('?page=', '');
            loadTurmasCoordenador(page);
        });
    });
}
document.addEventListener('DOMContentLoaded', function() {
    loadTurmasCoordenador(1);
});
</script>
<?php include 'footer.php'; ?>
</body>
</html>