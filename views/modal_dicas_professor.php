<!-- Modal de Dicas do Professor -->
<div id="modalDicasProfessor" style="display:none;position:fixed;z-index:2100;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.45);">
    <div class="modal-dicas-content">
        <div class="modal-dicas-header">
            <div class="modal-dicas-icone">
                <i class="bi bi-lightbulb-fill"></i>
            </div>
            <h4 class="mb-0 text-white">Dicas do Professor</h4>
            <span onclick="fecharModalDicasProfessor()" class="modal-dicas-close">&times;</span>
        </div>
        <div class="modal-dicas-body">
            <!-- Stepper -->
            <div id="stepperDicasProfessor" class="mb-4">
                <div class="d-flex justify-content-center align-items-center gap-2 mb-3">
                    <span class="step-circle" id="stepCircleProfessor1"><i class="bi"></i></span>
                    <span class="step-line"></span>
                    <span class="step-circle" id="stepCircleProfessor2"><i class="bi"></i></span>
                    <span class="step-line"></span>
                    <span class="step-circle" id="stepCircleProfessor3"><i class="bi"></i></span>
                </div>
            </div>
            <div id="stepContentDicasProfessor">
                <!-- Conteúdo dos steps será preenchido via JS -->
            </div>
        </div>
        <div class="modal-dicas-footer">
            <button class="btn btn-outline-primary" id="btnStepAnteriorProfessor" style="display:none;"><i class="bi bi-arrow-left"></i> Anterior</button>
            <button class="btn btn-outline-primary ms-3" id="btnStepProximoProfessor">Próximo <i class="bi bi-arrow-right"></i></button>
        </div>
    </div>
    <style>
        .modal-dicas-content { background: #fff; border-radius: 22px; max-width: 760px; width: 98vw; min-width: 420px; min-height: 420px; max-height: 900px; margin: 60px auto; position: relative; box-shadow: 0 8px 32px rgba(0,0,0,0.18); overflow: hidden; display: flex; flex-direction: column; }
        .modal-dicas-header { background: linear-gradient(90deg,#0d6efd 60%,#4f8cff 100%); padding: 28px 36px 20px 36px; display: flex; align-items: center; justify-content: center; gap: 18px; position: relative; text-align: center; }
        .modal-dicas-icone { background: #fff; color: #0d6efd; border-radius: 50%; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; box-shadow: 0 2px 8px #0d6efd33; }
        .modal-dicas-header h4 { color: #fff; font-weight: bold; font-size: 1.55em; margin-bottom: 0; flex: 1 1 auto; text-align: center; }
        .modal-dicas-close { position: absolute; top: 18px; right: 28px; font-size: 32px; cursor: pointer; color: #fff; opacity: 0.8; transition: opacity 0.2s; }
        .modal-dicas-close:hover { opacity: 1; }
        .modal-dicas-body { padding: 38px 32px 28px 32px; flex: 1 1 auto; display: flex; flex-direction: column; justify-content: flex-start; }
        .modal-dicas-footer { width: 100%; display: flex; justify-content: center; align-items: center; gap: 18px; padding: 22px 0 18px 0; background: #f8faff; border-top: 1.5px solid #e3e9f7; border-radius: 0 0 22px 22px; min-height: 70px; }
        .modal-dicas-footer .btn { min-width: 120px; font-size: 1.08em; font-weight: 500; }
        #modalDicasProfessor .step-circle { width: 32px; height: 32px; border-radius: 50%; background: #e3e9f7; color: #0d6efd; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.15em; border: 2px solid #b6c6e6; transition: background 0.2s, color 0.2s; }
        #modalDicasProfessor .step-circle.active { background: #0d6efd; color: #fff; border-color: #0d6efd; }
        #modalDicasProfessor .step-line { flex: 1 1 0; height: 3px; background: #b6c6e6; }
        #stepContentDicasProfessor { min-height: 110px; max-height: 180px; margin-bottom: 0.5em; }
        .dica-step-card { display: flex; align-items: flex-start; gap: 18px; border-radius: 16px; padding: 18px 18px 18px 18px; margin-bottom: 0.5em; box-shadow: 0 2px 12px #e3e9f7; font-size: 1.13em; font-weight: 500; background: #f8faff; }
        .dica-step-icone { font-size: 2.3em; width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-top: 2px; box-shadow: 0 2px 8px #e3e9f7; }
        .dica-blue { color: #0d6efd; background: #e3e9f7; }
        .dica-green { color: #198754; background: #e6f7ec; }
        .dica-yellow { color: #ffc107; background: #fffbe6; }
        .dica-purple { color: #6f42c1; background: #f3e6ff; }
        @media (max-width: 600px) { .modal-dicas-content { max-width: 98vw; min-width: 0; padding: 0; } .modal-dicas-header, .modal-dicas-body { padding-left: 12px; padding-right: 12px; } }
    </style>
</div>
<script>
const stepsDicasProfessor = [
    {
        title: 'Minhas Turmas',
        html: `<div class='dica-step-card bg-dica-blue'><div class='dica-step-icone dica-blue'><i class='bi bi-collection'></i></div><div>Visualize todas as turmas em que você está vinculado. Veja detalhes como ano letivo, turno, status e disciplinas associadas.</div></div>`
    },
    {
        title: 'Registrar Aulas',
        html: `<div class='dica-step-card bg-dica-green'><div class='dica-step-icone dica-green'><i class='bi bi-journal-plus'></i></div><div>Registre as aulas ministradas em cada turma. Informe data, conteúdo e observações para manter o histórico atualizado.</div></div>`
    },
    {
        title: 'Histórico e Planos',
        html: `<div class='dica-step-card bg-dica-purple'><div class='dica-step-icone dica-purple'><i class='bi bi-clock-history'></i></div><div>Acompanhe o histórico de aulas e consulte os planos de ensino de cada disciplina. Mantenha-se organizado e atualizado!</div></div>`
    }
];
let stepAtualProfessor = 0;
function mostrarStepDicasProfessor(idx) {
    stepAtualProfessor = idx;
    let totalSteps = stepsDicasProfessor.length;
    let icones = ['bi-collection','bi-journal-plus','bi-clock-history'];
    for (let i = 0; i < 3; i++) {
        let el = document.getElementById('stepCircleProfessor'+(i+1));
        if (el) {
            el.style.display = i < totalSteps ? '' : 'none';
            el.classList.toggle('active', i === idx);
            let icon = el.querySelector('i');
            if (icon) {
                icon.className = 'bi ' + (icones[i] || '');
                icon.style.opacity = i < totalSteps ? 1 : 0;
                icon.style.fontSize = '1.25em';
            }
        }
        let line = el && el.nextElementSibling && el.nextElementSibling.classList.contains('step-line') ? el.nextElementSibling : null;
        if (line) line.style.display = (i < totalSteps-1) ? '' : 'none';
    }
    document.getElementById('stepContentDicasProfessor').innerHTML = `
        <h5 class='fw-bold mb-3 text-primary'>${stepsDicasProfessor[idx].title}</h5>
        <div style='font-size:1.13em;'>${stepsDicasProfessor[idx].html}</div>
    `;
    document.getElementById('btnStepAnteriorProfessor').style.display = idx === 0 ? 'none' : '';
    document.getElementById('btnStepProximoProfessor').innerHTML = idx === stepsDicasProfessor.length-1 ? 'Fechar <i class="bi bi-x"></i>' : 'Próximo <i class="bi bi-arrow-right"></i>';
}
document.getElementById('btnStepAnteriorProfessor').onclick = function() {
    if (stepAtualProfessor > 0) mostrarStepDicasProfessor(stepAtualProfessor-1);
};
document.getElementById('btnStepProximoProfessor').onclick = function() {
    if (stepAtualProfessor < stepsDicasProfessor.length-1) mostrarStepDicasProfessor(stepAtualProfessor+1);
    else fecharModalDicasProfessor();
};
function abrirModalDicasProfessor() {
    document.getElementById('modalDicasProfessor').style.display = 'block';
    mostrarStepDicasProfessor(0);
}
function fecharModalDicasProfessor() {
    document.getElementById('modalDicasProfessor').style.display = 'none';
}
</script>
