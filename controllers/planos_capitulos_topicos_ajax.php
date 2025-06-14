<?php
session_start();
if (!isset($_SESSION['usuario_nome']) || !in_array($_SESSION['usuario_tipo'], ['coordenador', 'admin', 'professor'])) {
    http_response_code(403);
    exit('Acesso negado');
}
require_once '../config/conexao.php';
$plano_id = isset($_GET['plano_id']) ? intval($_GET['plano_id']) : null;
if (!$plano_id) exit('');

// Buscar capítulos e tópicos do plano
$capitulos = [];
$topicosPorCapitulo = [];
$sql = "SELECT * FROM capitulos WHERE plano_id = $plano_id ORDER BY ordem ASC, id ASC";
$result = $conn->query($sql);
$cap_ids = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $capitulos[] = $row;
        $cap_ids[] = $row['id'];
    }
}
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
$totalCapitulos = count($capitulos);
// Renderiza o HTML do stepper (igual ao trecho do wizard-stepper-capitulos)
ob_start();
if ($totalCapitulos === 0) {
?>
<div id="mensagem-sem-capitulo-<?= $plano_id ?>" class="d-flex justify-content-center align-items-center w-100" style="min-height:220px;">
    <div class="w-100 mx-auto" style="max-width:480px;">
        <div class="p-4 rounded-4 shadow-sm border border-2 border-warning bg-white text-center"
            style="background:linear-gradient(90deg,#fff 80%,#fffbe6 100%);">
            <div class="mb-2">
                <i class="bi bi-exclamation-circle text-warning" style="font-size:2em;"></i>
            </div>
            <div class="fw-bold text-warning mb-1" style="font-size:1.13em;">
                Nenhum capítulo cadastrado neste plano!
            </div>
            <div class="text-muted" style="font-size:1em;">
                Clique em <span class="badge bg-success text-white"><i class="bi bi-plus-circle"></i> Adicionar Capítulo</span> para cadastrar o primeiro capítulo deste plano.
            </div>
        </div>
    </div>
</div>
<?php
} else {
?>
<div class="wizard-stepper-capitulos mb-4" id="wizard-stepper-<?= $plano_id ?>">
    <div class="d-flex flex-row align-items-center justify-content-center gap-4 mb-3" style="gap: 48px !important;">
        <button class="btn btn-outline-primary me-2" id="wizard-prev-top-<?= $plano_id ?>" style="min-width:90px;"><i class="bi bi-arrow-left"></i> Anterior</button>
        <div class="d-flex flex-row align-items-center gap-4" style="gap: 48px !important;">
            <?php foreach ($capitulos as $idx => $cap): ?>
            <div class="wizard-step-circle position-relative <?= $idx === 0 ? 'active' : '' ?>"
                data-step="<?= $idx ?>"
                style="width:54px;height:54px;border-radius:50%;background:<?= $cap['status']==='concluido'?'#28a745':($cap['status']==='cancelado'?'#6c757d':'#0d6efd') ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.7rem;font-weight:bold;cursor:pointer;transition:box-shadow .2s;box-shadow:0 2px 8px #0001;">
                <i class="bi bi-journal-bookmark-fill"></i>
                <?php if ($idx < $totalCapitulos-1): ?>
                <div class="wizard-step-line position-absolute top-50 start-100 translate-middle-y" style="height:5px;width:60px;background:#b6d4fe;z-index:0;"></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <button class="btn btn-outline-primary ms-2" id="wizard-next-top-<?= $plano_id ?>" style="min-width:90px;">Próximo <i class="bi bi-arrow-right"></i></button>
    </div>
    <div class="wizard-step-content position-relative" style="min-height:340px;">
        <?php foreach ($capitulos as $idx => $cap): ?>
        <div class="wizard-step-card" data-step="<?= $idx ?>" style="display:<?= $idx===0?'block':'none' ?>;">
            <div class="row g-4 justify-content-center">
                <div class="col-12 capitulo-card">
                    <?php
                    $capStatus = $cap['status'];
                    $capClass = '';
                    $capMsg = '';
                    if ($capStatus === 'cancelado') {
                        $capClass = 'bg-secondary text-white position-relative';
                        $capMsg = '<div class=\'cap-status-msg text-center fw-bold py-2\' style=\'background:#6c757d;color:#fff;border-radius:10px 10px 0 0;\'>Cancelado</div>';
                    } elseif ($capStatus === 'concluido') {
                        $capClass = 'bg-secondary-subtle text-dark position-relative';
                        $capMsg = '<div class=\'cap-status-msg text-center fw-bold py-2\' style=\'background:#adb5bd;color:#222;border-radius:10px 10px 0 0;\'>Concluído</div>';
                    }
                    ?>
                    <div class="card card-turma h-100 <?= $capClass ?>"
                        style="border-radius:18px; position:relative;
                        <?php if ($capStatus === 'cancelado'): ?>
                            border: 3px solid #6c757d;
                        <?php elseif ($capStatus === 'concluido'): ?>
                            border: 3px solid #28a745;
                        <?php endif; ?>
                        ">
                        <?php if ($capStatus === 'cancelado' || $capStatus === 'concluido'): ?>
                            <div class="status-overlay d-flex flex-column justify-content-center align-items-center"
                                style="position:absolute;top:0;left:0;width:100%;height:100%;
                                background:<?= $capStatus === 'cancelado' ? 'rgba(108,117,125,0.13)' : 'rgba(40,167,69,0.10)' ?>;
                                z-index:1;border-radius:18px;
                                color:<?= $capStatus === 'cancelado' ? '#444' : '#155724' ?>;
                                font-size:1.5em;font-weight:bold;text-shadow:0 2px 8px #fff;
                                pointer-events:none;">
                                <i class="bi <?= $capStatus === 'cancelado' ? 'bi-x-circle-fill' : 'bi-check-circle-fill' ?> mb-2"
                                    style="font-size:2.5em;"></i>
                                <?= $capStatus === 'cancelado' ? 'Capítulo Cancelado' : 'Capítulo Concluído' ?>
                            </div>
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column <?= ($capStatus === 'cancelado' || $capStatus === 'concluido') ? 'opacity-50' : '' ?>" style="position:relative;z-index:2;">
                            <div class="mb-2 d-flex flex-wrap gap-3 align-items-center"
                                style="font-size:1.25rem;">
                                <span
                                    class="badge bg-info-subtle text-dark border border-info"
                                    style="font-size:1.18rem;">
                                    <i class="bi bi-journal-bookmark-fill text-primary"></i>
                                    Capítulo: <b><?= htmlspecialchars($cap['titulo']) ?></b>
                                </span>
                                <span class="badge bg-secondary" style="font-size:1.13rem;">
                                    <i class="bi bi-list-ol"></i> Ordem:
                                    <?= $cap['ordem'] ?>
                                </span>
                                <span class="badge 
                                    <?php
                                        if ($cap['status'] === 'concluido') echo 'bg-success';
                                        elseif ($cap['status'] === 'cancelado') echo 'bg-secondary';
                                        else echo 'bg-info text-dark';
                                    ?>" style="font-size:1.13rem;">
                                    <i class="bi bi-activity"></i> <?= $cap['status'] ?>
                                </span>
                                <div class="ms-auto d-flex gap-2 action-btns-on-top" style="position:absolute;top:10px;right:10px;z-index:1000;">
                                    <button class="btn btn-primary btn-sm"
                                        title="Editar Capítulo" style="font-size:1.15rem;"
                                        onclick="abrirModalEditarCapitulo(<?= $cap['id'] ?>, '<?= htmlspecialchars(addslashes($cap['titulo'])) ?>', <?= $cap['ordem'] ?>, '<?= $cap['status'] ?>', '<?= htmlspecialchars(addslashes($cap['descricao'])) ?>')">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm"
                                        title="Excluir Capítulo" style="font-size:1.15rem;"
                                        onclick="abrirModalExcluirCapitulo(<?= $cap['id'] ?>, '<?= htmlspecialchars(addslashes($cap['titulo'])) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <button class="btn btn-success btn-sm"
                                        title="Adicionar Tópico" style="font-size:1.15rem;"
                                        onclick="abrirModalTopico(<?= $cap['id'] ?>)">
                                        <i class="bi bi-plus-circle"></i>Adicionar Tópico
                                    </button>
                                    <?php if ($cap['status'] !== 'concluido' && $cap['status'] !== 'pendente'): ?>
                                    <span style="display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;background:rgba(255,255,255,0.7);border-radius:50%;box-shadow:0 2px 8px #0002;z-index:51;">
                                        <button class="btn btn-toggle-no-border btn-outline-secondary btn-sm"
                                            title="Ativar/Cancelar Capítulo"
                                            style="font-size:2.2rem;background:none;border:none;box-shadow:none;outline:none;padding:0;margin:0;z-index:1000;pointer-events:auto;position:relative;"
                                            onclick="abrirModalToggleCapitulo(<?= $cap['id'] ?>, '<?= addslashes($cap['titulo']) ?>', '<?= $cap['status'] ?>', this)">
                                            <i class="bi <?= $cap['status'] === 'cancelado' ? 'bi-toggle-on' : 'bi-toggle-off' ?>"></i>
                                        </button>
                                    </span>
                                    <?php elseif ($cap['status'] === 'cancelado'): ?>
                                    <span style="display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;background:rgba(255,255,255,0.7);border-radius:50%;box-shadow:0 2px 8px #0002;z-index:51;">
                                        <button class="btn btn-toggle-no-border btn-outline-secondary btn-sm"
                                            title="Ativar Capítulo"
                                            style="font-size:2.2rem;background:none;border:none;box-shadow:none;outline:none;padding:0;margin:0;z-index:100;pointer-events:auto;"
                                            onclick="abrirModalToggleCapitulo(<?= $cap['id'] ?>, '<?= addslashes($cap['titulo']) ?>', '<?= $cap['status'] ?>', this)">
                                            <i class="bi bi-toggle-on"></i>
                                        </button>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div style="background: linear-gradient(90deg, #e3f0ff 80%, #f8fafc 100%); border: 1.5px solid #b6d4fe; border-radius: 12px; box-shadow: 0 2px 8px #0d6efd11; padding: 15px 20px; display: flex; align-items: center; gap: 12px; min-height: 45px;">
                                    <i class="bi bi-card-text text-primary" style="font-size:1.4rem;"></i>
                                    <span style="color:#222; font-size:1.25rem; line-height:1.6; white-space:pre-line; word-break:break-word;">
                                        <?= nl2br(htmlspecialchars($cap['descricao'])) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="ms-3">
                                <?php if (!empty($topicosPorCapitulo[$cap['id']])): foreach ($topicosPorCapitulo[$cap['id']] as $top): ?>
                                <?php
                                $topStatus = $top['status'];
                                $topClass = '';
                                $topBorder = '';
                                if ($topStatus === 'cancelado') {
                                    $topClass = 'position-relative';
                                    $topBorder = 'border: 3px solid #6c757d;';
                                } elseif ($topStatus === 'concluido') {
                                    $topClass = 'position-relative';
                                    $topBorder = 'border: 3px solid #28a745;';
                                }
                                ?>
                                <div class="mb-3 p-3 rounded shadow-sm <?= $topClass ?>"
                                    style="background:linear-gradient(90deg,#f8fafc 80%,#e3f0ff 100%);border-radius:12px;position:relative;font-size:1.13rem;<?= $topBorder ?>">
                                    <?php if ($topStatus === 'cancelado' || $topStatus === 'concluido'): ?>
                                        <div class="status-overlay d-flex flex-column justify-content-center align-items-center"
                                            style="position:absolute;top:0;left:0;width:100%;height:100%;
                                            background:<?= $topStatus === 'cancelado' ? 'rgba(108,117,125,0.13)' : 'rgba(40,167,69,0.10)' ?>;
                                            z-index:1;border-radius:12px;
                                            color:<?= $topStatus === 'cancelado' ? '#444' : '#155724' ?>;
                                            font-size:1.2em;font-weight:bold;text-shadow:0 2px 8px #fff;
                                            pointer-events:none;">
                                            <i class="bi <?= $topStatus === 'cancelado' ? 'bi-x-circle-fill' : 'bi-check-circle-fill' ?> mb-2"
                                                style="font-size:2em;"></i>
                                            <?= $topStatus === 'cancelado' ? 'Tópico Cancelado' : 'Tópico Concluído' ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="<?= ($topStatus === 'cancelado' || $topStatus === 'concluido') ? 'opacity-50' : '' ?>" style="position:relative;z-index:2;">
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="bi bi-dot fs-4 text-primary me-1"></i>
                                            <span class="fw-bold fs-5 text-primary"
                                                style="font-size:1.18rem;">
                                                <i class="bi bi-lightbulb-fill text-warning"></i>
                                                <?= htmlspecialchars($top['titulo']) ?>
                                            </span>
                                            <span class="badge bg-secondary ms-2"
                                                style="font-size:1.08rem;">
                                                <i class="bi bi-list-ol"></i> Ordem:
                                                <?= $top['ordem'] ?>
                                            </span>
                                            <span class="badge 
                                                <?php
                                                    if ($top['status'] === 'concluido') echo 'bg-success';
                                                    elseif ($top['status'] === 'cancelado') echo 'bg-secondary';
                                                    elseif ($top['status'] === 'pendente') echo 'bg-warning text-dark';
                                                    else echo 'bg-info text-dark';
                                                ?>" style="font-size:1.08rem;">
                                                <i class="bi bi-activity"></i>
                                                <?= $top['status'] ?>
                                            </span>
                                            <span
                                                class="badge bg-light text-muted border border-secondary"
                                                style="font-size:1.08rem;">
                                                <i class="bi bi-calendar-event"></i>
                                                <?= date('d/m/Y', strtotime($top['data_criacao'])) ?>
                                            </span>
                                            <div class="ms-auto d-flex gap-2 action-btns-on-top" >
                                                <button class="btn btn-primary btn-sm"
                                                    title="Editar Tópico"
                                                    style="font-size:1.08rem;"
                                                    onclick="abrirModalEditarTopico(<?= $top['id'] ?>, <?= $cap['id'] ?>, '<?= htmlspecialchars(addslashes($top['titulo'])) ?>', '<?= htmlspecialchars(addslashes($top['descricao'])) ?>', '<?= $top['status'] ?>')">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <button class="btn btn-danger btn-sm"
                                                    title="Excluir Tópico"
                                                    style="font-size:1.08rem;"
                                                    onclick="abrirModalExcluirTopico(<?= $top['id'] ?>, '<?= htmlspecialchars(addslashes($top['titulo'])) ?>')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                                <?php if ($top['status'] !== 'concluido' && $top['status'] !== 'pendente'): ?>
                                                <span style="display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:50%;box-shadow:0 2px 8px #0002;z-index:51;">
                                                    <button class="btn btn-toggle-no-border btn-outline-secondary btn-sm"
                                                        title="Ativar/Cancelar Tópico"
                                                        style="font-size:2rem;background:none;border:none;box-shadow:none;outline:none;padding:0;margin:0;pointer-events:auto;position:relative;"
                                                        onclick="abrirModalToggleTopico(<?= $top['id'] ?>, '<?= addslashes($top['titulo']) ?>', '<?= $top['status'] ?>', this)">
                                                        <i class="bi <?= $top['status'] === 'cancelado' ? 'bi-toggle-on' : 'bi-toggle-off' ?>"></i>
                                                    </button>
                                                </span>
                                                <?php elseif ($top['status'] === 'cancelado'): ?>
                                                <span style="display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:50%;box-shadow:0 2px 8px #0002;z-index:51;">
                                                    <button class="btn btn-toggle-no-border btn-outline-secondary btn-sm"
                                                        title="Ativar Tópico"
                                                        style="font-size:2rem;background:none;border:none;box-shadow:none;outline:none;padding:0;margin:0;z-index:100;pointer-events:auto;"
                                                        onclick="abrirModalToggleTopico(<?= $top['id'] ?>, '<?= addslashes($top['titulo']) ?>', '<?= $top['status'] ?>', this)">
                                                        <i class="bi bi-toggle-on"></i>
                                                    </button>
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="mt-1 mb-1 p-2 rounded"
                                            style="background:#fffbe6;border:1px solid #ffe58f;font-size:1.08rem;">
                                            <i class="bi bi-info-circle text-warning"></i>
                                            <span class="text-dark"><?= nl2br(htmlspecialchars($top['descricao'])) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; else: ?>
                                <div class="d-flex justify-content-center align-items-center"
                                    style="min-height:120px;">
                                    <div class="w-100" style="max-width:420px;">
                                        <div class="p-3 rounded-4 shadow-sm border border-2 border-warning bg-white text-center"
                                            style="background:linear-gradient(90deg,#fff 80%,#fffbe6 100%);">
                                            <div class="mb-2">
                                                <i class="bi bi-exclamation-circle text-warning"
                                                    style="font-size:2em;"></i>
                                            </div>
                                            <div class="fw-bold text-warning mb-1"
                                                style="font-size:1.13em;">
                                                Nenhum tópico cadastrado neste capítulo!
                                            </div>
                                            <div class="text-muted" style="font-size:1em;">
                                                Clique em <span
                                                    class="badge bg-success text-white"><i
                                                        class="bi bi-plus-circle"></i>
                                                    Adicionar Tópico</span> para cadastrar o
                                                primeiro tópico deste capítulo.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Navegação -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <button class="btn btn-outline-primary" id="wizard-prev-<?= $plano_id ?>" style="min-width:100px;"><i class="bi bi-arrow-left"></i> Anterior</button>
                <button class="btn btn-outline-primary" id="wizard-next-<?= $plano_id ?>" style="min-width:100px;">Próximo <i class="bi bi-arrow-right"></i></button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
} // fecha o else do if ($totalCapitulos === 0)
echo ob_get_clean();
