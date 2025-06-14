<div id="modalDicasAulas" class="modal" tabindex="-1" aria-labelledby="modalDicasAulasLabel" aria-hidden="true" style="display:none;position:fixed;z-index:2100;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.45);">
    <div class="modal-dicas-content" style="max-width:760px;min-width:420px;width:98vw;">
        <div class="modal-dicas-header" style="background:linear-gradient(90deg,#0d6efd 60%,#4f8cff 100%);">
            <div class="modal-dicas-icone" style="background:#fff;color:#0d6efd;">
                <i class="bi bi-lightbulb-fill"></i>
            </div>
            <h4 class="mb-0 text-white">Dicas de Funcionamento - Registro de Aulas</h4>
            <span onclick="fecharModalDicasAulas()" class="modal-dicas-close" style="color:#fff;">&times;</span>
        </div>
        <div class="modal-dicas-body">
            <!-- Stepper -->
            <div id="stepperDicasAulas" class="mb-4">
                <div class="d-flex justify-content-center align-items-center gap-2 mb-3">
                    <span class="step-circle" id="stepCircleAulas1"><i class="bi"></i></span>
                    <span class="step-line"></span>
                    <span class="step-circle" id="stepCircleAulas2"><i class="bi"></i></span>
                    <span class="step-line"></span>
                    <span class="step-circle" id="stepCircleAulas3"><i class="bi"></i></span>
                    <span class="step-line"></span>
                    <span class="step-circle" id="stepCircleAulas4"><i class="bi"></i></span>
                    <span class="step-line"></span>
                    <span class="step-circle" id="stepCircleAulas5"><i class="bi"></i></span>
                </div>
            </div>
            <div id="stepContentDicasAulas">
                <!-- Conteúdo dos steps será preenchido via JS -->
            </div>
        </div>
        <div class="modal-dicas-footer">
            <button class="btn btn-outline-primary" id="btnStepAnteriorAulas" style="display:none;"><i class="bi bi-arrow-left"></i> Anterior</button>
            <button class="btn btn-outline-primary ms-3" id="btnStepProximoAulas">Próximo <i class="bi bi-arrow-right"></i></button>
        </div>
    </div>
    <style>
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
    #modalDicasAulas .step-circle {
        width: 32px; height: 32px; border-radius: 50%; background: #e3e9f7; color: #0d6efd; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.15em; border: 2px solid #b6c6e6;
        transition: background 0.2s, color 0.2s;
    }
    #modalDicasAulas .step-circle.active {
        background: #0d6efd; color: #fff; border-color: #0d6efd;
    }
    #modalDicasAulas .step-line {
        flex: 1 1 0; height: 3px; background: #b6c6e6;
    }
    #stepContentDicasAulas {
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
    .bg-dica-blue { background: #f8faff; }
    .bg-dica-green { background: #e6f7ec; }
    .bg-dica-yellow { background: #fffbe6; }
    .bg-dica-orange { background: #fff3e6; }
    .bg-dica-purple { background: #f3e6ff; }
    .dica-blue { color: #0d6efd; background: #e3e9f7; }
    .dica-green { color: #198754; background: #e6f7ec; }
    .dica-yellow { color: #ffc107; background: #fffbe6; }
    .dica-orange { color: #fd7e14; background: #fff3e6; }
    .dica-purple { color: #6f42c1; background: #f3e6ff; }
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
    }
    </style>
    <script>
    // Steps do modal de dicas para registro de aulas
    const stepsDicasAulas = [
        {
            title: 'Navegação pelas Disciplinas',
            html: `<div class="dica-step-card bg-dica-blue"><div class="dica-step-icone dica-blue"><i class="bi bi-book"></i></div><div>
                Use as <b>abas no topo</b> para alternar entre as disciplinas da turma.<br>
                <span class="text-primary">Cada aba mostra o plano de aula e o histórico daquela disciplina.</span>
            </div></div>`
        },
        {
            title: 'Registrar uma Nova Aula',
            html: `<div class="dica-step-card bg-dica-green"><div class="dica-step-icone dica-green"><i class="bi bi-plus-circle-fill"></i></div><div>
                Clique em <span class="badge bg-success text-white"><i class="bi bi-plus"></i> Registrar Aula</span> para abrir o modal de registro.<br>
                <span class="text-success">Você poderá marcar os tópicos ministrados e adicionar observações.</span>
            </div></div>`
        },
        {
            title: 'Seleção de Capítulos e Tópicos',
            html: `<div class="dica-step-card bg-dica-yellow"><div class="dica-step-icone dica-yellow"><i class="bi bi-list-check"></i></div><div>
                No modal, navegue entre os <b>capítulos</b> usando o stepper e marque os <b>tópicos ministrados</b>.<br>
                <span class="text-warning">Tópicos já concluídos aparecem marcados e não podem ser desmarcados.</span>
            </div></div>`
        },
        {
            title: 'Tópicos Personalizados e Comentários',
            html: `<div class="dica-step-card bg-dica-orange"><div class="dica-step-icone dica-orange"><i class="bi bi-lightbulb"></i></div><div>
                No segundo passo do modal, adicione <b>tópicos personalizados</b> se abordou conteúdos fora do plano.<br>
                <span class="text-orange">Também é possível informar a data da aula e adicionar comentários.</span>
            </div></div>`
        },
        {
            title: 'Histórico de Aulas',
            html: `<div class="dica-step-card bg-dica-purple"><div class="dica-step-icone dica-purple"><i class="bi bi-clock-history"></i></div><div>
                Consulte o <b>histórico das últimas aulas</b> na aba <span class="badge bg-secondary text-white">Último Registro</span>.<br>
                <span class="text-muted">Veja tópicos planejados, personalizados e comentários de cada aula registrada.</span>
            </div></div>`
        }
    ];
    let stepAtualAulas = 0;
    function mostrarStepDicasAulas(idx) {
        stepAtualAulas = idx;
        // Ícones para cada step (ordem deve bater com stepsDicasAulas)
        let icones = [
            'bi-book',
            'bi-plus-circle-fill',
            'bi-list-check',
            'bi-lightbulb',
            'bi-clock-history'
        ];
        for (let i = 0; i < 5; i++) {
            let el = document.getElementById('stepCircleAulas'+(i+1));
            if (el) {
                el.style.display = i < stepsDicasAulas.length ? '' : 'none';
                el.classList.toggle('active', i === idx);
                let icon = el.querySelector('i');
                if (icon) {
                    icon.className = 'bi ' + (icones[i] || '');
                    icon.style.opacity = i < stepsDicasAulas.length ? 1 : 0;
                    icon.style.fontSize = '1.25em';
                }
            }
            let line = el && el.nextElementSibling && el.nextElementSibling.classList.contains('step-line') ? el.nextElementSibling : null;
            if (line) line.style.display = (i < stepsDicasAulas.length-1) ? '' : 'none';
        }
        // Atualiza conteúdo
        document.getElementById('stepContentDicasAulas').innerHTML = `
            <h5 class='fw-bold mb-3 text-primary'>${stepsDicasAulas[idx].title}</h5>
            <div style='font-size:1.13em;'>${stepsDicasAulas[idx].html}</div>
        `;
        // Botões
        document.getElementById('btnStepAnteriorAulas').style.display = idx === 0 ? 'none' : '';
        document.getElementById('btnStepProximoAulas').innerHTML = idx === stepsDicasAulas.length-1 ? 'Fechar <i class="bi bi-x"></i>' : 'Próximo <i class="bi bi-arrow-right"></i>';
    }
    document.addEventListener('DOMContentLoaded', function() {
        // Substitui o modal Bootstrap pelo modal customizado
        document.getElementById('btnDicasPlanos').onclick = function(e) {
            if (e) e.stopPropagation();
            document.getElementById('modalDicasAulas').style.display = 'block';
            mostrarStepDicasAulas(0);
        };
        document.getElementById('btnStepAnteriorAulas').onclick = function() {
            if (stepAtualAulas > 0) mostrarStepDicasAulas(stepAtualAulas-1);
        };
        document.getElementById('btnStepProximoAulas').onclick = function() {
            if (stepAtualAulas < stepsDicasAulas.length-1) mostrarStepDicasAulas(stepAtualAulas+1);
            else fecharModalDicasAulas();
        };
    });
    function fecharModalDicasAulas() {
        document.getElementById('modalDicasAulas').style.display = 'none';
    }
    </script>
</div>
