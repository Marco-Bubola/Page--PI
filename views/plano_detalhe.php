<?php
session_start();
if (!isset($_SESSION['usuario_nome']) || ($_SESSION['usuario_tipo'] !== 'coordenador' && $_SESSION['usuario_tipo'] !== 'admin')) {
    header('Location: index.php');
    exit();
}
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: planos.php');
    exit();
}
$plano_id = intval($_GET['id']);
include 'navbar.php';
include 'notificacao.php';
require_once '../config/conexao.php';

// Buscar dados do plano
$stmt = $conn->prepare('SELECT p.*, d.nome AS disciplina_nome FROM planos p JOIN disciplinas d ON p.disciplina_id = d.id WHERE p.id = ?');
$stmt->bind_param('i', $plano_id);
$stmt->execute();
$plano = $stmt->get_result()->fetch_assoc();
if (!$plano) {
    echo '<div class="alert alert-danger">Plano não encontrado.</div>';
    exit();
}
$stmt->close();

// Buscar capítulos do plano
$capitulos = [];
$stmt = $conn->prepare('SELECT * FROM capitulos WHERE plano_id = ? ORDER BY ordem ASC, id ASC');
$stmt->bind_param('i', $plano_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $capitulos[] = $row;
}
$stmt->close();

// Buscar tópicos de todos os capítulos
$topicos = [];
if ($capitulos) {
    $cap_ids = array_column($capitulos, 'id');
    $in = implode(',', array_fill(0, count($cap_ids), '?'));
    $types = str_repeat('i', count($cap_ids));
    $stmt = $conn->prepare('SELECT * FROM topicos WHERE capitulo_id IN (' . $in . ') ORDER BY ordem ASC, id ASC');
    $stmt->bind_param($types, ...$cap_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $topicos[$row['capitulo_id']][] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Plano de Aula - Detalhes</title>
    <link rel="icon" type="image/png" href="../assets/img/LOGO_PAGE.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; }
        .card-plano { border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        .card-capitulo { background: #f8f9fa; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); margin-bottom: 18px; padding: 18px 18px 10px 18px; }
        .topico-box { background: #fff; border-radius: 5px; margin-bottom: 8px; padding: 10px 14px; border: 1px solid #eee; }
        .badge-status { font-size: 1em; }
        .plano-meta { font-size: 0.97em; color: #888; }
        .plano-label { font-size: 1em; color: #666; }
    </style>
</head>
<body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Notificações
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('sucesso')) {
        let msg = '';
        if (urlParams.get('sucesso') === 'capitulo_criado') msg = 'Capítulo criado com sucesso!';
        if (urlParams.get('sucesso') === 'capitulo_editado') msg = 'Capítulo editado com sucesso!';
        if (urlParams.get('sucesso') === 'capitulo_excluido') msg = 'Capítulo excluído com sucesso!';
        if (urlParams.get('sucesso') === 'topico_criado') msg = 'Tópico criado com sucesso!';
        if (urlParams.get('sucesso') === 'topico_editado') msg = 'Tópico editado com sucesso!';
        if (urlParams.get('sucesso') === 'topico_excluido') msg = 'Tópico excluído com sucesso!';
        if (msg) mostrarNotificacao(msg, 'success');
    }
    if (urlParams.has('erro')) {
        let msg = '';
        if (urlParams.get('erro') === 'erro_banco') msg = 'Erro ao salvar no banco!';
        if (urlParams.get('erro') === 'dados_invalidos') msg = 'Dados inválidos!';
        if (msg) mostrarNotificacao(msg, 'danger');
    }
</script>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card card-plano p-4 mb-3">
                <h2 class="mb-2">Plano: <?= htmlspecialchars($plano['titulo']) ?></h2>
                <div class="plano-label mb-2">Veja abaixo os capítulos e tópicos deste plano de aula. Você pode adicionar, editar ou excluir capítulos e tópicos conforme necessário.</div>
                <div class="mb-2"><b>Disciplina:</b> <?= htmlspecialchars($plano['disciplina_nome']) ?> &nbsp;|&nbsp; <b>Status:</b> <span class="badge badge-status <?= $plano['status'] === 'concluido' ? 'bg-success' : 'bg-warning text-dark' ?>"> <?= $plano['status'] === 'concluido' ? 'Concluído' : 'Em andamento' ?> </span></div>
                <div class="plano-meta mb-2">Criado em: <?= isset($plano['criado_em']) ? date('d/m/Y H:i', strtotime($plano['criado_em'])) : '-' ?></div>
                <div class="plano-meta mb-2">Data início: <?= isset($plano['data_inicio']) ? date('d/m/Y', strtotime($plano['data_inicio'])) : '-' ?> | Data fim: <?= isset($plano['data_fim']) ? date('d/m/Y', strtotime($plano['data_fim'])) : '-' ?></div>
                <div class="card-desc mb-2"><b>Objetivo Geral:</b> <?= nl2br(htmlspecialchars($plano['objetivo_geral'])) ?></div>
                <div class="card-desc mb-2"> <?= nl2br(htmlspecialchars($plano['descricao'])) ?> </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Capítulos</h4>
                <button class="btn btn-success btn-sm" onclick="abrirModalCapitulo(<?= $plano_id ?>)">Adicionar Capítulo</button>
            </div>
            <?php if ($capitulos): foreach ($capitulos as $cap): ?>
                <div class="card card-capitulo mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div><b><?= htmlspecialchars($cap['titulo']) ?></b> <span class="badge bg-secondary">Ordem: <?= $cap['ordem'] ?></span> <span class="badge badge-status <?= $cap['status'] === 'concluido' ? 'bg-success' : 'bg-info text-dark' ?>">Status: <?= $cap['status'] ?></span></div>
                        <div>
                            <button class="btn btn-primary btn-sm" onclick="abrirModalEditarCapitulo(<?= $cap['id'] ?>, '<?= htmlspecialchars(addslashes($cap['titulo'])) ?>', <?= $cap['ordem'] ?>, '<?= $cap['status'] ?>', '<?= htmlspecialchars(addslashes($cap['descricao'])) ?>', <?= $cap['duracao_estimativa'] ? $cap['duracao_estimativa'] : 'null' ?>)">Editar</button>
                            <button class="btn btn-danger btn-sm" onclick="abrirModalExcluirCapitulo(<?= $cap['id'] ?>, '<?= htmlspecialchars(addslashes($cap['titulo'])) ?>')">Excluir</button>
                            <button class="btn btn-success btn-sm" onclick="abrirModalTopico(<?= $cap['id'] ?>)">Adicionar Tópico</button>
                        </div>
                    </div>
                    <div class="mb-2"><b>Descrição:</b> <?= nl2br(htmlspecialchars($cap['descricao'])) ?></div>
                    <div class="mb-2"><b>Duração estimada:</b> <?= $cap['duracao_estimativa'] ? $cap['duracao_estimativa'] . ' min' : '-' ?></div>
                    <div class="ms-3">
                        <b>Tópicos:</b>
                        <?php if (!empty($topicos[$cap['id']])): foreach ($topicos[$cap['id']] as $top): ?>
                            <div class="topico-box mb-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <b><?= htmlspecialchars($top['titulo']) ?></b>
                                        <span class="badge bg-secondary">Ordem: <?= $top['ordem'] ?></span>
                                        <span class="badge badge-status <?= $top['status'] === 'concluido' ? 'bg-success' : ($top['status'] === 'pendente' ? 'bg-warning text-dark' : 'bg-info text-dark') ?>">Status: <?= $top['status'] ?></span>
                                    </div>
                                    <div>
                                        <button class="btn btn-primary btn-sm" onclick="abrirModalEditarTopico(<?= $top['id'] ?>, <?= $cap['id'] ?>, '<?= htmlspecialchars(addslashes($top['titulo'])) ?>', '<?= htmlspecialchars(addslashes($top['descricao'])) ?>', '<?= $top['status'] ?>', '<?= htmlspecialchars(addslashes($top['observacoes'] ?? '')) ?>')">Editar</button>
                                        <button class="btn btn-danger btn-sm" onclick="abrirModalExcluirTopico(<?= $top['id'] ?>, '<?= htmlspecialchars(addslashes($top['titulo'])) ?>')">Excluir</button>
                                    </div>
                                </div>
                                <div><b>Descrição:</b> <?= nl2br(htmlspecialchars($top['descricao'])) ?></div>
                                <?php if (!empty($top['observacoes'])): ?><div><b>Observações:</b> <?= nl2br(htmlspecialchars($top['observacoes'])) ?></div><?php endif; ?>
                                <div class="text-muted" style="font-size:0.93em;">Criado em: <?= date('d/m/Y H:i', strtotime($top['data_criacao'])) ?> | Atualizado em: <?= date('d/m/Y H:i', strtotime($top['data_atualizacao'])) ?></div>
                            </div>
                        <?php endforeach; else: ?>
                            <div class="text-muted">Nenhum tópico cadastrado.</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; else: ?>
                <div class="alert alert-info">Nenhum capítulo cadastrado.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- Modal Capítulo (criar/editar) -->
<div class="modal fade" id="modalCapitulo" tabindex="-1" aria-labelledby="tituloModalCapitulo" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="tituloModalCapitulo">Adicionar Capítulo</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <form id="formCapitulo">
        <div class="modal-body">
          <input type="hidden" name="plano_id" value="<?= $plano_id ?>">
          <input type="hidden" name="id_capitulo" id="id_capitulo">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="titulo_capitulo" class="form-label">Título do capítulo</label>
              <input type="text" name="titulo" id="titulo_capitulo" placeholder="Título do capítulo" required class="form-control">
            </div>
            <div class="col-md-6">
              <label for="duracao_capitulo" class="form-label">Duração estimada (min)</label>
              <input type="number" name="duracao_estimativa" id="duracao_capitulo" placeholder="Duração estimada (min)" class="form-control">
            </div>
            <div class="col-md-6">
              <label for="status_capitulo" class="form-label">Status</label>
              <select name="status" id="status_capitulo" class="form-select">
                <option value="em_andamento">Em andamento</option>
                <option value="concluido">Concluído</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="descricao_capitulo" class="form-label">Descrição do capítulo</label>
              <textarea name="descricao" id="descricao_capitulo" placeholder="Descrição do capítulo" class="form-control" rows="2"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Modal Tópico (criar/editar) -->
<div class="modal fade" id="modalTopico" tabindex="-1" aria-labelledby="tituloModalTopico" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="tituloModalTopico">Adicionar Tópico</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <form id="formTopico">
        <div class="modal-body">
          <input type="hidden" name="capitulo_id" id="capitulo_id_topico">
          <input type="hidden" name="id_topico" id="id_topico">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="titulo_topico" class="form-label">Título do tópico</label>
              <input type="text" name="titulo" id="titulo_topico" placeholder="Título do tópico" required class="form-control">
            </div>
            <div class="col-md-6">
              <label for="status_topico" class="form-label">Status</label>
              <select name="status" id="status_topico" class="form-select">
                <option value="em_andamento">Em andamento</option>
                <option value="concluido">Concluído</option>
                <option value="pendente">Pendente</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="descricao_topico" class="form-label">Descrição do tópico</label>
              <textarea name="descricao" id="descricao_topico" placeholder="Descrição do tópico" required class="form-control" rows="2"></textarea>
            </div>
            <div class="col-md-6">
              <label for="observacoes_topico" class="form-label">Observações</label>
              <textarea name="observacoes" id="observacoes_topico" placeholder="Observações" class="form-control" rows="2"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Modal Exclusão Capítulo -->
<div class="modal fade" id="modalExcluirCapitulo" tabindex="-1" aria-labelledby="excluirCapituloLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="excluirCapituloLabel">Excluir Capítulo</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <form id="formExcluirCapitulo">
        <div class="modal-body">
          <input type="hidden" name="id_capitulo" id="excluir_id_capitulo">
          <p id="excluir_nome_capitulo" style="margin:15px 0;"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-danger">Confirmar Exclusão</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Modal Exclusão Tópico -->
<div class="modal fade" id="modalExcluirTopico" tabindex="-1" aria-labelledby="excluirTopicoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="excluirTopicoLabel">Excluir Tópico</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <form id="formExcluirTopico">
        <div class="modal-body">
          <input type="hidden" name="id_topico" id="excluir_id_topico">
          <p id="excluir_nome_topico" style="margin:15px 0;"></p>
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
// Função para mostrar mensagem dinâmica
function mostrarNotificacao(msg, tipo = 'success') {
    let notif = document.createElement('div');
    notif.className = 'alert alert-' + (tipo === 'success' ? 'success' : 'danger') + ' position-fixed top-0 start-50 translate-middle-x mt-3 shadow';
    notif.style.zIndex = 2000;
    notif.style.minWidth = '300px';
    notif.innerHTML = msg;
    document.body.appendChild(notif);
    setTimeout(() => notif.remove(), 3000);
}
// CAPÍTULO
function abrirModalCapitulo(plano_id) {
    document.getElementById('formCapitulo').reset();
    document.getElementById('formCapitulo').action = '';
    document.getElementById('tituloModalCapitulo').innerText = 'Adicionar Capítulo';
    document.getElementById('id_capitulo').value = '';
    document.getElementById('titulo_capitulo').value = '';
    document.getElementById('descricao_capitulo').value = '';
    document.getElementById('duracao_capitulo').value = '';
    document.getElementById('status_capitulo').value = 'em_andamento';
    const modal = new bootstrap.Modal(document.getElementById('modalCapitulo'));
    modal.show();
}
function abrirModalEditarCapitulo(id, titulo, ordem, status, descricao, duracao) {
    document.getElementById('formCapitulo').action = '';
    document.getElementById('tituloModalCapitulo').innerText = 'Editar Capítulo';
    document.getElementById('id_capitulo').value = id;
    document.getElementById('titulo_capitulo').value = titulo;
    document.getElementById('status_capitulo').value = status;
    document.getElementById('descricao_capitulo').value = descricao || '';
    document.getElementById('duracao_capitulo').value = duracao && duracao !== 'null' ? duracao : '';
    const modal = new bootstrap.Modal(document.getElementById('modalCapitulo'));
    modal.show();
}
function fecharModalCapitulo() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalCapitulo'));
    if (modal) modal.hide();
}
function abrirModalExcluirCapitulo(id, titulo) {
    document.getElementById('excluir_id_capitulo').value = id;
    document.getElementById('excluir_nome_capitulo').innerHTML = 'Tem certeza que deseja excluir o capítulo <b>' + titulo + '</b>?';
    const modal = new bootstrap.Modal(document.getElementById('modalExcluirCapitulo'));
    modal.show();
}
function fecharModalExcluirCapitulo() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalExcluirCapitulo'));
    if (modal) modal.hide();
}
// TÓPICO
function abrirModalTopico(capitulo_id) {
    document.getElementById('formTopico').reset();
    document.getElementById('formTopico').action = '';
    document.getElementById('tituloModalTopico').innerText = 'Adicionar Tópico';
    document.getElementById('id_topico').value = '';
    document.getElementById('capitulo_id_topico').value = capitulo_id;
    document.getElementById('titulo_topico').value = '';
    document.getElementById('descricao_topico').value = '';
    document.getElementById('status_topico').value = 'em_andamento';
    document.getElementById('observacoes_topico').value = '';
    const modal = new bootstrap.Modal(document.getElementById('modalTopico'));
    modal.show();
}
function abrirModalEditarTopico(id, capitulo_id, titulo, descricao, status, observacoes) {
    document.getElementById('formTopico').action = '';
    document.getElementById('tituloModalTopico').innerText = 'Editar Tópico';
    document.getElementById('id_topico').value = id;
    document.getElementById('capitulo_id_topico').value = capitulo_id;
    document.getElementById('titulo_topico').value = titulo;
    document.getElementById('descricao_topico').value = descricao;
    document.getElementById('status_topico').value = status;
    document.getElementById('observacoes_topico').value = observacoes;
    const modal = new bootstrap.Modal(document.getElementById('modalTopico'));
    modal.show();
}
function fecharModalTopico() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalTopico'));
    if (modal) modal.hide();
}
function abrirModalExcluirTopico(id, titulo) {
    document.getElementById('excluir_id_topico').value = id;
    document.getElementById('excluir_nome_topico').innerHTML = 'Tem certeza que deseja excluir o tópico <b>' + titulo + '</b>?';
    const modal = new bootstrap.Modal(document.getElementById('modalExcluirTopico'));
    modal.show();
}
function fecharModalExcluirTopico() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalExcluirTopico'));
    if (modal) modal.hide();
}
window.onclick = function(event) {
    var modals = [
        document.getElementById('modalCapitulo'),
        document.getElementById('modalExcluirCapitulo'),
        document.getElementById('modalTopico'),
        document.getElementById('modalExcluirTopico')
    ];
    modals.forEach(function(modal) {
        if (event.target == modal) modal.style.display = 'none';
    });
}
// Interceptar formulários AJAX
// CAPÍTULO
const formCapitulo = document.getElementById('formCapitulo');
formCapitulo.onsubmit = async function(e) {
    e.preventDefault();
    const id = document.getElementById('id_capitulo').value;
    const url = id ? '../controllers/editar_capitulo_ajax.php' : '../controllers/criar_capitulo_ajax.php';
    const formData = new FormData(formCapitulo);
    const resp = await fetch(url, { method: 'POST', body: formData });
    const data = await resp.json();
    if (data.success) {
        fecharModalCapitulo();
        mostrarNotificacao('Capítulo salvo com sucesso!', 'success');
        setTimeout(() => location.reload(), 1200);
    } else {
        mostrarNotificacao(data.error || 'Erro ao salvar capítulo', 'danger');
    }
};
const formExcluirCapitulo = document.getElementById('formExcluirCapitulo');
formExcluirCapitulo.onsubmit = async function(e) {
    e.preventDefault();
    const id = document.getElementById('excluir_id_capitulo').value;
    const formData = new FormData();
    formData.append('id_capitulo', id);
    const resp = await fetch('../controllers/excluir_capitulo_ajax.php', { method: 'POST', body: formData });
    const data = await resp.json();
    if (data.success) {
        fecharModalExcluirCapitulo();
        mostrarNotificacao('Capítulo excluído com sucesso!', 'success');
        setTimeout(() => location.reload(), 1200);
    } else {
        mostrarNotificacao(data.error || 'Erro ao excluir capítulo', 'danger');
    }
};
// TÓPICO
const formTopico = document.getElementById('formTopico');
formTopico.onsubmit = async function(e) {
    e.preventDefault();
    const id = document.getElementById('id_topico').value;
    const url = id ? '../controllers/editar_topico_ajax.php' : '../controllers/criar_topico_ajax.php';
    const formData = new FormData(formTopico);
    const resp = await fetch(url, { method: 'POST', body: formData });
    const data = await resp.json();
    if (data.success) {
        fecharModalTopico();
        mostrarNotificacao('Tópico salvo com sucesso!', 'success');
        setTimeout(() => location.reload(), 1200);
    } else {
        mostrarNotificacao(data.error || 'Erro ao salvar tópico', 'danger');
    }
};
const formExcluirTopico = document.getElementById('formExcluirTopico');
formExcluirTopico.onsubmit = async function(e) {
    e.preventDefault();
    const id = document.getElementById('excluir_id_topico').value;
    const formData = new FormData();
    formData.append('id_topico', id);
    const resp = await fetch('../controllers/excluir_topico_ajax.php', { method: 'POST', body: formData });
    const data = await resp.json();
    if (data.success) {
        fecharModalExcluirTopico();
        mostrarNotificacao('Tópico excluído com sucesso!', 'success');
        setTimeout(() => location.reload(), 1200);
    } else {
        mostrarNotificacao(data.error || 'Erro ao excluir tópico', 'danger');
    }
};
</script>
<?php include 'footer.php'; ?>
</body>
</html> 