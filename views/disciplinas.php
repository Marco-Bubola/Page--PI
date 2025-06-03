<?php
session_start();
if (!isset($_SESSION['usuario_nome']) || ($_SESSION['usuario_tipo'] !== 'coordenador' && $_SESSION['usuario_tipo'] !== 'admin')) {
    header('Location: index.php');
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
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card card-disciplina h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title mb-2">Disciplina: <?= htmlspecialchars($disciplina['nome']) ?></h5>
                                <div class="mb-1"><b>Código:</b> <?= htmlspecialchars($disciplina['codigo']) ?></div>
                                <div class="mb-1"><b>Descrição:</b> <?= nl2br(htmlspecialchars($disciplina['descricao'])) ?></div>
                                <div class="mb-2"><b>Ativa:</b> <?= $disciplina['ativa'] ? 'Sim' : 'Não' ?></div>
                                <div class="d-flex gap-2 mt-auto">
                                    <button class="btn btn-primary btn-sm" onclick="abrirModalEditarDisciplina(<?= $disciplina['id'] ?>, '<?= htmlspecialchars(addslashes($disciplina['nome'])) ?>', '<?= htmlspecialchars(addslashes($disciplina['codigo'])) ?>', '<?= htmlspecialchars(addslashes($disciplina['descricao'])) ?>', <?= $disciplina['ativa'] ?>)">Editar</button>
                                    <button class="btn btn-danger btn-sm" onclick="abrirModalExcluirDisciplina(<?= $disciplina['id'] ?>, '<?= htmlspecialchars(addslashes($disciplina['nome'])) ?>')">Excluir</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<!-- Modal de Criar Disciplina (atualizado) -->
<div id="modalDisciplina" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);">
    <div style="background:#fff;padding:30px 20px;border-radius:8px;max-width:350px;margin:100px auto;position:relative;">
        <span onclick="fecharModalDisciplina()" style="position:absolute;top:10px;right:15px;font-size:22px;cursor:pointer;">&times;</span>
        <h3>Criar Nova Disciplina</h3>
        <form action="../controllers/criar_disciplina.php" method="POST">
            <input type="text" name="nome_disciplina" placeholder="Nome da disciplina" required style="width:100%;padding:8px;margin:10px 0;border:1px solid #ccc;border-radius:4px;">
            <input type="text" name="codigo_disciplina" placeholder="Código da disciplina" style="width:100%;padding:8px;margin:10px 0;border:1px solid #ccc;border-radius:4px;">
            <textarea name="descricao_disciplina" placeholder="Descrição" style="width:100%;padding:8px;margin:10px 0;border:1px solid #ccc;border-radius:4px;"></textarea>
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="ativa_disciplina" id="criar_ativa_disciplina" value="1" checked>
                <label class="form-check-label" for="criar_ativa_disciplina">Ativa</label>
            </div>
            <input type="hidden" name="redirect" value="disciplinas.php">
            <button type="submit" class="btn btn-primary">Salvar</button>
        </form>
    </div>
</div>
<!-- Modal de Editar Disciplina (atualizado) -->
<div id="modalEditarDisciplina" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);">
    <div style="background:#fff;padding:30px 20px;border-radius:8px;max-width:350px;margin:100px auto;position:relative;">
        <span onclick="fecharModalEditarDisciplina()" style="position:absolute;top:10px;right:15px;font-size:22px;cursor:pointer;">&times;</span>
        <h3>Editar Disciplina</h3>
        <form id="formEditarDisciplina" action="../controllers/editar_disciplina.php" method="POST">
            <input type="hidden" name="id_disciplina" id="editar_id_disciplina">
            <input type="text" name="nome_disciplina" id="editar_nome_disciplina" placeholder="Nome da disciplina" required style="width:100%;padding:8px;margin:10px 0;border:1px solid #ccc;border-radius:4px;">
            <input type="text" name="codigo_disciplina" id="editar_codigo_disciplina" placeholder="Código da disciplina" style="width:100%;padding:8px;margin:10px 0;border:1px solid #ccc;border-radius:4px;">
            <textarea name="descricao_disciplina" id="editar_descricao_disciplina" placeholder="Descrição" style="width:100%;padding:8px;margin:10px 0;border:1px solid #ccc;border-radius:4px;"></textarea>
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="ativa_disciplina" id="editar_ativa_disciplina" value="1">
                <label class="form-check-label" for="editar_ativa_disciplina">Ativa</label>
            </div>
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        </form>
    </div>
</div>
<!-- Modal de Confirmar Exclusão -->
<div id="modalExcluirDisciplina" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);">
    <div style="background:#fff;padding:30px 20px;border-radius:8px;max-width:350px;margin:100px auto;position:relative;">
        <span onclick="fecharModalExcluirDisciplina()" style="position:absolute;top:10px;right:15px;font-size:22px;cursor:pointer;">&times;</span>
        <h3>Excluir Disciplina</h3>
        <form id="formExcluirDisciplina" action="../controllers/excluir_disciplina.php" method="POST">
            <input type="hidden" name="id_disciplina" id="excluir_id_disciplina">
            <p id="excluir_nome_disciplina" style="margin:15px 0;"></p>
            <button type="submit" class="btn btn-danger">Confirmar Exclusão</button>
            <button type="button" class="btn btn-secondary" onclick="fecharModalExcluirDisciplina()">Cancelar</button>
        </form>
    </div>
</div>
<script>
function abrirModalDisciplina() {
    document.getElementById('modalDisciplina').style.display = 'block';
}
function fecharModalDisciplina() {
    document.getElementById('modalDisciplina').style.display = 'none';
}
function abrirModalEditarDisciplina(id, nome, codigo, descricao, ativa) {
    document.getElementById('editar_id_disciplina').value = id;
    document.getElementById('editar_nome_disciplina').value = nome;
    document.getElementById('editar_codigo_disciplina').value = codigo;
    document.getElementById('editar_descricao_disciplina').value = descricao;
    document.getElementById('editar_ativa_disciplina').checked = (ativa == 1);
    document.getElementById('modalEditarDisciplina').style.display = 'block';
}
function fecharModalEditarDisciplina() {
    document.getElementById('modalEditarDisciplina').style.display = 'none';
}
function abrirModalExcluirDisciplina(id, nome) {
    document.getElementById('excluir_id_disciplina').value = id;
    document.getElementById('excluir_nome_disciplina').innerHTML = 'Tem certeza que deseja excluir <b>' + nome + '</b>?';
    document.getElementById('modalExcluirDisciplina').style.display = 'block';
}
function fecharModalExcluirDisciplina() {
    document.getElementById('modalExcluirDisciplina').style.display = 'none';
}
window.onclick = function(event) {
    var modalEdit = document.getElementById('modalEditarDisciplina');
    var modalExc = document.getElementById('modalExcluirDisciplina');
    var modalCriar = document.getElementById('modalDisciplina');
    if (event.target == modalEdit) fecharModalEditarDisciplina();
    if (event.target == modalExc) fecharModalExcluirDisciplina();
    if (event.target == modalCriar) fecharModalDisciplina();
}
</script>
<?php include 'footer.php'; ?>
</body>
</html> 