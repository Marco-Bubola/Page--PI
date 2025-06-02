<?php
session_start();
if (!isset($_SESSION['usuario_nome']) || ($_SESSION['usuario_tipo'] !== 'coordenador' && $_SESSION['usuario_tipo'] !== 'admin')) {
    header('Location: index.php');
    exit();
}
include 'navbar.php';
include 'notificacao.php';
require_once '../config/conexao.php';

// Buscar turmas
$turmas = [];
$sql = 'SELECT * FROM turmas ORDER BY ano_letivo DESC, nome';
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $turmas[] = $row;
    }
}
// Buscar todas as disciplinas
$disciplinas = [];
$sql = 'SELECT * FROM disciplinas ORDER BY nome';
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $disciplinas[] = $row;
    }
}
// Buscar disciplinas vinculadas para cada turma (para edição)
$turmaDisciplinas = [];
$sql = 'SELECT turma_id, disciplina_id FROM turma_disciplinas';
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $turmaDisciplinas[$row['turma_id']][] = $row['disciplina_id'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Turmas - PI Page</title>
    <link rel="icon" type="image/png" href="../assets/img/LOGO_PAGE.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        body { background: #f5f5f5; }
        .container-fluid { min-height: 100vh; }
        .card-turma { border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); min-height: 180px; }
        .card-title { font-size: 1.2rem; font-weight: 600; }
        .turma-meta { font-size: 0.97em; color: #888; }
        .turma-label { font-size: 1em; color: #666; }
    </style>
</head>
<body>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Exibir notificação se houver parâmetro na URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('sucesso')) {
        let msg = '';
        if (urlParams.get('sucesso') === 'turma_criada') msg = 'Turma criada com sucesso!';
        if (urlParams.get('sucesso') === 'turma_editada') msg = 'Turma editada com sucesso!';
        if (urlParams.get('sucesso') === 'turma_excluida') msg = 'Turma excluída com sucesso!';
        if (msg) mostrarNotificacao(msg, 'success');
    }
    if (urlParams.has('erro')) {
        let msg = '';
        if (urlParams.get('erro') === 'erro_banco') msg = 'Erro ao salvar no banco!';
        if (urlParams.get('erro') === 'dados_invalidos') msg = 'Dados inválidos!';
        if (urlParams.get('erro') === 'turma_vinculada') msg = 'Não é possível excluir turma com disciplinas vinculadas!';
        if (msg) mostrarNotificacao(msg, 'danger');
    }
</script>
<div class="container-fluid py-4">
    <div class="row justify-content-center mb-4">
        <div class="col-12 col-lg-9">
            <div class="bg-white rounded shadow-sm p-4 mb-3">
                <h2 class="mb-2">Turmas</h2>
                <div class="turma-label mb-1">Aqui você encontra todas as turmas cadastradas. Cada card mostra o nome, ano letivo, turno e permite gerenciar as disciplinas e planos da turma.</div>
                <button class="btn btn-success mt-2" onclick="abrirModalTurma()">Criar Turma</button>
            </div>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9">
            <div class="row g-4">
                <?php foreach ($turmas as $turma): ?>
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card card-turma h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title mb-1">Turma: <?= htmlspecialchars($turma['nome']) ?></h5>
                                <div class="turma-meta mb-1">Ano letivo: <?= htmlspecialchars($turma['ano_letivo']) ?> | Turno: <?= htmlspecialchars($turma['turno']) ?>
                                    | Início: <?= $turma['inicio'] ? date('d/m/Y', strtotime($turma['inicio'])) : '-' ?>
                                    | Fim: <?= $turma['fim'] ? date('d/m/Y', strtotime($turma['fim'])) : '-' ?>
                                    | Status: <?= htmlspecialchars($turma['status']) ?>
                                </div>
                                <div class="mb-2"><b>Disciplinas:</b> <?php
                                    $ids = isset($turmaDisciplinas[$turma['id']]) ? $turmaDisciplinas[$turma['id']] : [];
                                    $nomes = [];
                                    foreach ($disciplinas as $disc) if (in_array($disc['id'], $ids)) $nomes[] = $disc['nome'];
                                    echo $nomes ? implode(', ', $nomes) : '<span class="text-muted">Nenhuma</span>';
                                ?></div>
                                <div class="d-flex gap-2 mt-auto">
                                    <button class="btn btn-primary btn-sm" onclick="abrirModalEditarTurma(<?= $turma['id'] ?>, '<?= htmlspecialchars(addslashes($turma['nome'])) ?>', '<?= $turma['ano_letivo'] ?>', '<?= $turma['turno'] ?>', '<?= $turma['inicio'] ?>', '<?= $turma['fim'] ?>', '<?= $turma['status'] ?>')">Editar</button>
                                    <button class="btn btn-danger btn-sm" onclick="abrirModalExcluirTurma(<?= $turma['id'] ?>, '<?= htmlspecialchars(addslashes($turma['nome'])) ?>')">Excluir</button>
                                    <a href="planos.php?turma_id=<?= $turma['id'] ?>" class="btn btn-secondary btn-sm">Gerenciar Planos</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<!-- Modal de Criar/Editar Turma -->
<div id="modalTurma" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);">
    <div style="background:#fff;padding:30px 20px;border-radius:8px;max-width:400px;margin:100px auto;position:relative;">
        <span onclick="fecharModalTurma()" style="position:absolute;top:10px;right:15px;font-size:22px;cursor:pointer;">&times;</span>
        <h4 id="tituloModalTurma">Criar Turma</h4>
        <form id="formTurma" action="../controllers/criar_turma.php" method="POST">
            <input type="hidden" name="id_turma" id="id_turma">
            <input type="text" name="nome" id="nome_turma" placeholder="Nome da turma" required class="form-control mb-2">
            <input type="number" name="ano_letivo" id="ano_letivo_turma" placeholder="Ano letivo" required class="form-control mb-2" min="2000" max="2100">
            <select name="turno" id="turno_turma" class="form-select mb-2">
                <option value="manha">Manhã</option>
                <option value="tarde">Tarde</option>
                <option value="noite">Noite</option>
            </select>
            <div class="row mb-2">
                <div class="col">
                    <label>Início:</label>
                    <input type="date" name="inicio" id="inicio_turma" class="form-control">
                </div>
                <div class="col">
                    <label>Fim:</label>
                    <input type="date" name="fim" id="fim_turma" class="form-control">
                </div>
            </div>
            <select name="status" id="status_turma" class="form-select mb-2">
                <option value="ativa">Ativa</option>
                <option value="concluída">Concluída</option>
                <option value="cancelada">Cancelada</option>
            </select>
            <label><b>Disciplinas da turma:</b></label>
            <select name="disciplinas[]" id="disciplinas_turma" class="form-select mb-2" multiple required>
                <option></option>
                <?php foreach ($disciplinas as $disc): ?>
                    <option value="<?= $disc['id'] ?>"><?= htmlspecialchars($disc['nome']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="redirect" value="turmas.php">
            <button type="submit" class="btn btn-primary" id="btnSalvarTurma">Salvar</button>
        </form>
    </div>
</div>
<!-- Modal de Confirmar Exclusão -->
<div id="modalExcluirTurma" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);">
    <div style="background:#fff;padding:30px 20px;border-radius:8px;max-width:400px;margin:100px auto;position:relative;">
        <span onclick="fecharModalExcluirTurma()" style="position:absolute;top:10px;right:15px;font-size:22px;cursor:pointer;">&times;</span>
        <h4>Excluir Turma</h4>
        <form action="../controllers/excluir_turma.php" method="POST">
            <input type="hidden" name="id_turma" id="excluir_id_turma">
            <input type="hidden" name="redirect" value="turmas.php">
            <p id="excluir_nome_turma" style="margin:15px 0;"></p>
            <button type="submit" class="btn btn-danger">Confirmar Exclusão</button>
            <button type="button" class="btn btn-secondary" onclick="fecharModalExcluirTurma()">Cancelar</button>
        </form>
    </div>
</div>
<script>
function abrirModalTurma() {
    document.getElementById('formTurma').action = '../controllers/criar_turma.php';
    document.getElementById('tituloModalTurma').innerText = 'Criar Turma';
    document.getElementById('id_turma').value = '';
    document.getElementById('nome_turma').value = '';
    document.getElementById('ano_letivo_turma').value = '';
    document.getElementById('turno_turma').value = 'manha';
    document.getElementById('inicio_turma').value = '';
    document.getElementById('fim_turma').value = '';
    document.getElementById('status_turma').value = 'ativa';
    let sel = document.getElementById('disciplinas_turma');
    for (let i = 0; i < sel.options.length; i++) sel.options[i].selected = false;
    // Destroy and re-initialize Select2
    if ($('#disciplinas_turma').hasClass('select2-hidden-accessible')) {
        $('#disciplinas_turma').select2('destroy');
    }
    $('#disciplinas_turma').select2({
        width: '100%',
        placeholder: 'Selecione as disciplinas',
        language: 'pt-BR',
        tags: true,
        tokenSeparators: [',', ' ']
    });
    document.getElementById('modalTurma').style.display = 'block';
}
function abrirModalEditarTurma(id, nome, ano, turno, inicio, fim, status) {
    document.getElementById('formTurma').action = '../controllers/editar_turma.php';
    document.getElementById('tituloModalTurma').innerText = 'Editar Turma';
    document.getElementById('id_turma').value = id;
    document.getElementById('nome_turma').value = nome;
    document.getElementById('ano_letivo_turma').value = ano;
    document.getElementById('turno_turma').value = turno;
    document.getElementById('inicio_turma').value = inicio || '';
    document.getElementById('fim_turma').value = fim || '';
    document.getElementById('status_turma').value = status || 'ativa';
    let sel = document.getElementById('disciplinas_turma');
    for (let i = 0; i < sel.options.length; i++) sel.options[i].selected = false;
    let turmaDiscs = {};
    <?php foreach ($turmaDisciplinas as $tid => $dids): ?>
        turmaDiscs[<?= $tid ?>] = [<?= implode(',', $dids) ?>];
    <?php endforeach; ?>
    if (turmaDiscs[id]) {
        for (let i = 0; i < sel.options.length; i++) {
            if (turmaDiscs[id].includes(parseInt(sel.options[i].value))) sel.options[i].selected = true;
        }
    }
    // Destroy and re-initialize Select2
    if ($('#disciplinas_turma').hasClass('select2-hidden-accessible')) {
        $('#disciplinas_turma').select2('destroy');
    }
    $('#disciplinas_turma').select2({
        width: '100%',
        placeholder: 'Selecione as disciplinas',
        language: 'pt-BR',
        tags: true,
        tokenSeparators: [',', ' ']
    });
    document.getElementById('modalTurma').style.display = 'block';
}
function fecharModalTurma() {
    document.getElementById('modalTurma').style.display = 'none';
}
function abrirModalExcluirTurma(id, nome) {
    document.getElementById('excluir_id_turma').value = id;
    document.getElementById('excluir_nome_turma').innerHTML = 'Tem certeza que deseja excluir a turma <b>' + nome + '</b>?';
    document.getElementById('modalExcluirTurma').style.display = 'block';
}
function fecharModalExcluirTurma() {
    document.getElementById('modalExcluirTurma').style.display = 'none';
}
window.onclick = function(event) {
    var modalCriar = document.getElementById('modalTurma');
    var modalExc = document.getElementById('modalExcluirTurma');
    if (event.target == modalCriar) fecharModalTurma();
    if (event.target == modalExc) fecharModalExcluirTurma();
}
</script>
</body>
</html> 