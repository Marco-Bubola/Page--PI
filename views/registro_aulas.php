<?php
session_start();
if (!isset($_SESSION['usuario_nome']) || $_SESSION['usuario_tipo'] !== 'professor') {
    header('Location: index.php');
    exit();
}
require_once '../config/conexao.php';
include 'navbar.php';
include 'notificacao.php';

// Certifique-se de inicializar as variáveis antes de usá-las
$disciplinas = [];
$planos = [];
$capitulos = [];
$topicos = [];
$ultimas_aulas = [];
$topicos_aula = [];
$topicos_personalizados_aula = [];
    $turma_id = isset($_GET['turma_id']) ? intval($_GET['turma_id']) : null;
    $professor_id = $_SESSION['usuario_id'];

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

    // Buscar últimos registros de aula do professor nesta turma
    $ultimas_aulas = [];
    $stmt = $conn->prepare("
        SELECT a.id, a.data, a.comentario, d.nome AS disciplina_nome
        FROM aulas a
        JOIN disciplinas d ON a.disciplina_id = d.id
        WHERE a.turma_id = ? AND a.professor_id = ?
        ORDER BY a.data DESC, a.id DESC
        LIMIT 5
    ");
    $stmt->bind_param('ii', $turma_id, $professor_id);
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
    <title>Registro de Aulas - PI Page</title>
    <link rel="stylesheet" href="../assets/css/css_base_page.css">
    <link rel="icon" type="image/png" href="../assets/img/LOGO_PAGE.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background: #f5f5f5; }
        .main-title { font-size: 2.3rem; font-weight: 800; color: #112130; margin-bottom: 0.7rem; display: flex; align-items: center; gap: 10px; }
        .section-title { font-size: 1.7rem; font-weight: 700; color: #0d6efd; display: flex; align-items: center; gap: 8px; }
        .section-desc { color: #666; font-size: 1.18rem; margin-bottom: 1.7rem; }
        .card-disciplina { border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,0.10); border: none; }
        .card-header.bg-primary { border-radius: 18px 18px 0 0; font-size: 1.25rem; font-weight: 700; }
        .capitulo-title { font-weight: 700; color: #0d6efd; font-size: 1.15rem; }
        .topico-checkbox { margin-right: 8px; }
        .topico-label { font-weight: 600; }
        .modal-lg { max-width: 800px; }
        .icon-concluido { color: #198754; margin-right: 4px; }
        .icon-nao-concluido { color: #adb5bd; margin-right: 4px; }
        .custom-divider { border-top: 2px solid #e9ecef; margin: 2.5rem 0 2rem 0; }
        .list-group-item-personalizado { background: #f8f9fa; color: #0d6efd; }
        .card-historico { border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,0.10); border: none; }
        .badge.bg-primary { background: #0d6efd !important; font-size: 1.08em; padding: 7px 12px; border-radius: 8px; }
        .btn-outline-primary, .btn-outline-danger, .btn, .btn-sm { border-radius: 8px; font-size: 1.13rem; padding: 10px 18px; }
        .form-check-input:checked { background-color: #198754; border-color: #198754; }
        .topico-desc { font-size: 1.08em; color: #555; margin-left: 1.2em; margin-bottom: 0.2em; }
        .topico-personalizado-box { background: #f8f9fa; border-radius: 8px; padding: 8px 14px; margin-bottom: 8px; }
        .topico-personalizado-title { color: #0d6efd; font-weight: 700; font-size: 1.08em; }
        .topico-personalizado-desc { font-size: 1.08em; color: #444; margin-left: 1.2em; }
        .list-group-item { font-size: 1.13em; border-radius: 10px; margin-bottom: 8px; }
        @media (max-width: 767px) {
            .main-title { font-size: 1.4rem; }
            .section-title { font-size: 1.1rem; }
        }
    </style>
</head>
<body>
<div class="container-fluid  py-4" >
    <div class="mb-4 text-center">
        <h2 class="main-title"><i class="fa-solid fa-pen-to-square"></i> Registro de Aulas</h2>
        <div class="section-desc"><i class="fa-solid fa-circle-info me-1"></i>Selecione a disciplina e registre os tópicos ministrados, incluindo tópicos personalizados se necessário.</div>
    </div>
    <div class="row g-4">
        <?php foreach ($disciplinas as $disc): ?>
            <div class="col-12 col-md-4">
                <div class="card card-disciplina mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <span><i class="fa-solid fa-book-open me-2"></i> <?= htmlspecialchars($disc['nome']) ?></span>
                        <button class="btn btn-light btn-sm fw-bold" onclick="abrirModalAula(<?= $disc['id'] ?>, '<?= htmlspecialchars(addslashes($disc['nome'])) ?>')">
                            <i class="fa-solid fa-plus me-1"></i> Registrar Aula
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (isset($planos[$disc['id']])): $plano = $planos[$disc['id']]; ?>
                            <?php
                            $icon_plano = $plano['status'] === 'concluido'
                                ? '<i class="fa-solid fa-circle-check icon-concluido" title="Concluído"></i>'
                                : '<i class="fa-regular fa-circle icon-nao-concluido" title="Em andamento"></i>';
                            ?>
                            <div class="mb-2">
                                <?= $icon_plano ?><b><?= htmlspecialchars($plano['titulo']) ?></b>
                                <span class="badge <?= $plano['status'] === 'concluido' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                    <?= $plano['status'] === 'concluido' ? 'Concluído' : 'Em andamento' ?>
                                </span>
                            </div>
                            <?php if (!empty($capitulos[$plano['id']])): ?>
                                <?php foreach ($capitulos[$plano['id']] as $cap): ?>
                                    <?php
                                    $icon_cap = $cap['status'] === 'concluido'
                                        ? '<i class="fa-solid fa-circle-check icon-concluido" title="Concluído"></i>'
                                        : '<i class="fa-regular fa-circle icon-nao-concluido" title="Em andamento"></i>';
                                    ?>
                                    <div class="mb-2">
                                        <div class="capitulo-title"><?= $icon_cap ?><?= htmlspecialchars($cap['titulo']) ?></div>
                                        <ul class="mb-1">
                                            <?php foreach ($topicos[$cap['id']] ?? [] as $top): ?>
                                                <?php
                                                $icon_top = $top['status'] === 'concluido'
                                                    ? '<i class="fa-solid fa-circle-check icon-concluido" title="Concluído"></i>'
                                                    : '<i class="fa-regular fa-circle icon-nao-concluido" title="Em andamento"></i>';
                                                ?>
                                                <li>
                                                    <?= $icon_top ?><span class="fw-semibold"><?= htmlspecialchars($top['titulo']) ?></span>
                                                    <?php if (!empty($top['descricao'])): ?>
                                                        <div class="topico-desc"><?= nl2br(htmlspecialchars($top['descricao'])) ?></div>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-muted">Nenhum capítulo cadastrado.</div>
                            <?php endif; ?>
                            <?php if (!empty($topicosPersonalizadosPorPlano[$plano['id']])): ?>
                                <div class="mt-2">
                                    <b><i class="fa-solid fa-lightbulb"></i> Tópicos personalizados ministrados:</b>
                                    <?php foreach ($topicosPersonalizadosPorPlano[$plano['id']] as $desc): ?>
                                        <div class="topico-personalizado-box mb-1">
                                            <span class="topico-personalizado-title"><i class="fa-solid fa-circle-dot"></i> Tópico personalizado</span>
                                            <div class="topico-personalizado-desc"><?= nl2br(htmlspecialchars($desc)) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-muted">Nenhum plano cadastrado para esta disciplina.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="custom-divider"></div>

    <div class="mb-3 text-center">
        <span class="section-title"><i class="fa-solid fa-clock-rotate-left"></i> Últimos registros de aula nesta turma</span>
        <div class="section-desc"><i class="fa-solid fa-circle-info me-1"></i>Veja o histórico das últimas aulas registradas, incluindo tópicos planejados e personalizados.</div>
    </div>
    <div class="card card-historico shadow-sm mb-5">
        <div class="card-body">
            <?php if ($ultimas_aulas): ?>
                <ul class="list-group">
                    <?php foreach ($ultimas_aulas as $aula): ?>
                        <li class="list-group-item">
                            <div class="mb-1">
                                <b><i class="fa-solid fa-calendar-day me-1"></i> <?= date('d/m/Y', strtotime($aula['data'])) ?></b>
                                <span class="badge bg-primary ms-2"><i class="fa-solid fa-book me-1"></i><?= htmlspecialchars($aula['disciplina_nome']) ?></span>
                            </div>
                            <div>
                                <b><i class="fa-solid fa-list-check me-1"></i>Tópicos ministrados:</b>
                                <?php if (!empty($topicos_aula[$aula['id']])): ?>
                                    <?= implode(', ', array_map('htmlspecialchars', $topicos_aula[$aula['id']])) ?>
                                <?php else: ?>
                                    <span class="text-muted">Nenhum tópico registrado</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($topicos_personalizados_aula[$aula['id']])): ?>
                                <div>
                                    <b><i class="fa-solid fa-lightbulb"></i> Tópicos personalizados:</b>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($topicos_personalizados_aula[$aula['id']] as $desc): ?>
                                            <li class="list-group-item list-group-item-personalizado">
                                                <i class="fa-solid fa-lightbulb"></i> <?= htmlspecialchars($desc) ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($aula['comentario'])): ?>
                                <div class="text-muted mt-1"><b><i class="fa-solid fa-comment-dots me-1"></i>Comentário:</b> <?= nl2br(htmlspecialchars($aula['comentario'])) ?></div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="text-muted"><i class="fa-solid fa-circle-exclamation me-1"></i>Nenhum registro de aula encontrado.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de Registro de Aula -->
<div class="modal fade" id="modalAula" tabindex="-1" aria-labelledby="modalAulaLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="formAula" class="needs-validation" novalidate>
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="modalAulaLabel">Registrar Aula</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <span class="badge bg-primary">Disciplina</span>
            <span id="modalDisciplinaNome" class="fw-bold"></span>
          </div>
          <input type="hidden" name="disciplina_id" id="aula_disciplina_id">
          <input type="hidden" name="turma_id" value="<?= $turma_id ?>">
          <div class="mb-3">
            <label for="aula_data" class="form-label">Data da aula:</label>
            <input type="date" name="data" id="aula_data" class="form-control" required>
            <div class="invalid-feedback">Informe a data da aula.</div>
          </div>
          <div id="topicos_aula_box"></div>
          <div class="mb-3">
            <label class="form-label">Tópicos personalizados (não planejados):</label>
            <div id="topicos_personalizados_box"></div>
            <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="adicionarTopicoPersonalizado()">
                <i class="fa-solid fa-plus me-1"></i> Adicionar tópico personalizado
            </button>
          </div>
          <div class="mb-3">
            <label for="aula_comentario" class="form-label">Comentário:</label>
            <textarea name="comentario" id="aula_comentario" class="form-control" rows="2" placeholder="Observações sobre a aula"></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-1"></i>Cancelar</button>
          <button type="submit" class="btn btn-success"><i class="fa-solid fa-floppy-disk me-1"></i>Salvar Aula</button>
        </div>
      </div>
    </form>
  </div>
</div>
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
    // Montar lista de tópicos
    let html = '';
    const plano = planos[disc_id];
    if (plano && capitulos[plano.id]) {
        html += '<label class="form-label mb-1">Selecione os tópicos ministrados nesta aula:</label>';
        capitulos[plano.id].forEach(cap => {
            html += `<div class="mb-2"><div class="capitulo-title">${cap.titulo}</div><div class="row">`;
            (topicos[cap.id]||[]).forEach(top => {
                const checked = top.status === 'concluido' ? 'checked' : '';
                const disabled = top.status === 'concluido' ? 'disabled' : '';
                html += `<div class="col-12 col-md-6"><div class="form-check mb-1">
                    <input class="form-check-input topico-checkbox" type="checkbox" name="topicos[]" value="${top.id}" id="topico_${top.id}" ${checked} ${disabled}>
                    <label class="form-check-label topico-label" for="topico_${top.id}">${top.titulo}${top.status === 'concluido' ? ' <i class="bi bi-check-circle-fill icon-concluido" title="Concluído"></i>' : ''}</label>
                </div></div>`;
            });
            html += `</div></div>`;
        });
    } else {
        html = '<div class="text-muted">Nenhum capítulo/tópico disponível.</div>';
    }
    document.getElementById('topicos_aula_box').innerHTML = html;
    new bootstrap.Modal(document.getElementById('modalAula')).show();
}
// Validação Bootstrap
(function () {
  'use strict'
  var forms = document.querySelectorAll('.needs-validation')
  Array.prototype.slice.call(forms).forEach(function (form) {
    form.addEventListener('submit', function (event) {
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
    const resp = await fetch('../controllers/registrar_aula.php', { method: 'POST', body: formData });
    const data = await resp.json();
    if (data.success) {
        mostrarNotificacao('Aula registrada com sucesso!', 'success');
        setTimeout(() => location.reload(), 1200);
    } else {
        mostrarNotificacao(data.error || 'Erro ao registrar aula', 'danger');
    }
};
</script>
<?php include 'footer.php'; ?>
</body>
</html>
