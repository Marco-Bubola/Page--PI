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
// Buscar últimos 5 planos
$ultimos_planos = [];
$res = $conn->query('SELECT p.id, p.titulo, p.status, p.criado_em, d.nome AS disciplina_nome, t.nome AS turma_nome FROM planos p JOIN disciplinas d ON p.disciplina_id = d.id JOIN turmas t ON p.turma_id = t.id ORDER BY p.criado_em DESC LIMIT 5');
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) $ultimos_planos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Home Coordenador - PI Page</title>
    <link rel="icon" type="image/png" href="../assets/img/LOGO_PAGE.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; }
        .user { font-size: 18px; color: #007bff; margin-top: 10px; }
        .card-home { border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); }
        .carousel-item .disc-card { background:#f1f1f1; padding:18px 12px; border-radius:7px; min-width:80px; max-width:100px; text-align:center; box-shadow:0 1px 4px rgba(0,0,0,0.07); font-weight:500; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .btn-nav { min-width: 180px; margin-bottom: 10px; }
        .turma-card { background: #f8f9fa; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); margin-bottom: 18px; padding: 18px 18px 10px 18px; }
        .turma-title { font-size: 1.1rem; font-weight: 600; color: #007bff; }
        .disciplina-badge { background: #e9ecef; color: #333; border-radius: 5px; padding: 2px 8px; margin-right: 6px; font-size: 0.95em; }
        .plano-badge { margin-left: 8px; }
        .section-title { font-size: 1.3rem; font-weight: 600; color: #222; margin-bottom: 0.7rem; }
        .section-desc { color: #666; font-size: 1.01rem; margin-bottom: 1.2rem; }
    </style>
</head>
<body>
<div class="container-fluid py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">
            <div class="mb-4">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="card text-center shadow-sm">
                            <div class="card-body">
                                <div class="fs-2 fw-bold text-primary"><?php echo count($turmas); ?></div>
                                <div class="text-muted">Turmas</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card text-center shadow-sm">
                            <div class="card-body">
                                <div class="fs-2 fw-bold text-success"><?php echo count($disciplinas); ?></div>
                                <div class="text-muted">Disciplinas</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card text-center shadow-sm">
                            <div class="card-body">
                                <div class="fs-2 fw-bold text-warning"><?php echo $total_planos; ?></div>
                                <div class="text-muted">Planos de Aula</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card text-center shadow-sm">
                            <div class="card-body">
                                <div class="fs-2 fw-bold text-info"><?php echo $total_usuarios; ?></div>
                                <div class="text-muted">Usuários</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card card-home p-4 mb-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-3">
                    <div>
                        <h2 class="mb-1">Bem-vindo, Coordenador</h2>
        <div class="user">Olá, <strong><?php echo htmlspecialchars($nome); ?></strong>!</div>
                    </div>
        <?php if (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'admin'): ?>
                        <a href="gerenciar_usuarios.php" class="btn btn-primary btn-nav ms-md-3 mt-3 mt-md-0">Gerenciar Usuários</a>
                    <?php endif; ?>
                </div>
                <hr class="my-3">
                <div class="row g-4">
                    <div class="col-12 col-md-6">
                        <div class="section-title">Disciplinas em destaque</div>
                        <div class="section-desc">Veja as principais disciplinas cadastradas no sistema. Acesse a página de disciplinas para gerenciar todas.</div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <a href="disciplinas.php" class="btn btn-outline-primary btn-sm me-2">Ver Disciplinas</a>
                        </div>
                        <div id="carouselDisciplinas" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                <?php
                                $total = count($disciplinas);
                                $por_slide = 4;
                                $num_slides = ceil($total / $por_slide);
                                for ($i = 0; $i < $num_slides; $i++): ?>
                                    <div class="carousel-item<?= $i === 0 ? ' active' : '' ?>">
                                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                                            <?php for ($j = $i * $por_slide; $j < min(($i+1)*$por_slide, $total); $j++): ?>
                                                <div class="disc-card">
                                                    <?= htmlspecialchars($disciplinas[$j]['nome']) ?>
                                                </div>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            <?php if ($num_slides > 1): ?>
                            <button class="carousel-control-prev" type="button" data-bs-target="#carouselDisciplinas" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Anterior</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#carouselDisciplinas" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Próximo</span>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="section-title">Turmas, Disciplinas e Planos</div>
                        <div class="section-desc">Veja as turmas cadastradas, suas disciplinas vinculadas e o status dos planos de aula.</div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <a href="turmas.php" class="btn btn-outline-success btn-sm ms-2">Ver Turmas</a>
                        </div>
                        <?php foreach ($turmas as $turma): ?>
                            <div class="turma-card mb-3">
                                <div class="turma-title mb-1">
                                    <?= htmlspecialchars($turma['nome']) ?> <span class="badge bg-secondary ms-2">Ano: <?= htmlspecialchars($turma['ano_letivo']) ?></span> <span class="badge bg-info text-dark ms-1">Turno: <?= htmlspecialchars($turma['turno']) ?></span>
                                </div>
                                <div class="mb-1"><b>Disciplinas:</b></div>
                                <ul class="mb-2 ps-3">
                                    <?php if (!empty($turmaDisciplinas[$turma['id']])): foreach ($turmaDisciplinas[$turma['id']] as $disc_id): ?>
                                        <li>
                                            <span class="disciplina-badge"><?= htmlspecialchars($disciplinasNomes[$disc_id] ?? '-') ?></span>
                                            <?php if (isset($planosTurmaDisc[$turma['id']][$disc_id])): $plano = $planosTurmaDisc[$turma['id']][$disc_id]; ?>
                                                <span class="plano-badge badge <?= $plano['status'] === 'concluido' ? 'bg-success' : 'bg-warning text-dark' ?>">Plano: <?= htmlspecialchars($plano['titulo']) ?> (<?= $plano['status'] === 'concluido' ? 'Concluído' : 'Em andamento' ?>)</span>
                                            <?php else: ?>
                                                <span class="plano-badge badge bg-light text-dark">Sem plano</span>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; else: ?>
                                        <li class="text-muted">Nenhuma disciplina vinculada</li>
                                    <?php endif; ?>
                                </ul>
                                <a href="planos.php?turma_id=<?= $turma['id'] ?>" class="btn btn-outline-secondary btn-sm">Gerenciar Planos</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <hr class="my-4">
                <div class="mb-2 d-flex align-items-center justify-content-between">
                    <div class="section-title mb-0">Últimos Planos de Aula</div>
                    <a href="planos.php" class="btn btn-outline-secondary btn-sm">Ver Todos</a>
                </div>
                <div class="section-desc">Acompanhe os planos de aula mais recentes criados no sistema.</div>
                <ul class="list-group mb-2">
                    <?php if ($ultimos_planos): foreach ($ultimos_planos as $plano): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <b><?= htmlspecialchars($plano['titulo']) ?></b>
                                <span class="badge bg-primary ms-2">Disciplina: <?= htmlspecialchars($plano['disciplina_nome']) ?></span>
                                <span class="badge bg-info text-dark ms-2">Turma: <?= htmlspecialchars($plano['turma_nome']) ?></span>
                                <span class="badge <?= $plano['status'] === 'concluido' ? 'bg-success' : 'bg-warning text-dark' ?> ms-2"><?= $plano['status'] === 'concluido' ? 'Concluído' : 'Em andamento' ?></span>
                            </div>
                            <span class="text-muted small"><?= date('d/m/Y H:i', strtotime($plano['criado_em'])) ?></span>
                        </li>
                    <?php endforeach; else: ?>
                        <li class="list-group-item text-muted">Nenhum plano cadastrado recentemente.</li>
        <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
// Exibir notificação se houver parâmetro na URL
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('sucesso')) {
    let msg = '';
    if (urlParams.get('sucesso') === 'turma_criada') msg = 'Turma criada com sucesso!';
    if (urlParams.get('sucesso') === 'disciplina_criada') msg = 'Disciplina criada com sucesso!';
    if (msg) mostrarNotificacao(msg, 'success');
}
if (urlParams.has('erro')) {
    let msg = '';
    if (urlParams.get('erro') === 'disciplina_existente') msg = 'Disciplina já existe!';
    if (urlParams.get('erro') === 'erro_banco') msg = 'Erro ao salvar no banco!';
    if (urlParams.get('erro') === 'dados_invalidos') msg = 'Dados inválidos!';
    if (msg) mostrarNotificacao(msg, 'danger');
}
</script>
<?php include 'footer.php'; ?>
</body>
</html> 