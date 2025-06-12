<?php
session_start();
if (!isset($_SESSION['usuario_nome']) || $_SESSION['usuario_tipo'] !== 'professor') {
    header('Location: index.php');
    exit();
}

$nome = $_SESSION['usuario_nome'];
$usuario_id = $_SESSION['usuario_id'];

include 'navbar.php';
include 'notificacao.php';
require_once '../config/conexao.php';

// --- BUSCA DE DADOS PARA O PROFESSOR ---

$turmas_professor = [];
$disciplinas_professor = [];
$disciplinas_nomes_professor = [];
$turma_disciplinas_professor = [];
$planos_turma_disciplina_professor = [];

// 1. Buscar apenas as turmas e disciplinas realmente vinculadas (usando turma_disciplinas)
$sql_turmas_disciplinas = "
    SELECT 
        t.id AS turma_id,
        t.nome AS turma_nome,
        t.ano_letivo,
        t.turno,
        d.id AS disciplina_id,
        d.nome AS disciplina_nome
    FROM 
        turmas t
    INNER JOIN 
        turma_disciplinas td ON td.turma_id = t.id
    INNER JOIN 
        disciplinas d ON td.disciplina_id = d.id
    ORDER BY 
        t.nome, d.nome
";
$stmt = $conn->prepare($sql_turmas_disciplinas);
// Não precisa de bind_param pois não há WHERE
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Turmas únicas
        if (!isset($turmas_professor[$row['turma_id']])) {
            $turmas_professor[$row['turma_id']] = [
                'id' => $row['turma_id'],
                'nome' => $row['turma_nome'],
                'ano_letivo' => $row['ano_letivo'],
                'turno' => $row['turno']
            ];
        }
        // Disciplinas únicas
        if (!isset($disciplinas_professor[$row['disciplina_id']])) {
            $disciplinas_professor[$row['disciplina_id']] = [
                'id' => $row['disciplina_id'],
                'nome' => $row['disciplina_nome']
            ];
            $disciplinas_nomes_professor[$row['disciplina_id']] = $row['disciplina_nome'];
        }
        // Vincular disciplinas a turmas
        if (!in_array($row['disciplina_id'], $turma_disciplinas_professor[$row['turma_id']] ?? [])) {
            $turma_disciplinas_professor[$row['turma_id']][] = $row['disciplina_id'];
        }
    }
}
$stmt->close();

// 2. Buscar planos de aula do professor para cada turma/disciplina
$sql_planos = "
    SELECT 
        p.id, p.titulo, p.status, p.turma_id, p.disciplina_id
    FROM 
        planos p
    ORDER BY 
        p.criado_em DESC
";
$stmt = $conn->prepare($sql_planos);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Armazena o plano mais recente para cada turma/disciplina
        if (!isset($planos_turma_disciplina_professor[$row['turma_id']][$row['disciplina_id']])) {
            $planos_turma_disciplina_professor[$row['turma_id']][$row['disciplina_id']] = [
                'id' => $row['id'],
                'titulo' => $row['titulo'],
                'status' => $row['status']
            ];
        }
    }
}
$stmt->close();

// 3. Buscar total de planos de aula criados pelo professor
$total_planos_professor = 0;
$res_planos = $conn->prepare('SELECT COUNT(*) AS total FROM planos WHERE criado_por = ?');
$res_planos->bind_param('i', $usuario_id);
$res_planos->execute();
$res_planos->bind_result($total_planos_professor);
$res_planos->fetch();
$res_planos->close();

// 4. Buscar últimos 5 planos de aula criados pelo professor
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
        p.criado_por = ?
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background: #f5f5f5; }
        .user { font-size: 1.5rem; color: #007bff; margin-top: 10px; }
        .card-home { border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,0.10); }
        .btn-nav { min-width: 220px; margin-bottom: 10px; font-size: 1.2rem; padding: 14px 0; }
        .turma-card { background: #fff; border-radius: 14px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 24px; padding: 24px 24px 16px 24px; }
        .turma-title { font-size: 1.35rem; font-weight: 700; color: #007bff; }
        .disciplina-badge { background: #e9ecef; color: #333; border-radius: 7px; padding: 4px 12px; margin-right: 10px; font-size: 1.1em; }
        .plano-badge { margin-left: 12px; font-size: 1.05em; }
        .section-title { font-size: 2rem; font-weight: 700; color: #222; margin-bottom: 1.2rem; display: flex; align-items: center; gap: 10px; }
        .section-desc { color: #666; font-size: 1.15rem; margin-bottom: 1.5rem; }
        .list-group-item { font-size: 1.15rem; padding: 1.1rem 1.2rem; border-radius: 10px; margin-bottom: 10px; }
        .badge { font-size: 1em; padding: 7px 12px; }
        .btn, .btn-sm { font-size: 1.1rem; padding: 10px 18px; border-radius: 8px; }
        .mb-4 { margin-bottom: 2.5rem!important; }
        .mb-3 { margin-bottom: 1.7rem!important; }
        .mb-2 { margin-bottom: 1.2rem!important; }
        .gap-2 { gap: 1.1rem!important; }
        .shadow-sm { box-shadow: 0 2px 10px rgba(0,0,0,0.07)!important; }
        .fs-2 { font-size: 2.7rem!important; }
        .fw-bold { font-weight: 800!important; }
        .text-muted { font-size: 1.1rem; }
        @media (max-width: 768px) {
            .section-title { font-size: 1.3rem; }
            .btn-nav { font-size: 1rem; min-width: 100%; }
            .turma-title { font-size: 1.1rem; }
        }
        /* --- Adicionado do turmas.php --- */
        .card-turma {
            border-radius: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            background: #fff;
            position: relative;
            margin-bottom: 24px;
        }
        .card-turma.cancelada {
            opacity: 0.7;
            filter: grayscale(0.2);
        }
        .card-turma.concluida {
            border: 2px solid #198754;
        }
        .status-overlay {
            position: absolute;
            top: 12px;
            left: 12px;
            background: #e9ecef;
            color: #333;
            border-radius: 8px;
            padding: 4px 14px;
            font-size: 1.05em;
            font-weight: 600;
            z-index: 2;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }
        .turma-status-badge {
            font-size: 1em;
            padding: 7px 12px;
        }
        .disciplina-status {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 7px 10px;
            margin-bottom: 6px;
        }
        /* Fim do CSS de turmas.php */
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
                                <div class="fs-2 fw-bold text-primary"><i class="fa-solid fa-users me-2"></i><?php echo count($turmas_professor); ?></div>
                                <div class="text-muted">Minhas Turmas</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card text-center shadow-sm">
                            <div class="card-body">
                                <div class="fs-2 fw-bold text-success"><i class="fa-solid fa-book-open me-2"></i><?php echo count($disciplinas_professor); ?></div>
                                <div class="text-muted">Minhas Disciplinas</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card text-center shadow-sm">
                            <div class="card-body">
                                <div class="fs-2 fw-bold text-warning"><i class="fa-solid fa-clipboard-list me-2"></i><?php echo $total_planos_professor; ?></div>
                                <div class="text-muted">Meus Planos de Aula</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-home p-4 mb-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
                    <div>
                        <h2 class="mb-2 section-title"><i class="fa-solid fa-chalkboard-user"></i>Bem-vindo, Professor</h2>
                        <div class="user">Olá, <strong><?php echo htmlspecialchars($nome); ?></strong>!</div>
                    </div>
                    <div class="d-flex flex-column flex-md-row align-items-center">
                        <a href="planos.php" class="btn btn-primary btn-nav ms-md-3 mt-3 mt-md-0"><i class="fa-solid fa-clipboard-list me-2"></i>Gerenciar Meus Planos</a>
                    </div>
                </div>
                <hr class="my-4">

                <div class="row g-4">
                    <div class="col-12 col-md-6">
                        <div class="section-title"><i class="fa-solid fa-users-viewfinder"></i>Minhas Turmas e Planos de Aula</div>
                        <div class="section-desc">Acompanhe as turmas em que você tem planos de aula e o status dos planos.</div>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <a href="turmas.php" class="btn btn-outline-success btn-sm ms-2"><i class="fa-solid fa-users-viewfinder me-1"></i>Ver Todas as Turmas</a>
                        </div>
                        <?php if (!empty($turmas_professor)): ?>
                            <?php foreach ($turmas_professor as $turma): ?>
                                <div class="turma-card mb-3">
                                    <div class="turma-title mb-2">
                                        <i class="fa-solid fa-users me-2"></i><?= htmlspecialchars($turma['nome']) ?> <span class="badge bg-secondary ms-2"><i class="fa-solid fa-calendar-alt me-1"></i>Ano: <?= htmlspecialchars($turma['ano_letivo']) ?></span> <span class="badge bg-info text-dark ms-1"><i class="fa-solid fa-clock me-1"></i>Turno: <?= htmlspecialchars($turma['turno']) ?></span>
                                    </div>
                                    <div class="mb-2"><b><i class="fa-solid fa-book me-1"></i>Disciplinas:</b></div>
                                    <ul class="mb-2 ps-3">
                                        <?php if (!empty($turma_disciplinas_professor[$turma['id']])): ?>
                                            <?php foreach ($turma_disciplinas_professor[$turma['id']] as $disc_id): ?>
                                                <li>
                                                    <span class="disciplina-badge"><i class="fa-solid fa-book-open me-1"></i><?= htmlspecialchars($disciplinas_nomes_professor[$disc_id] ?? '-') ?></span>
                                                    <?php if (isset($planos_turma_disciplina_professor[$turma['id']][$disc_id])):
                                                        $plano = $planos_turma_disciplina_professor[$turma['id']][$disc_id]; ?>
                                                        <span class="plano-badge badge <?= $plano['status'] === 'concluido' ? 'bg-success' : 'bg-warning text-dark' ?>"><i class="fa-solid fa-clipboard-list me-1"></i>Plano: <?= htmlspecialchars($plano['titulo']) ?> (<?= $plano['status'] === 'concluido' ? 'Concluído' : 'Em andamento' ?>)</span>
                                                    <?php else: ?>
                                                        <span class="plano-badge badge bg-light text-dark"><i class="fa-solid fa-ban me-1"></i>Sem plano</span>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li class="text-muted"><i class="fa-solid fa-circle-exclamation me-1"></i>Nenhuma disciplina com plano de aula seu nesta turma</li>
                                        <?php endif; ?>
                                    </ul>
                                    <div class="d-flex gap-2 mt-2">
                                        <a href="planos.php?turma_id=<?= $turma['id'] ?>" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-clipboard-list me-1"></i>Ver Planos da Turma</a>
                                        <a href="registro_aulas.php?turma_id=<?= $turma['id'] ?>" class="btn btn-success btn-sm"><i class="fa-solid fa-chalkboard me-1"></i>Registrar Aulas</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted"><i class="fa-solid fa-circle-exclamation me-1"></i>Nenhuma turma com planos de aula associados a você.</p>
                        <?php endif; ?>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3 d-flex align-items-center justify-content-between">
                            <div class="section-title mb-0"><i class="fa-solid fa-chalkboard"></i>Histórico de Aulas Ministradas</div>
                            <a href="registro_aulas.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-chalkboard-user me-1"></i>Ver Todas as Aulas</a>
                        </div>
                        <div class="section-desc">Veja as últimas aulas que você registrou no sistema.</div>
                        <ul class="list-group mb-2">
                            <?php
                            // Buscar últimos 5 registros de aulas do professor
                            $historico_aulas = [];
                            $topicos_aula = [];
                            $topicos_personalizados_aula = [];
                            $stmt = $conn->prepare("
                                SELECT a.id, a.data, d.nome AS disciplina_nome, t.nome AS turma_nome, a.comentario
                                FROM aulas a
                                JOIN disciplinas d ON a.disciplina_id = d.id
                                JOIN turmas t ON a.turma_id = t.id
                                WHERE a.professor_id = ?
                                ORDER BY a.data DESC, a.id DESC
                                LIMIT 5
                            ");
                            $stmt->bind_param('i', $usuario_id);
                            $stmt->execute();
                            $res = $stmt->get_result();
                            $aula_ids = [];
                            while ($row = $res->fetch_assoc()) {
                                $historico_aulas[] = $row;
                                $aula_ids[] = $row['id'];
                            }
                            $stmt->close();
                            // Buscar tópicos ministrados e personalizados para essas aulas
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
                            ?>
                            <?php if ($historico_aulas): ?>
                                <?php foreach ($historico_aulas as $aula): ?>
                                    <li class="list-group-item">
                                        <div>
                                            <b><i class="fa-solid fa-calendar-day me-1"></i><?= date('d/m/Y', strtotime($aula['data'])) ?></b>
                                            <span class="badge bg-primary ms-2"><i class="fa-solid fa-book me-1"></i>Disciplina: <?= htmlspecialchars($aula['disciplina_nome']) ?></span>
                                            <span class="badge bg-info text-dark ms-2"><i class="fa-solid fa-users me-1"></i>Turma: <?= htmlspecialchars($aula['turma_nome']) ?></span>
                                        </div>
                                        <?php if (!empty($topicos_aula[$aula['id']])): ?>
                                            <div>
                                                <b><i class="fa-solid fa-list-check me-1"></i>Tópicos ministrados:</b>
                                                <?= implode(', ', array_map('htmlspecialchars', $topicos_aula[$aula['id']])) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($topicos_personalizados_aula[$aula['id']])): ?>
                                            <div>
                                                <b><i class="fa-solid fa-pen-nib me-1"></i>Tópicos personalizados:</b>
                                                <?= implode(', ', array_map('htmlspecialchars', $topicos_personalizados_aula[$aula['id']])) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($aula['comentario'])): ?>
                                            <div class="text-muted mt-1"><b><i class="fa-solid fa-comment-dots me-1"></i>Comentário:</b> <?= nl2br(htmlspecialchars($aula['comentario'])) ?></div>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-muted"><i class="fa-solid fa-circle-exclamation me-1"></i>Nenhuma aula registrada recentemente por você.</li>
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
