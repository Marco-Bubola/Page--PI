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
        .container-fluid { min-height: 100vh; }
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
    <div class="row justify-content-center mb-4">
        <div class="col-12 col-lg-10">
            <div class="card card-plano p-4 mb-3">
                <h2 class="mb-2">Plano: <?= htmlspecialchars($plano['titulo']) ?></h2>
                <div class="plano-label mb-2">Veja abaixo os capítulos e tópicos deste plano de aula. Você pode adicionar, editar ou excluir capítulos e tópicos conforme necessário.</div>
                <div class="mb-2"><b>Disciplina:</b> <?= htmlspecialchars($plano['disciplina_nome']) ?> &nbsp;|&nbsp; <b>Status:</b> <span class="badge badge-status <?= $plano['status'] === 'concluido' ? 'bg-success' : 'bg-warning text-dark' ?>"> <?= $plano['status'] === 'concluido' ? 'Concluído' : 'Em andamento' ?> </span></div>
                <div class="plano-meta mb-2">Criado em: <?= isset($plano['criado_em']) ? date('d/m/Y H:i', strtotime($plano['criado_em'])) : '-' ?></div>
                <div class="card-desc mb-2"> <?= nl2br(htmlspecialchars($plano['descricao'])) ?> </div>
            </div>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Capítulos</h4>
                <button class="btn btn-success btn-sm" onclick="abrirModalCapitulo(<?= $plano_id ?>)">Adicionar Capítulo</button>
            </div>
            <?php if ($capitulos): foreach ($capitulos as $cap): ?>
                <div class="card card-capitulo mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div><b><?= htmlspecialchars($cap['titulo']) ?></b> <span class="badge bg-secondary">Ordem: <?= $cap['ordem'] ?></span> <span class="badge badge-status <?= $cap['status'] === 'concluido' ? 'bg-success' : 'bg-info text-dark' ?>">Status: <?= $cap['status'] ?></span></div>
                        <div>
                            <button class="btn btn-primary btn-sm" onclick="abrirModalEditarCapitulo(<?= $cap['id'] ?>, '<?= htmlspecialchars(addslashes($cap['titulo'])) ?>', <?= $cap['ordem'] ?>, '<?= $cap['status'] ?>')">Editar</button>
                            <button class="btn btn-danger btn-sm" onclick="abrirModalExcluirCapitulo(<?= $cap['id'] ?>, '<?= htmlspecialchars(addslashes($cap['titulo'])) ?>')">Excluir</button>
                            <button class="btn btn-success btn-sm" onclick="abrirModalTopico(<?= $cap['id'] ?>)">Adicionar Tópico</button>
                        </div>
                    </div>
                    <div class="ms-3">
                        <b>Tópicos:</b>
                        <?php if (!empty($topicos[$cap['id']])): foreach ($topicos[$cap['id']] as $top): ?>
                            <div class="topico-box d-flex justify-content-between align-items-center">
                                <span><?= htmlspecialchars($top['descricao']) ?> <span class="badge bg-secondary">Ordem: <?= $top['ordem'] ?></span></span>
                                <span>
                                    <button class="btn btn-primary btn-sm" onclick="abrirModalEditarTopico(<?= $top['id'] ?>, <?= $cap['id'] ?>, '<?= htmlspecialchars(addslashes($top['descricao'])) ?>', <?= $top['ordem'] ?>)">Editar</button>
                                    <button class="btn btn-danger btn-sm" onclick="abrirModalExcluirTopico(<?= $top['id'] ?>, '<?= htmlspecialchars(addslashes($top['descricao'])) ?>')">Excluir</button>
                                </span>
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
<!-- Modais de Capítulo e Tópico (criar, editar, excluir) -->
<?php // Modais: Capítulo criar, editar, excluir; Tópico criar, editar, excluir ?>
<div id="modalCapitulo" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);">
    <div style="background:#fff;padding:30px 20px;border-radius:8px;max-width:400px;margin:100px auto;position:relative;">
        <span onclick="fecharModalCapitulo()" style="position:absolute;top:10px;right:15px;font-size:22px;cursor:pointer;">&times;</span>
        <h4 id="tituloModalCapitulo">Adicionar Capítulo</h4>
        <form id="formCapitulo" action="../controllers/criar_capitulo.php" method="POST">
            <input type="hidden" name="plano_id" value="<?= $plano_id ?>">
            <input type="hidden" name="id_capitulo" id="id_capitulo">
            <input type="text" name="titulo" id="titulo_capitulo" placeholder="Título do capítulo" required class="form-control mb-2">
            <input type="number" name="ordem" id="ordem_capitulo" placeholder="Ordem" required class="form-control mb-2">
            <select name="status" id="status_capitulo" class="form-select mb-2">
                <option value="em_andamento">Em andamento</option>
                <option value="concluido">Concluído</option>
            </select>
            <input type="hidden" name="redirect" value="plano_detalhe.php?id=<?= $plano_id ?>">
            <button type="submit" class="btn btn-primary" id="btnSalvarCapitulo">Salvar</button>
        </form>
    </div>
</div>
<div id="modalExcluirCapitulo" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);">
    <div style="background:#fff;padding:30px 20px;border-radius:8px;max-width:400px;margin:100px auto;position:relative;">
        <span onclick="fecharModalExcluirCapitulo()" style="position:absolute;top:10px;right:15px;font-size:22px;cursor:pointer;">&times;</span>
        <h4>Excluir Capítulo</h4>
        <form action="../controllers/excluir_capitulo.php" method="POST">
            <input type="hidden" name="id_capitulo" id="excluir_id_capitulo">
            <input type="hidden" name="redirect" value="plano_detalhe.php?id=<?= $plano_id ?>">
            <p id="excluir_nome_capitulo" style="margin:15px 0;"></p>
            <button type="submit" class="btn btn-danger">Confirmar Exclusão</button>
            <button type="button" class="btn btn-secondary" onclick="fecharModalExcluirCapitulo()">Cancelar</button>
        </form>
    </div>
</div>
<div id="modalTopico" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);">
    <div style="background:#fff;padding:30px 20px;border-radius:8px;max-width:400px;margin:100px auto;position:relative;">
        <span onclick="fecharModalTopico()" style="position:absolute;top:10px;right:15px;font-size:22px;cursor:pointer;">&times;</span>
        <h4 id="tituloModalTopico">Adicionar Tópico</h4>
        <form id="formTopico" action="../controllers/criar_topico.php" method="POST">
            <input type="hidden" name="capitulo_id" id="capitulo_id_topico">
            <input type="hidden" name="id_topico" id="id_topico">
            <textarea name="descricao" id="descricao_topico" placeholder="Descrição do tópico" required class="form-control mb-2"></textarea>
            <input type="number" name="ordem" id="ordem_topico" placeholder="Ordem" required class="form-control mb-2">
            <input type="hidden" name="redirect" value="plano_detalhe.php?id=<?= $plano_id ?>">
            <button type="submit" class="btn btn-primary" id="btnSalvarTopico">Salvar</button>
        </form>
    </div>
</div>
<div id="modalExcluirTopico" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);">
    <div style="background:#fff;padding:30px 20px;border-radius:8px;max-width:400px;margin:100px auto;position:relative;">
        <span onclick="fecharModalExcluirTopico()" style="position:absolute;top:10px;right:15px;font-size:22px;cursor:pointer;">&times;</span>
        <h4>Excluir Tópico</h4>
        <form action="../controllers/excluir_topico.php" method="POST">
            <input type="hidden" name="id_topico" id="excluir_id_topico">
            <input type="hidden" name="redirect" value="plano_detalhe.php?id=<?= $plano_id ?>">
            <p id="excluir_nome_topico" style="margin:15px 0;"></p>
            <button type="submit" class="btn btn-danger">Confirmar Exclusão</button>
            <button type="button" class="btn btn-secondary" onclick="fecharModalExcluirTopico()">Cancelar</button>
        </form>
    </div>
</div>
<script>
// CAPÍTULO
function abrirModalCapitulo(plano_id) {
    document.getElementById('formCapitulo').action = '../controllers/criar_capitulo.php';
    document.getElementById('tituloModalCapitulo').innerText = 'Adicionar Capítulo';
    document.getElementById('id_capitulo').value = '';
    document.getElementById('titulo_capitulo').value = '';
    document.getElementById('ordem_capitulo').value = '';
    document.getElementById('status_capitulo').value = 'em_andamento';
    document.getElementById('modalCapitulo').style.display = 'block';
}
function abrirModalEditarCapitulo(id, titulo, ordem, status) {
    document.getElementById('formCapitulo').action = '../controllers/editar_capitulo.php';
    document.getElementById('tituloModalCapitulo').innerText = 'Editar Capítulo';
    document.getElementById('id_capitulo').value = id;
    document.getElementById('titulo_capitulo').value = titulo;
    document.getElementById('ordem_capitulo').value = ordem;
    document.getElementById('status_capitulo').value = status;
    document.getElementById('modalCapitulo').style.display = 'block';
}
function fecharModalCapitulo() {
    document.getElementById('modalCapitulo').style.display = 'none';
}
function abrirModalExcluirCapitulo(id, titulo) {
    document.getElementById('excluir_id_capitulo').value = id;
    document.getElementById('excluir_nome_capitulo').innerHTML = 'Tem certeza que deseja excluir o capítulo <b>' + titulo + '</b>?';
    document.getElementById('modalExcluirCapitulo').style.display = 'block';
}
function fecharModalExcluirCapitulo() {
    document.getElementById('modalExcluirCapitulo').style.display = 'none';
}
// TÓPICO
function abrirModalTopico(capitulo_id) {
    document.getElementById('formTopico').action = '../controllers/criar_topico.php';
    document.getElementById('tituloModalTopico').innerText = 'Adicionar Tópico';
    document.getElementById('id_topico').value = '';
    document.getElementById('capitulo_id_topico').value = capitulo_id;
    document.getElementById('descricao_topico').value = '';
    document.getElementById('ordem_topico').value = '';
    document.getElementById('modalTopico').style.display = 'block';
}
function abrirModalEditarTopico(id, capitulo_id, descricao, ordem) {
    document.getElementById('formTopico').action = '../controllers/editar_topico.php';
    document.getElementById('tituloModalTopico').innerText = 'Editar Tópico';
    document.getElementById('id_topico').value = id;
    document.getElementById('capitulo_id_topico').value = capitulo_id;
    document.getElementById('descricao_topico').value = descricao;
    document.getElementById('ordem_topico').value = ordem;
    document.getElementById('modalTopico').style.display = 'block';
}
function fecharModalTopico() {
    document.getElementById('modalTopico').style.display = 'none';
}
function abrirModalExcluirTopico(id, descricao) {
    document.getElementById('excluir_id_topico').value = id;
    document.getElementById('excluir_nome_topico').innerHTML = 'Tem certeza que deseja excluir o tópico <b>' + descricao + '</b>?';
    document.getElementById('modalExcluirTopico').style.display = 'block';
}
function fecharModalExcluirTopico() {
    document.getElementById('modalExcluirTopico').style.display = 'none';
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
</script>
</body>
</html> 