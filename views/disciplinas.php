<?php
session_start();
if (!isset($_SESSION['usuario_nome']) || !in_array($_SESSION['usuario_tipo'], ['coordenador', 'admin', 'professor'])) {
    header('Location: login.php');
    exit();
}
include 'navbar.php';
include 'notificacao.php';
require_once '../config/conexao.php';

// Buscar disciplinas
$disciplinas = [];
$sql = 'SELECT * FROM disciplinas ORDER BY nome';
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $disciplinas[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Disciplinas - PI Page</title>
    <link rel="icon" type="image/png" href="../assets/img/LOGO_PAGE.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; }
        .card-disciplina { border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); min-height: 120px; }
        .card-title { font-size: 1.2rem; font-weight: 600; }
        .disciplina-label { font-size: 1em; color: #666; }
    </style>
</head>
<body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Exibir notificação se houver parâmetro na URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('sucesso')) {
        let msg = '';
        if (urlParams.get('sucesso') === 'disciplina_criada') msg = 'Disciplina criada com sucesso!';
        if (urlParams.get('sucesso') === 'disciplina_editada') msg = 'Disciplina editada com sucesso!';
        if (urlParams.get('sucesso') === 'disciplina_excluida') msg = 'Disciplina excluída com sucesso!';
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
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="bg-white rounded shadow-sm p-4 mb-3">
                <h2 class="mb-2">Disciplinas</h2>
                <div class="disciplina-label mb-1">Aqui você encontra todas as disciplinas cadastradas. Cada card mostra o nome da disciplina e permite editar ou excluir.</div>
                <button class="btn btn-success mt-2" onclick="abrirModalDisciplina()">Criar Disciplina</button>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="row g-4">
                <?php foreach ($disciplinas as $disciplina): ?>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="card card-disciplina h-100 shadow-sm border-0">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="card-title mb-0"><?= htmlspecialchars($disciplina['nome']) ?></h5>
                                    <span class="badge <?= $disciplina['ativa'] ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= $disciplina['ativa'] ? 'Ativa' : 'Inativa' ?>
                                    </span>
                                </div>
                                <div class="mb-1 text-muted" style="font-size:0.95em;">
                                    <i class="bi bi-hash"></i> <b>Código:</b> <?= htmlspecialchars($disciplina['codigo']) ?>
                                </div>
                                <div class="mb-1" style="font-size:0.95em;">
                                    <i class="bi bi-people"></i> <b>Turmas vinculadas:</b> <?= $qtdTurmas[$disciplina['id']] ?? 0 ?>
                                </div>
                                <div class="mb-2" style="font-size:0.97em;">
                                    <i class="bi bi-info-circle"></i>
                                    <b>Descrição:</b>
                                    <?= strlen($disciplina['descricao']) > 80 ? nl2br(htmlspecialchars(substr($disciplina['descricao'],0,80))) . '... <span class="text-primary ver-mais" style="cursor:pointer;" data-desc="' . htmlspecialchars($disciplina['descricao']) . '">ver mais</span>' : nl2br(htmlspecialchars($disciplina['descricao'])) ?>
                                </div>
                                <div class="d-flex gap-2 mt-auto justify-content-center">
                                    <button class="btn btn-primary btn-sm" title="Editar" onclick="abrirModalEditarDisciplina(<?= $disciplina['id'] ?>, '<?= htmlspecialchars(addslashes($disciplina['nome'])) ?>', '<?= htmlspecialchars(addslashes($disciplina['codigo'])) ?>', '<?= htmlspecialchars(addslashes($disciplina['descricao'])) ?>', <?= $disciplina['ativa'] ?>)">
                                        <i class="bi bi-pencil"></i> Editar
                                    </button>
                                    <button class="btn btn-danger btn-sm" title="Excluir" onclick="abrirModalExcluirDisciplina(<?= $disciplina['id'] ?>, '<?= htmlspecialchars(addslashes($disciplina['nome'])) ?>')">
                                        <i class="bi bi-trash"></i> Excluir
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<!-- Modal de Criar Disciplina (Bootstrap 5) -->
<div class="modal fade" id="modalDisciplina" tabindex="-1" aria-labelledby="tituloModalDisciplina" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title" id="tituloModalDisciplina">Criar Nova Disciplina</h3>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <form id="formCriarDisciplina" autocomplete="off">
        <div class="modal-body">
          <div class="mb-3">
            <label for="nome_disciplina" class="form-label">Nome da disciplina</label>
            <input type="text" name="nome_disciplina" id="nome_disciplina" class="form-control" required>
          </div>
          <div class="mb-3">
            <label for="codigo_disciplina" class="form-label">Código</label>
            <input type="text" name="codigo_disciplina" id="codigo_disciplina" class="form-control">
          </div>
          <div class="mb-3">
            <label for="descricao_disciplina" class="form-label">Descrição</label>
            <textarea name="descricao_disciplina" id="descricao_disciplina" class="form-control" rows="2"></textarea>
          </div>
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="ativa_disciplina" id="criar_ativa_disciplina" value="1" checked>
            <label class="form-check-label" for="criar_ativa_disciplina">Ativa</label>
          </div>
        </div>
        <div class="modal-footer d-flex justify-content-center gap-2">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Modal de Editar Disciplina (Bootstrap 5) -->
<div class="modal fade" id="modalEditarDisciplina" tabindex="-1" aria-labelledby="tituloModalEditarDisciplina" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title" id="tituloModalEditarDisciplina">Editar Disciplina</h3>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <form id="formEditarDisciplina" autocomplete="off">
        <input type="hidden" name="id_disciplina" id="editar_id_disciplina">
        <div class="modal-body">
          <div class="mb-3">
            <label for="editar_nome_disciplina" class="form-label">Nome da disciplina</label>
            <input type="text" name="nome_disciplina" id="editar_nome_disciplina" class="form-control" required>
          </div>
          <div class="mb-3">
            <label for="editar_codigo_disciplina" class="form-label">Código</label>
            <input type="text" name="codigo_disciplina" id="editar_codigo_disciplina" class="form-control">
          </div>
          <div class="mb-3">
            <label for="editar_descricao_disciplina" class="form-label">Descrição</label>
            <textarea name="descricao_disciplina" id="editar_descricao_disciplina" class="form-control" rows="2"></textarea>
          </div>
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="ativa_disciplina" id="editar_ativa_disciplina" value="1">
            <label class="form-check-label" for="editar_ativa_disciplina">Ativa</label>
          </div>
        </div>
        <div class="modal-footer d-flex justify-content-center gap-2">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Modal de Confirmar Exclusão (Bootstrap 5) -->
<div class="modal fade" id="modalExcluirDisciplina" tabindex="-1" aria-labelledby="excluirDisciplinaLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title" id="excluirDisciplinaLabel">Excluir Disciplina</h3>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <form id="formExcluirDisciplina">
        <input type="hidden" name="id_disciplina" id="excluir_id_disciplina">
        <div class="modal-body">
          <p id="excluir_nome_disciplina" style="margin:15px 0;"></p>
        </div>
        <div class="modal-footer d-flex justify-content-center gap-2">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-danger">Confirmar Exclusão</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
// Funções para abrir/fechar modais usando Bootstrap 5
function abrirModalDisciplina() {
  document.getElementById('formCriarDisciplina').reset();
  const modal = new bootstrap.Modal(document.getElementById('modalDisciplina'));
  modal.show();
}
function fecharModalDisciplina() {
  const modal = bootstrap.Modal.getInstance(document.getElementById('modalDisciplina'));
  if (modal) modal.hide();
}
function abrirModalEditarDisciplina(id, nome, codigo, descricao, ativa) {
  document.getElementById('formEditarDisciplina').reset();
  document.getElementById('editar_id_disciplina').value = id;
  document.getElementById('editar_nome_disciplina').value = nome;
  document.getElementById('editar_codigo_disciplina').value = codigo;
  document.getElementById('editar_descricao_disciplina').value = descricao;
  document.getElementById('editar_ativa_disciplina').checked = (ativa == 1);
  const modal = new bootstrap.Modal(document.getElementById('modalEditarDisciplina'));
  modal.show();
}
function fecharModalEditarDisciplina() {
  const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarDisciplina'));
  if (modal) modal.hide();
}
function abrirModalExcluirDisciplina(id, nome) {
  document.getElementById('excluir_id_disciplina').value = id;
  document.getElementById('excluir_nome_disciplina').innerHTML = 'Tem certeza que deseja excluir <b>' + nome + '</b>?';
  const modal = new bootstrap.Modal(document.getElementById('modalExcluirDisciplina'));
  modal.show();
}
function fecharModalExcluirDisciplina() {
  const modal = bootstrap.Modal.getInstance(document.getElementById('modalExcluirDisciplina'));
  if (modal) modal.hide();
}

// --- ADICIONE O CÓDIGO ABAIXO PARA AJAX ---

// CRIAR DISCIPLINA AJAX
document.getElementById('formCriarDisciplina').onsubmit = function(e) {
  e.preventDefault();
  const form = e.target;
  const dados = new FormData(form);
  fetch('../controllers/criar_disciplina_ajax.php', {
    method: 'POST',
    body: dados
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) {
      window.location.href = '?sucesso=disciplina_criada';
    } else {
      mostrarNotificacao(res.error || 'Erro ao criar disciplina', 'danger');
    }
  });
};

// EDITAR DISCIPLINA AJAX
document.getElementById('formEditarDisciplina').onsubmit = function(e) {
  e.preventDefault();
  const form = e.target;
  const dados = new FormData(form);
  fetch('../controllers/editar_disciplina_ajax.php', {
    method: 'POST',
    body: dados
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) {
      window.location.href = '?sucesso=disciplina_editada';
    } else {
      mostrarNotificacao(res.error || 'Erro ao editar disciplina', 'danger');
    }
  });
};

// EXCLUIR DISCIPLINA AJAX
document.getElementById('formExcluirDisciplina').onsubmit = function(e) {
  e.preventDefault();
  const form = e.target;
  const dados = new FormData(form);
  fetch('../controllers/excluir_disciplina_ajax.php', {
    method: 'POST',
    body: dados
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) {
      window.location.href = '?sucesso=disciplina_excluida';
    } else {
      mostrarNotificacao(res.error || 'Erro ao excluir disciplina', 'danger');
    }
  });
};
</script>
<?php include 'footer.php'; ?>
</body>
</html>