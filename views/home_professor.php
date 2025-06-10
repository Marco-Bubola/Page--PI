<?php
session_start();
if (!isset($_SESSION['usuario_nome']) || $_SESSION['usuario_tipo'] !== 'professor') {
    header('Location: index.php');
    exit();
}

$nome = $_SESSION['usuario_nome'];
$usuario_id = $_SESSION['usuario_id']; // Assumindo que o ID do usuário está na sessão

include 'navbar.php';
include 'notificacao.php'; // Incluir notificacao.php para que as notificações funcionem
require_once '../config/conexao.php';

// --- BUSCA DE DADOS PARA O PROFESSOR ---

// 1. Buscar Turmas e Disciplinas vinculadas a planos de aula do professor
$turmas_professor = [];
$disciplinas_professor = [];
$disciplinas_nomes_professor = [];
$turma_disciplinas_professor = [];
$planos_turma_disciplina_professor = [];

$sql_professor_data = "
    SELECT
        t.id AS turma_id,
        t.nome AS turma_nome,
        t.ano_letivo,
        t.turno,
        d.id AS disciplina_id,
        d.nome AS disciplina_nome,
        p.id AS plano_id,
        p.titulo AS plano_titulo,
        p.status AS plano_status,
        p.criado_em AS plano_criado_em
    FROM
        planos p
    JOIN
        turmas t ON p.turma_id = t.id
    JOIN
        disciplinas d ON p.disciplina_id = d.id
    WHERE
        p.criado_por = ?  /* <--- CORRIGIDO AQUI: de p.usuario_id para p.criado_por */
    ORDER BY
        t.nome, d.nome, p.criado_em DESC
";
// Buscar turmas
$turmas = [];
$sql = 'SELECT * FROM turmas ORDER BY ano_letivo DESC, nome';
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $turmas[] = $row;
    }
}
$stmt = $conn->prepare($sql_professor_data);
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$result_professor_data = $stmt->get_result();

if ($result_professor_data && $result_professor_data->num_rows > 0) {
    while ($row = $result_professor_data->fetch_assoc()) {
        // Coleta de turmas únicas
        if (!isset($turmas_professor[$row['turma_id']])) {
            $turmas_professor[$row['turma_id']] = [
                'id' => $row['turma_id'],
                'nome' => $row['turma_nome'],
                'ano_letivo' => $row['ano_letivo'],
                'turno' => $row['turno']
            ];
        }

        // Coleta de disciplinas únicas
        if (!isset($disciplinas_professor[$row['disciplina_id']])) {
            $disciplinas_professor[$row['disciplina_id']] = [
                'id' => $row['disciplina_id'],
                'nome' => $row['disciplina_nome']
            ];
            $disciplinas_nomes_professor[$row['disciplina_id']] = $row['disciplina_nome'];
        }

        // Vincular disciplinas a turmas (apenas as que têm planos do professor)
        if (!in_array($row['disciplina_id'], $turma_disciplinas_professor[$row['turma_id']] ?? [])) {
            $turma_disciplinas_professor[$row['turma_id']][] = $row['disciplina_id'];
        }

        // Armazenar o último plano de aula para cada combinação turma-disciplina
        // (assume-se que o plano mais recente é o que queremos exibir na listagem de turmas/disciplinas)
        if (!isset($planos_turma_disciplina_professor[$row['turma_id']][$row['disciplina_id']])) {
            $planos_turma_disciplina_professor[$row['turma_id']][$row['disciplina_id']] = [
                'id' => $row['plano_id'],
                'titulo' => $row['plano_titulo'],
                'status' => $row['plano_status']
            ];
        }
    }
}
$stmt->close();

// 2. Buscar total de planos de aula criados pelo professor
$total_planos_professor = 0;
$res_planos = $conn->prepare('SELECT COUNT(*) AS total FROM planos WHERE criado_por = ?');
$res_planos->bind_param('i', $usuario_id);
$res_planos->execute();
$res_planos->bind_result($total_planos_professor);
$res_planos->fetch();
$res_planos->close();

// 3. Buscar últimos 5 planos de aula criados pelo professor
$ultimos_planos_professor = [];
$sql_ultimos_planos = "
    SELECT
        p.id, p.titulo, p.status, p.criado_em,
        d.nome AS disciplina_nome,
        t.nome AS turma_nome
    FROM
        planos p
    JOIN
        disciplinas d ON p.disciplina_id = d.id
    JOIN
        turmas t ON p.turma_id = t.id
    WHERE
        p.criado_por = ?  /* <--- CORRIGIDO AQUI */
    ORDER BY
        p.criado_em DESC
    LIMIT 5
";
$stmt = $conn->prepare($sql_ultimos_planos);
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$result_ultimos_planos = $stmt->get_result();

if ($result_ultimos_planos && $result_ultimos_planos->num_rows > 0) {
    while ($row = $result_ultimos_planos->fetch_assoc()) {
        $ultimos_planos_professor[] = $row;
    }
}
$stmt->close();

// --- FIM DA BUSCA DE DADOS ---
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Home Professor - PI Page</title>
    <link rel="icon" type="image/png" href="../assets/img/LOGO_PAGE.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; }
        .user { font-size: 18px; color: #007bff; margin-top: 10px; }
        .card-home { border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); }
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
    <div class="row">
        <div class="col-12">
            <div class="mb-4">
                <div class="row g-3">
                    <div class="col-6 col-md-4">
                        <div class="card text-center shadow-sm">
                            <div class="card-body">
                                <div class="fs-2 fw-bold text-primary"><?php echo count($turmas_professor); ?></div>
                                <div class="text-muted">Minhas Turmas</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card text-center shadow-sm">
                            <div class="card-body">
                                <div class="fs-2 fw-bold text-success"><?php echo count($disciplinas_professor); ?></div>
                                <div class="text-muted">Minhas Disciplinas</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card text-center shadow-sm">
                            <div class="card-body">
                                <div class="fs-2 fw-bold text-warning"><?php echo $total_planos_professor; ?></div>
                                <div class="text-muted">Meus Planos de Aula</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-home p-4 mb-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-3">
                    <div>
                        <h2 class="mb-1">Bem-vindo, Professor</h2>
                        <div class="user">Olá, <strong><?php echo htmlspecialchars($nome); ?></strong>!</div>
                    </div>
                    <a href="planos.php" class="btn btn-primary btn-nav ms-md-3 mt-3 mt-md-0">Gerenciar Meus Planos</a>
                </div>
                <hr class="my-3">

                <div class="row g-4">
                    <div class="col-12 col-md-6">
                        <div class="section-title">Minhas Turmas e Planos de Aula</div>
                        <div class="section-desc">Acompanhe as turmas em que você tem planos de aula e o status dos planos.</div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <a href="turmas.php" class="btn btn-outline-success btn-sm ms-2">Ver Todas as Turmas</a>
                        </div>
                        <?php if (!empty($turmas_professor)): ?>
                            <?php foreach ($turmas_professor as $turma): ?>
                                <div class="turma-card mb-3">
                                    <div class="turma-title mb-1">
                                        <?= htmlspecialchars($turma['nome']) ?> <span class="badge bg-secondary ms-2">Ano: <?= htmlspecialchars($turma['ano_letivo']) ?></span> <span class="badge bg-info text-dark ms-1">Turno: <?= htmlspecialchars($turma['turno']) ?></span>
                                    </div>
                                    <div class="mb-1"><b>Disciplinas:</b></div>
                                    <ul class="mb-2 ps-3">
                                        <?php if (!empty($turma_disciplinas_professor[$turma['id']])): ?>
                                            <?php foreach ($turma_disciplinas_professor[$turma['id']] as $disc_id): ?>
                                                <li>
                                                    <span class="disciplina-badge"><?= htmlspecialchars($disciplinas_nomes_professor[$disc_id] ?? '-') ?></span>
                                                    <?php if (isset($planos_turma_disciplina_professor[$turma['id']][$disc_id])):
                                                        $plano = $planos_turma_disciplina_professor[$turma['id']][$disc_id]; ?>
                                                        <span class="plano-badge badge <?= $plano['status'] === 'concluido' ? 'bg-success' : 'bg-warning text-dark' ?>">Plano: <?= htmlspecialchars($plano['titulo']) ?> (<?= $plano['status'] === 'concluido' ? 'Concluído' : 'Em andamento' ?>)</span>
                                                    <?php else: ?>
                                                        <span class="plano-badge badge bg-light text-dark">Sem plano</span>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li class="text-muted">Nenhuma disciplina com plano de aula seu nesta turma</li>
                                        <?php endif; ?>
                                    </ul>
                                    <a href="planos.php?turma_id=<?= $turma['id'] ?>" class="btn btn-outline-secondary btn-sm">Ver Planos da Turma</a>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">Nenhuma turma com planos de aula associados a você.</p>
                        <?php endif; ?>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-2 d-flex align-items-center justify-content-between">
                            <div class="section-title mb-0">Últimos Planos de Aula Criados por Você</div>
                            <a href="planos.php?usuario_id=<?= $usuario_id ?>" class="btn btn-outline-secondary btn-sm">Ver Todos os Meus Planos</a>
                        </div>
                        <div class="section-desc">Acompanhe os planos de aula mais recentes que você criou.</div>
                        <ul class="list-group mb-2">
                            <?php if ($ultimos_planos_professor): ?>
                                <?php foreach ($ultimos_planos_professor as $plano): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <b><?= htmlspecialchars($plano['titulo']) ?></b>
                                            <span class="badge bg-primary ms-2">Disciplina: <?= htmlspecialchars($plano['disciplina_nome']) ?></span>
                                            <span class="badge bg-info text-dark ms-2">Turma: <?= htmlspecialchars($plano['turma_nome']) ?></span>
                                            <span class="badge <?= $plano['status'] === 'concluido' ? 'bg-success' : 'bg-warning text-dark' ?> ms-2"><?= $plano['status'] === 'concluido' ? 'Concluído' : 'Em andamento' ?></span>
                                        </div>
                                        <span class="text-muted small"><?= date('d/m/Y H:i', strtotime($plano['criado_em'])) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-muted">Nenhum plano de aula criado recentemente por você.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'footer.php'; ?>
</body>
</html>