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

// Buscar capítulos de todos os planos exibidos
$capitulosPorPlano = [];
if (!empty($planos)) {
    $ids = array();
    if ($turma_id) {
        foreach ($planos as $plano) $ids[] = $plano['id'];
    } else {
        foreach ($planos as $plano) $ids[] = $plano['id'];
    }
    if ($ids) {
        $in = implode(',', array_map('intval', $ids));
        $sql = "SELECT * FROM capitulos WHERE plano_id IN ($in) ORDER BY plano_id, ordem ASC, id ASC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $capitulosPorPlano[$row['plano_id']][] = $row;
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
    <style>
        body { background: #f5f5f5; }
       
        .card-plano { border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); min-height: 340px; }
        .card-title { font-size: 1.25rem; font-weight: 600; }
        .card-desc { color: #555; font-size: 1.05rem; margin-bottom: 10px; }
        .badge-status { font-size: 1em; }
        .list-group-item { font-size: 0.98em; }
        .plano-meta { font-size: 0.95em; color: #888; }
        .plano-label { font-size: 0.98em; color: #666; }
    </style>
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
            <div class="bg-white rounded shadow-sm p-4 mb-3 d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-2">Planos de Aula<?= $turma_id ? ' da Turma: <span class=\"text-primary\">' . htmlspecialchars($turma_nome) . '</span>' : '' ?></h2>
                    <p class="mb-1 plano-label">Aqui você encontra todos os planos de aula cadastrados<?= $turma_id ? ' para esta turma' : '' ?>. Cada card mostra a disciplina, o título, a descrição, status, data de criação e os capítulos do plano. Clique em "Gerenciar capítulos/tópicos" para ver detalhes ou editar cada plano.</p>
                </div>
                <?php if (!$turma_id): ?>
                <button class="btn btn-success" onclick="abrirModalPlano()">Criar Plano</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="row g-4">
                <?php if ($turma_id): ?>
                    <?php foreach ($disciplinas as $disc): ?>
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="card card-plano h-100 shadow-sm border-0">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="text-primary mb-0"><i class="bi bi-journal-bookmark"></i> <?= htmlspecialchars($disc['nome']) ?></h6>
                                        <?php if (isset($planos[$disc['id']])): $plano = $planos[$disc['id']]; ?>
                                            <span class="badge badge-status <?= $plano['status'] === 'concluido' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                                <?= $plano['status'] === 'concluido' ? 'Concluído' : 'Em andamento' ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (isset($planos[$disc['id']])): $plano = $planos[$disc['id']]; ?>
                                        <h5 class="card-title mb-1">Plano: <?= htmlspecialchars($plano['titulo']) ?></h5>
                                        <div class="mb-1 text-muted" style="font-size:0.95em;">
                                            <i class="bi bi-calendar"></i> <b>Criado em:</b> <?= isset($plano['criado_em']) ? date('d/m/Y H:i', strtotime($plano['criado_em'])) : '-' ?>
                                        </div>
                                        <div class="mb-2" style="font-size:0.97em;">
                                            <i class="bi bi-info-circle"></i>
                                            <b>Descrição:</b>
                                            <?= strlen($plano['descricao']) > 80 ? nl2br(htmlspecialchars(substr($plano['descricao'],0,80))) . '... <span class=\"text-primary ver-mais\" style=\"cursor:pointer;\" data-desc=\"' . htmlspecialchars($plano['descricao']) . '\">ver mais</span>' : nl2br(htmlspecialchars($plano['descricao'])) ?>
                                        </div>
                                        <div class="mb-2"><b>Capítulos:</b></div>
                                        <ul class="list-group mb-2">
                                            <?php if (!empty($capitulosPorPlano[$plano['id']])): foreach ($capitulosPorPlano[$plano['id']] as $cap): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center py-1 px-2">
                                                    <span><?= htmlspecialchars($cap['titulo']) ?></span>
                                                    <span class="badge <?= $cap['status'] === 'concluido' ? 'bg-success' : 'bg-secondary' ?> ms-2"> <?= $cap['status'] ?> </span>
                                                </li>
                                            <?php endforeach; else: ?>
                                                <li class="list-group-item text-muted py-1 px-2">Nenhum capítulo cadastrado</li>
                                            <?php endif; ?>
                                        </ul>
                                        <div class="d-flex gap-2 mt-auto justify-content-center">
                                            <button class="btn btn-primary btn-sm" title="Editar" onclick="abrirModalEditarPlano(
                                                <?= $plano['id'] ?>,
                                                '<?= htmlspecialchars(addslashes($plano['titulo'])) ?>',
                                                '<?= isset($plano['disciplina_id']) ? $plano['disciplina_id'] : '' ?>',
                                                '<?= htmlspecialchars(addslashes($plano['descricao'])) ?>',
                                                '<?= $plano['status'] ?>',
                                                <?= $turma_id !== null ? $turma_id : "''" ?>,
                                                '<?= isset($plano['data_inicio']) ? date('Y-m-d', strtotime($plano['data_inicio'])) : '' ?>',
                                                '<?= isset($plano['data_fim']) ? date('Y-m-d', strtotime($plano['data_fim'])) : '' ?>',
                                                '<?= htmlspecialchars(addslashes($plano['objetivo_geral'])) ?>'
                                            )"><i class="bi bi-pencil"></i> Editar</button>
                                            <button class="btn btn-danger btn-sm" title="Excluir" onclick="abrirModalExcluirPlano(<?= $plano['id'] ?>, '<?= htmlspecialchars(addslashes($plano['titulo'])) ?>')"><i class="bi bi-trash"></i> Excluir</button>
                                            <a href="plano_detalhe.php?id=<?= $plano['id'] ?>" class="btn btn-secondary btn-sm" title="Gerenciar capítulos/tópicos"><i class="bi bi-list-task"></i> Gerenciar</a>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-muted mb-2">Nenhum plano cadastrado para esta disciplina.</div>
                                        <button class="btn btn-success btn-sm w-100" onclick="abrirModalPlanoDisciplina(<?= $disc['id'] ?>, '<?= htmlspecialchars(addslashes($disc['nome'])) ?>', <?= $turma_id ?>)"><i class="bi bi-plus-circle"></i> Criar Plano</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
            
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<!-- Modal de Criar Plano -->
<div class="modal fade" id="modalPlano" tabindex="-1" aria-labelledby="tituloModalPlano" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title" id="tituloModalPlano">Criar Plano de Aula</h3>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <form id="formPlano" action="../controllers/criar_plano.php" method="POST">
        <div class="modal-body">
          <?php if ($turma_id): ?>
            <input type="hidden" name="turma_id" id="turma_id_plano" value="<?= $turma_id ?>">
            <div class="row g-3">
              <div class="col-md-6">
                <label>Disciplina:</label>
                <input type="text" id="disciplina_nome_plano" class="form-control" value="" readonly>
                <input type="hidden" name="disciplina_id" id="disciplina_id_plano" value="">
              </div>
              <div class="col-md-6"></div>
            </div>
          <?php else: ?>
            <div class="row g-3">
              <div class="col-md-6">
                <label>Disciplina:</label>
                <select name="disciplina_id" class="form-select" required>
                  <option value="">Selecione</option>
                  <?php foreach ($disciplinas as $disc): ?>
                    <option value="<?= $disc['id'] ?>"><?= htmlspecialchars($disc['nome']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6"></div>
            </div>
          <?php endif; ?>
          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <label for="titulo_plano" class="form-label">Título do plano</label>
              <input type="text" name="titulo" id="titulo_plano" placeholder="Título do plano" required class="form-control">
            </div>
            <div class="col-md-6">
              <label for="status_plano" class="form-label">Status</label>
              <select name="status" id="status_plano" class="form-select">
                <option value="em_andamento">Em andamento</option>
                <option value="concluido">Concluído</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="data_inicio_plano" class="form-label">Data início</label>
              <input type="date" name="data_inicio" id="data_inicio_plano" class="form-control">
            </div>
            <div class="col-md-6">
              <label for="data_fim_plano" class="form-label">Data fim</label>
              <input type="date" name="data_fim" id="data_fim_plano" class="form-control">
            </div>
            <div class="col-md-6">
              <label for="objetivo_geral_plano" class="form-label">Objetivo Geral</label>
              <textarea name="objetivo_geral" id="objetivo_geral_plano" placeholder="Objetivo Geral" class="form-control" rows="2"></textarea>
            </div>
            <div class="col-md-6">
              <label for="descricao_plano" class="form-label">Descrição</label>
              <textarea name="descricao" id="descricao_plano" placeholder="Descrição" class="form-control" rows="2"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
        <input type="hidden" name="redirect" value="planos.php<?= $turma_id ? '?turma_id=' . $turma_id : '' ?>">
      </form>
    </div>
  </div>
</div>
<!-- Modal de Editar Plano -->
<div class="modal fade" id="modalEditarPlano" tabindex="-1" aria-labelledby="editarTituloModalPlano" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title" id="editarTituloModalPlano">Editar Plano de Aula</h3>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <form id="formEditarPlano" action="../controllers/editar_plano.php" method="POST">
        <div class="modal-body">
          <input type="hidden" name="id_plano" id="editar_id_plano">
          <?php if ($turma_id): ?>
            <input type="hidden" name="turma_id" id="editar_turma_id" value="<?= $turma_id ?>">
            <div class="row g-3">
              <div class="col-md-6">
                <label>Disciplina:</label>
                <select name="disciplina_id" id="editar_disciplina_id" class="form-select" required>
                  <option value="">Selecione</option>
                  <?php foreach ($disciplinas as $disc): ?>
                    <option value="<?= $disc['id'] ?>"><?= htmlspecialchars($disc['nome']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6"></div>
            </div>
          <?php else: ?>
            <div class="row g-3">
              <div class="col-md-6">
                <label>Disciplina:</label>
                <select name="disciplina_id" id="editar_disciplina_id" class="form-select" required>
                  <option value="">Selecione</option>
                  <?php foreach ($disciplinas as $disc): ?>
                    <option value="<?= $disc['id'] ?>"><?= htmlspecialchars($disc['nome']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6"></div>
            </div>
          <?php endif; ?>
          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <label for="editar_titulo" class="form-label">Título do plano</label>
              <input type="text" name="titulo" id="editar_titulo" placeholder="Título do plano" required class="form-control">
            </div>
            <div class="col-md-6">
              <label for="editar_status" class="form-label">Status</label>
              <select name="status" id="editar_status" class="form-select">
                <option value="em_andamento">Em andamento</option>
                <option value="concluido">Concluído</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="editar_data_inicio" class="form-label">Data início</label>
              <input type="date" name="data_inicio" id="editar_data_inicio" class="form-control">
            </div>
            <div class="col-md-6">
              <label for="editar_data_fim" class="form-label">Data fim</label>
              <input type="date" name="data_fim" id="editar_data_fim" class="form-control">
            </div>
            <div class="col-md-6">
              <label for="editar_objetivo_geral" class="form-label">Objetivo Geral</label>
              <textarea name="objetivo_geral" id="editar_objetivo_geral" placeholder="Objetivo Geral" class="form-control" rows="2"></textarea>
            </div>
            <div class="col-md-6">
              <label for="editar_descricao" class="form-label">Descrição</label>
              <textarea name="descricao" id="editar_descricao" placeholder="Descrição" class="form-control" rows="2"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        </div>
        <input type="hidden" name="redirect" value="planos.php<?= $turma_id ? '?turma_id=' . $turma_id : '' ?>">
      </form>
    </div>
  </div>
</div>
<!-- Modal de Confirmar Exclusão -->
<div class="modal fade" id="modalExcluirPlano" tabindex="-1" aria-labelledby="excluirPlanoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title" id="excluirPlanoLabel">Excluir Plano de Aula</h3>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <form id="formExcluirPlano" action="../controllers/excluir_plano.php" method="POST">
        <div class="modal-body">
          <input type="hidden" name="id_plano" id="excluir_id_plano">
          <p id="excluir_nome_plano" style="margin:15px 0;"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-danger">Confirmar Exclusão</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
function abrirModalPlano() {
  // Limpar o formulário
  document.getElementById('formPlano').reset();
  if (document.getElementById('disciplina_id_plano')) document.getElementById('disciplina_id_plano').value = '';
  if (document.getElementById('disciplina_nome_plano')) document.getElementById('disciplina_nome_plano').value = '';
  if (document.getElementById('titulo_plano')) document.getElementById('titulo_plano').value = '';
  if (document.getElementById('descricao_plano')) document.getElementById('descricao_plano').value = '';
  if (document.getElementById('objetivo_geral_plano')) document.getElementById('objetivo_geral_plano').value = '';
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
function abrirModalEditarPlano(id, titulo, disciplina_id, descricao, status, turma_id, data_inicio, data_fim, objetivo_geral) {
  document.getElementById('editar_id_plano').value = id;
  document.getElementById('editar_titulo').value = titulo;
  if (document.getElementById('editar_disciplina_id')) {
    document.getElementById('editar_disciplina_id').value = disciplina_id || '';
  }
  document.getElementById('editar_descricao').value = descricao || '';
  document.getElementById('editar_status').value = status || 'em_andamento';
  if (typeof turma_id !== 'undefined' && document.getElementById('editar_turma_id')) {
    document.getElementById('editar_turma_id').value = turma_id;
  }
  document.getElementById('editar_data_inicio').value = data_inicio || '';
  document.getElementById('editar_data_fim').value = data_fim || '';
  document.getElementById('editar_objetivo_geral').value = objetivo_geral || '';
  const modal = new bootstrap.Modal(document.getElementById('modalEditarPlano'));
  modal.show();
}
function fecharModalEditarPlano() {
  const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarPlano'));
  if (modal) modal.hide();
}
function abrirModalExcluirPlano(id, titulo) {
  document.getElementById('excluir_id_plano').value = id;
  document.getElementById('excluir_nome_plano').innerHTML = 'Tem certeza que deseja excluir o plano <b>' + titulo + '</b>?';
  const modal = new bootstrap.Modal(document.getElementById('modalExcluirPlano'));
  modal.show();
}
function fecharModalExcluirPlano() {
  const modal = bootstrap.Modal.getInstance(document.getElementById('modalExcluirPlano'));
  if (modal) modal.hide();
}
window.onclick = function(event) {
  // Não é mais necessário fechar modais manualmente, Bootstrap faz isso
}
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#disciplinas_turma').select2({
        width: '100%',
        placeholder: 'Selecione as disciplinas',
        language: 'pt-BR'
    });
});
</script>
<script>
function abrirModalPlanoDisciplina(disc_id, disc_nome, turma_id) {
  document.getElementById('formPlano').reset();
  document.getElementById('formPlano').action = '../controllers/criar_plano.php';
  document.getElementById('tituloModalPlano').innerText = 'Criar Plano de Aula';
  document.getElementById('disciplina_id_plano').value = disc_id;
  document.getElementById('disciplina_nome_plano').value = disc_nome;
  if (document.getElementById('titulo_plano')) document.getElementById('titulo_plano').value = '';
  if (document.getElementById('descricao_plano')) document.getElementById('descricao_plano').value = '';
  if (document.getElementById('objetivo_geral_plano')) document.getElementById('objetivo_geral_plano').value = '';
  if (document.getElementById('data_inicio_plano')) document.getElementById('data_inicio_plano').value = '';
  if (document.getElementById('data_fim_plano')) document.getElementById('data_fim_plano').value = '';
  if (document.getElementById('status_plano')) document.getElementById('status_plano').value = 'em_andamento';
  const modal = new bootstrap.Modal(document.getElementById('modalPlano'));
  modal.show();
}
</script>
<script>
// --- AJAX para CRIAR PLANO ---
document.getElementById('formPlano').onsubmit = async function(e) {
  e.preventDefault();
  const form = this;
  const formData = new FormData(form);
  const resp = await fetch('../controllers/criar_plano_ajax.php', { method: 'POST', body: formData });
  const data = await resp.json();
  if (data.success) {
    fecharModalPlano();
    mostrarNotificacao('Plano de aula criado com sucesso!', 'success');
    setTimeout(() => location.reload(), 1200);
  } else {
    mostrarNotificacao(data.error || 'Erro ao criar plano', 'danger');
  }
};
// --- AJAX para EDITAR PLANO ---
document.getElementById('formEditarPlano').onsubmit = async function(e) {
  e.preventDefault();
  const form = this;
  const formData = new FormData(form);
  const resp = await fetch('../controllers/editar_plano_ajax.php', { method: 'POST', body: formData });
  const data = await resp.json();
  if (data.success) {
    fecharModalEditarPlano();
    mostrarNotificacao('Plano de aula editado com sucesso!', 'success');
    setTimeout(() => location.reload(), 1200);
  } else {
    mostrarNotificacao(data.error || 'Erro ao editar plano', 'danger');
  }
};
// --- AJAX para EXCLUIR PLANO ---
document.getElementById('formExcluirPlano').onsubmit = async function(e) {
  e.preventDefault();
  const form = this;
  const formData = new FormData(form);
  const resp = await fetch('../controllers/excluir_plano_ajax.php', { method: 'POST', body: formData });
  const data = await resp.json();
  if (data.success) {
    fecharModalExcluirPlano();
    mostrarNotificacao('Plano de aula excluído com sucesso!', 'success');
    setTimeout(() => location.reload(), 1200);
  } else {
    mostrarNotificacao(data.error || 'Erro ao excluir plano', 'danger');
  }
};
</script>
<?php include 'footer.php'; ?>
</body>
</html>