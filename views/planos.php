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
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Planos de Aula - PI Page</title>
    <link rel="icon" type="image/png" href="../assets/img/LOGO_PAGE.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
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
                                        <div class="mb-2"><b>Capítulos:</b>
                                            <button class="btn btn-success btn-sm ms-2" onclick="abrirModalCapitulo(<?= $plano['id'] ?>)">Adicionar Capítulo</button>
                                        </div>
                                        <ul class="list-group mb-2">
                                            <?php if (!empty($capitulosPorPlano[$plano['id']])): foreach ($capitulosPorPlano[$plano['id']] as $cap): ?>
                                                <li class="list-group-item">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <b><?= htmlspecialchars($cap['titulo']) ?></b>
                                                            <span class="badge bg-secondary">Ordem: <?= $cap['ordem'] ?></span>
                                                            <span class="badge badge-status <?= $cap['status'] === 'concluido' ? 'bg-success' : 'bg-info text-dark' ?>">Status: <?= $cap['status'] ?></span>
                                                        </div>
                                                        <div>
                                                            <button class="btn btn-primary btn-sm" onclick="abrirModalEditarCapitulo(<?= $cap['id'] ?>, '<?= htmlspecialchars(addslashes($cap['titulo'])) ?>', <?= $cap['ordem'] ?>, '<?= $cap['status'] ?>', '<?= htmlspecialchars(addslashes($cap['descricao'])) ?>', <?= $cap['duracao_estimativa'] ? $cap['duracao_estimativa'] : 'null' ?>)">Editar</button>
                                                            <button class="btn btn-danger btn-sm" onclick="abrirModalExcluirCapitulo(<?= $cap['id'] ?>, '<?= htmlspecialchars(addslashes($cap['titulo'])) ?>')">Excluir</button>
                                                            <button class="btn btn-success btn-sm" onclick="abrirModalTopico(<?= $cap['id'] ?>)">Adicionar Tópico</button>
                                                        </div>
                                                    </div>
                                                    <div class="mb-1"><b>Descrição:</b> <?= nl2br(htmlspecialchars($cap['descricao'])) ?></div>
                                                    <div class="mb-1"><b>Duração estimada:</b> <?= $cap['duracao_estimativa'] ? $cap['duracao_estimativa'] . ' min' : '-' ?></div>
                                                    <div class="ms-3">
                                                        <b>Tópicos:</b>
                                                        <?php if (!empty($topicosPorCapitulo[$cap['id']])): foreach ($topicosPorCapitulo[$cap['id']] as $top): ?>
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
                                                </li>
                                            <?php endforeach; else: ?>
                                                <li class="list-group-item text-muted py-1 px-2">Nenhum capítulo cadastrado</li>
                                            <?php endif; ?>
                                        </ul>
                                        <!-- Remover o botão Gerenciar -->
                                        <!-- <a href="plano_detalhe.php?id=<?= $plano['id'] ?>" ...>Gerenciar</a> -->
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
          <input type="hidden" name="plano_id" id="plano_id_capitulo">
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

<!-- Scripts para manipular capítulos e tópicos (copiados/adaptados de plano_detalhe.php) -->
<script>
function abrirModalCapitulo(plano_id) {
    document.getElementById('formCapitulo').reset();
    document.getElementById('formCapitulo').action = '';
    document.getElementById('tituloModalCapitulo').innerText = 'Adicionar Capítulo';
    document.getElementById('id_capitulo').value = '';
    document.getElementById('plano_id_capitulo').value = plano_id;
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
    // plano_id não muda ao editar
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
// AJAX para capítulos
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
// AJAX para tópicos
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