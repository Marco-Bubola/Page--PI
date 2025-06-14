<!-- Modal de Dicas de Funcionamento extraído de planos.php -->
<div id="modalDicasPlanos" style="display:none;position:fixed;z-index:2100;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.45);">
    <div class="modal-dicas-content" style="max-width:760px;min-width:420px;width:98vw;">
        <div class="modal-dicas-header">
            <div class="modal-dicas-icone">
                <i class="bi bi-lightbulb-fill"></i>
            </div>
            <h4 class="mb-0 text-white">Dicas de Funcionamento</h4>
            <span onclick="fecharModalDicasPlanos()" class="modal-dicas-close">&times;</span>
        </div>
        <div class="modal-dicas-body">
            <!-- Stepper -->
            <div id="stepperDicasPlanos" class="mb-4">
                <div class="d-flex justify-content-center align-items-center gap-2 mb-3">
                    <span class="step-circle" id="stepCirclePlanos1"><i class="bi"></i></span>
                    <span class="step-line"></span>
                    <span class="step-circle" id="stepCirclePlanos2"><i class="bi"></i></span>
                    <span class="step-line"></span>
                    <span class="step-circle" id="stepCirclePlanos3"><i class="bi"></i></span>
                    <span class="step-line"></span>
                    <span class="step-circle" id="stepCirclePlanos4"><i class="bi"></i></span>
                    <span class="step-line"></span>
                    <span class="step-circle" id="stepCirclePlanos5"><i class="bi"></i></span>
                    <span class="step-line"></span>
                    <span class="step-circle" id="stepCirclePlanos6"><i class="bi"></i></span>
                </div>
            </div>
            <div id="stepContentDicasPlanos">
                <!-- Conteúdo dos steps será preenchido via JS -->
            </div>
        </div>
        <div class="modal-dicas-footer">
            <button class="btn btn-outline-primary" id="btnStepAnteriorPlanos" style="display:none;"><i class="bi bi-arrow-left"></i> Anterior</button>
            <button class="btn btn-outline-primary ms-3" id="btnStepProximoPlanos">Próximo <i class="bi bi-arrow-right"></i></button>
        </div>
    </div>
    <style>
    .btn-gradient-dicas {
        background: linear-gradient(90deg,#0d6efd 60%,#4f8cff 100%);
        color: #fff !important;
        border: none;
        transition: background 0.2s, box-shadow 0.2s;
    }
    .btn-gradient-dicas:hover, .btn-gradient-dicas:focus {
        background: linear-gradient(90deg,#4f8cff 60%,#0d6efd 100%);
        color: #fff !important;
        box-shadow: 0 4px 16px #0d6efd33;
    }
    .modal-dicas-content {
        background: #fff;
        border-radius: 22px;
        max-width: 760px;
        width: 98vw;
        min-width: 420px;
        min-height: 660px;
        max-height: 900px;
        margin: 60px auto;
        position: relative;
        box-shadow: 0 8px 32px rgba(0,0,0,0.18);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .modal-dicas-header {
        background: linear-gradient(90deg,#0d6efd 60%,#4f8cff 100%);
        padding: 28px 36px 20px 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 18px;
        position: relative;
        text-align: center;
    }
    .modal-dicas-icone {
        background: #fff;
        color: #0d6efd;
        border-radius: 50%;
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        box-shadow: 0 2px 8px #0d6efd33;
    }
    .modal-dicas-header h4 {
        color: #fff;
        font-weight: bold;
        font-size: 1.55em;
        margin-bottom: 0;
        flex: 1 1 auto;
        text-align: center;
    }
    .modal-dicas-close {
        position: absolute;
        top: 18px;
        right: 28px;
        font-size: 32px;
        cursor: pointer;
        color: #fff;
        opacity: 0.8;
        transition: opacity 0.2s;
    }
    .modal-dicas-close:hover {
        opacity: 1;
    }
    .modal-dicas-body {
        padding: 38px 32px 28px 32px;
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
    }
    .modal-dicas-footer {
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 18px;
        padding: 22px 0 18px 0;
        background: #f8faff;
        border-top: 1.5px solid #e3e9f7;
        border-radius: 0 0 22px 22px;
        min-height: 70px;
    }
    .modal-dicas-footer .btn {
        min-width: 120px;
        font-size: 1.08em;
        font-weight: 500;
    }
    #modalDicasPlanos .step-circle {
        width: 32px; height: 32px; border-radius: 50%; background: #e3e9f7; color: #0d6efd; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.15em; border: 2px solid #b6c6e6;
        transition: background 0.2s, color 0.2s;
    }
    #modalDicasPlanos .step-circle.active {
        background: #0d6efd; color: #fff; border-color: #0d6efd;
    }
    #modalDicasPlanos .step-line {
        flex: 1 1 0; height: 3px; background: #b6c6e6;
    }
    #stepContentDicasPlanos {
        min-height: 110px;
        max-height: 180px;
        margin-bottom: 0.5em;
    }
    .dica-step-card {
        display: flex;
        align-items: flex-start;
        gap: 18px;
        border-radius: 16px;
        padding: 18px 18px 18px 18px;
        margin-bottom: 0.5em;
        box-shadow: 0 2px 12px #e3e9f7;
        font-size: 1.13em;
        font-weight: 500;
        background: #f8faff;
    }
    .dica-step-icone {
        font-size: 2.3em;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-top: 2px;
        box-shadow: 0 2px 8px #e3e9f7;
    }
    .dica-blue { color: #0d6efd; background: #e3e9f7; }
    .dica-green { color: #198754; background: #e6f7ec; }
    .dica-yellow { color: #ffc107; background: #fffbe6; }
    .dica-red { color: #dc3545; background: #ffe6e9; }
    .dica-orange { color: #fd7e14; background: #fff3e6; }
    .dica-purple { color: #6f42c1; background: #f3e6ff; }
    .bg-dica-blue { background: #f8faff; }
    .bg-dica-green { background: #e6f7ec; }
    .bg-dica-yellow { background: #fffbe6; }
    .bg-dica-red { background: #ffe6e9; }
    .bg-dica-orange { background: #fff3e6; }
    .bg-dica-purple { background: #f3e6ff; }
    .text-dica-blue { color: #0d6efd; }
    .text-dica-purple { color: #6f42c1; }
    @media (max-width: 600px) {
         .modal-dicas-content {
            max-width: 98vw;
            min-width: 0;
            padding: 0;
        }
        .modal-dicas-header, .modal-dicas-body {
            padding-left: 12px;
            padding-right: 12px;
        }
  .wizard-stepper-capitulos {
    padding: 0 2px;
  }
  .wizard-step-circle {
    width: 38px !important;
    height: 38px !important;
    font-size: 1.1rem !important;
  }
  .wizard-step-line {
    width: 24px !important;
    min-width: 12px !important;
  }
  .wizard-step-content {
    min-height: 0 !important;
    padding: 0 !important;
  }
  .wizard-step-card {
    padding: 8px 2px !important;
    border-radius: 10px !important;
    margin-bottom: 10px !important;
  }
  .capitulo-card .card-body {
    padding: 10px 4px !important;
    font-size: 0.98em !important;
  }
  .capitulo-card .badge, .capitulo-card .action-btns-on-top button {
    font-size: 0.98em !important;
    padding: 4px 7px !important;
  }
  .status-overlay {
    font-size: 1.1em !important;
  }
  .mb-3, .mb-2, .mt-3, .mt-2 {
    margin-bottom: 0.5rem !important;
    margin-top: 0.5rem !important;
  }
  .p-3, .p-4 {
    padding: 0.5rem !important;
  }
  .col-12, .row, .g-4, .justify-content-center {
    padding: 0 !important;
    margin: 0 !important;
  }
}
    </style>
    <script>
        // Steps do modal de dicas para planos
const stepsDicasPlanos = [
    {
        title: 'Criar Plano',
        html: `<div class="dica-step-card bg-dica-green"><div class="dica-step-icone dica-green"><i class="bi bi-plus-circle"></i></div><div><b>1º passo:</b> Clique em <span class="badge bg-success text-white"><i class="bi bi-plus-circle"></i> Criar Plano</span> para adicionar um novo plano de aula para a disciplina/turma.<br><span class="text-success">Você só pode criar capítulos e tópicos depois de criar o plano.</span><br>Preencha os campos obrigatórios e clique em <span class="badge bg-primary text-white"><i class="bi bi-check-circle"></i> Salvar</span>.<br><div class='exemplo-bloco bg-success-subtle text-success border border-success mt-2 p-2 rounded-3'><b>Exemplo:</b> Plano: <span class='fw-bold'>Matemática Básica</span></div></div></div>`
    },
    {
        title: 'Criar Capítulo',
        html: `<div class="dica-step-card bg-dica-blue"><div class="dica-step-icone dica-blue"><i class="bi bi-journal-bookmark-fill"></i></div><div><b>2º passo:</b> Após criar o plano, clique em <span class="badge bg-success text-white"><i class="bi bi-plus-circle"></i> Adicionar Capítulo</span> para criar capítulos dentro do plano.<br><span class="text-primary">Os capítulos agrupam os conteúdos do plano e são obrigatórios para cadastrar tópicos.</span><br><div class='exemplo-bloco bg-info-subtle text-primary border border-info mt-2 p-2 rounded-3'><b>Exemplo:</b> Capítulo 1 - Introdução<br>Capítulo 2 - Operações Básicas</div></div></div>`
    },
    {
        title: 'Criar Tópico',
        html: `<div class="dica-step-card bg-dica-yellow"><div class="dica-step-icone dica-yellow"><i class="bi bi-lightbulb-fill"></i></div><div><b>3º passo:</b> Após criar um capítulo, clique em <span class="badge bg-success text-white"><i class="bi bi-plus-circle"></i> Adicionar Tópico</span> para cadastrar tópicos dentro do capítulo.<br><span class="text-warning">Cada tópico representa um conteúdo a ser ministrado.</span><br><div class='exemplo-bloco bg-warning-subtle text-warning border border-warning mt-2 p-2 rounded-3'><b>Exemplo:</b> Tópico 1 - O que é adição?<br>Tópico 2 - Exercícios práticos</div></div></div>`
    },
    {
        title: 'Botões: Editar, Excluir, Ativar/Cancelar',
        html: `<div class="dica-step-card bg-dica-orange"><div class="dica-step-icone dica-orange"><i class="bi bi-pencil-square"></i></div><div>
        <b>Editar:</b> <span class="badge bg-primary text-white"><i class="bi bi-pencil-square"></i> Editar</span> permite alterar qualquer plano, capítulo ou tópico.<br>
        <b>Excluir:</b> <span class="badge bg-danger text-white"><i class="bi bi-trash"></i> Excluir</span> remove o item permanentemente.<br>
        <b>Ativar/Concluir/Cancelar:</b> <span class="badge bg-warning text-dark"><i class="bi bi-toggle-off"></i> / <i class="bi bi-toggle-on"></i></span> muda o status do item.<br>
        <span class="text-muted">Ao cancelar, o item fica inativo. Ao concluir, marca como finalizado.</span>
        <div class="mt-3 d-flex flex-wrap gap-2">
            <span class="exemplo-bloco badge bg-primary text-white p-2"><i class="bi bi-pencil-square"></i> Editar</span>
            <span class="exemplo-bloco badge bg-danger text-white p-2"><i class="bi bi-trash"></i> Excluir</span>
            <span class="exemplo-bloco badge bg-warning text-dark p-2"><i class="bi bi-toggle-off"></i> / <i class="bi bi-toggle-on"></i> Ativar/Cancelar</span>
        </div>
        <div class="mt-2 text-muted small">Os botões aparecem ao lado de cada item. Sempre confirme a ação no modal exibido.</div>
        </div></div>`
    },
    {
        title: 'Badges, Status e Navegação',
        html: `<div class="dica-step-card bg-dica-purple"><div class="dica-step-icone dica-purple"><i class="bi bi-collection"></i></div><div><span class="fw-bold text-dica-purple">Badges:</span> Mostram o total de capítulos, tópicos, status e datas.<br><span class="fw-bold text-dica-purple">Status:</span> Indicam se o plano, capítulo ou tópico está em andamento, concluído ou cancelado.<br><span class="fw-bold text-dica-purple">Abas:</span> Navegue entre disciplinas usando as abas no topo (quando em uma turma).<br><div class='exemplo-bloco mt-2'><span class='badge bg-success'><i class='bi bi-check-circle-fill'></i> Concluído</span> <span class='badge bg-warning text-dark'><i class='bi bi-hourglass-split'></i> Em andamento</span> <span class='badge bg-secondary'><i class='bi bi-x-circle-fill'></i> Cancelado</span></div><span class="text-muted">Dica: Passe o mouse sobre os badges para ver mais detalhes.</span></div></div>`
    }
];
let stepAtualPlanos = 0;
function mostrarStepDicasPlanos(idx) {
    stepAtualPlanos = idx;
    // Ícones para cada step (ordem deve bater com stepsDicasPlanos)
    let icones = [
        'bi-plus-circle',
        'bi-journal-bookmark-fill',
        'bi-lightbulb-fill',
        'bi-pencil-square',
        'bi-collection'
    ];
    for (let i = 0; i < 6; i++) {
        let el = document.getElementById('stepCirclePlanos'+(i+1));
        if (el) {
            el.style.display = i < stepsDicasPlanos.length ? '' : 'none';
            el.classList.toggle('active', i === idx);
            let icon = el.querySelector('i');
            if (icon) {
                icon.className = 'bi ' + (icones[i] || '');
                icon.style.opacity = i < stepsDicasPlanos.length ? 1 : 0;
                icon.style.fontSize = '1.25em';
            }
        }
        let line = el && el.nextElementSibling && el.nextElementSibling.classList.contains('step-line') ? el.nextElementSibling : null;
        if (line) line.style.display = (i < stepsDicasPlanos.length-1) ? '' : 'none';
    }
    // Atualiza conteúdo
    document.getElementById('stepContentDicasPlanos').innerHTML = `
        <h5 class='fw-bold mb-3 text-primary'>${stepsDicasPlanos[idx].title}</h5>
        <div style='font-size:1.13em;'>${stepsDicasPlanos[idx].html}</div>
    `;
    // Botões
    document.getElementById('btnStepAnteriorPlanos').style.display = idx === 0 ? 'none' : '';
    document.getElementById('btnStepProximoPlanos').innerHTML = idx === stepsDicasPlanos.length-1 ? 'Fechar <i class="bi bi-x"></i>' : 'Próximo <i class="bi bi-arrow-right"></i>';
}
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('btnDicasPlanos').onclick = function(e) {
        if (e) e.stopPropagation();
        document.getElementById('modalDicasPlanos').style.display = 'block';
        mostrarStepDicasPlanos(0);
    };
    document.getElementById('btnStepAnteriorPlanos').onclick = function() {
        if (stepAtualPlanos > 0) mostrarStepDicasPlanos(stepAtualPlanos-1);
    };
    document.getElementById('btnStepProximoPlanos').onclick = function() {
        if (stepAtualPlanos < stepsDicasPlanos.length-1) mostrarStepDicasPlanos(stepAtualPlanos+1);
        else fecharModalDicasPlanos();
    };
});
function fecharModalDicasPlanos() {
    document.getElementById('modalDicasPlanos').style.display = 'none';
}
    </script>
</div>
