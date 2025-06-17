<div class="modal fade" id="modalAula" tabindex="-1" aria-labelledby="modalAulaLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width:1200px; min-width:900px; min-height:700px;">
        <form id="formAula" class="needs-validation" novalidate>
            <div class="modal-content" style="border-radius: 18px;">
                <div class="modal-header bg-primary text-white" style="border-radius: 18px 18px 0 0;">
                    <div class="d-flex align-items-center gap-3 w-100">
                        <span class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center"
                            style="width:48px;height:48px;font-size:2rem;box-shadow:0 2px 8px #fff3;">
                            <i class="fa-solid fa-chalkboard-user"></i>
                        </span>
                        <div>
                            <h5 class="modal-title fw-bold mb-0" id="modalAulaLabel">Registrar Aula</h5>
                            <div class="small text-white-50">
                                <span id="modalDisciplinaNome" class="fw-bold"></span>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <!-- Stepper visual estilizado dentro de um card -->
                <div class="card shadow-sm mx-4 mt-3 mb-0" style="border-radius: 16px;">
                    <div class="card-body py-3 px-2">
                        <div id="modal-progress-steps" class="d-flex align-items-center justify-content-center gap-0" style="user-select:none;">
                            <div class="stepper-step text-center flex-fill">
                                <div id="progress-step-1" class="stepper-circle bg-warning text-dark border border-warning mx-auto" style="width:48px;height:48px;display:flex;align-items:center;justify-content:center;font-size:1.6em;font-weight:bold;transition:.2s;">
                                    <i class="fa-solid fa-list-check"></i>
                                </div>
                                <div id="progress-label-1" class="fw-bold text-warning mt-2" style="font-size:1.08em;">Seleção de tópicos</div>
                            </div>
                            <div class="stepper-line flex-fill" id="progress-line" style="height:5px;min-width:60px;background:#ffc107;transition:.2s;margin:0 10px;border-radius:3px;"></div>
                            <div class="stepper-step text-center flex-fill">
                                <div id="progress-step-2" class="stepper-circle bg-light text-secondary border border-secondary mx-auto" style="width:48px;height:48px;display:flex;align-items:center;justify-content:center;font-size:1.6em;font-weight:bold;transition:.2s;">
                                    <i class="fa-solid fa-calendar-day"></i>
                                </div>
                                <div id="progress-label-2" class="fw-bold text-secondary mt-2" style="font-size:1.08em;">Data, comentário e tópicos personalizados</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-body" style="background: #f8f9fa; overflow-x: hidden;">
                    <input type="hidden" name="disciplina_id" id="aula_disciplina_id">
                    <input type="hidden" name="turma_id" value="<?= $turma_id ?>">

                    <!-- Step 1: Capítulos e tópicos -->
                    <div id="modal-step-1">
                        <div id="stepper_capitulos_modal"></div>
                        <div class="text-center mt-2 mb-4">
                            <span id="nome_capitulo_modal" class="fw-bold" style="font-size:1.25em;color:#0d6efd;"></span>
                        </div>
                        <div id="topicos_aula_box" style="max-height:400px;overflow-y:auto;overflow-x:hidden;"></div>
                        <div class="invalid-feedback text-center mt-2">
                            Selecione pelo menos um tópico para a aula.
                        </div>
                    </div>

                    <!-- Step 2: Data, comentário e tópicos personalizados -->
                    <div id="modal-step-2" style="display:none;">
                        <div class="row g-4">
                            <div class="col-md-7">
                                <div class="card border-2 border-primary shadow-sm h-100" style="border-radius:14px;">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-4">
                                            <span class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2"
                                                style="width:44px;height:44px;font-size:1.7rem;">
                                                <i class="fa-solid fa-calendar-day"></i>
                                            </span>
                                            <label for="aula_data" class="form-label fw-semibold text-primary mb-0 ms-2 me-3">Data da aula</label>
                                            <input type="date" name="data" id="aula_data" class="form-control" required style="max-width:220px;">
                                        </div>
                                        <div class="invalid-feedback text-center mb-3">Informe a data da aula.</div>
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-2"
                                                style="width:44px;height:44px;font-size:1.7rem;">
                                                <i class="fa-solid fa-comment-dots"></i>
                                            </span>
                                            <label for="aula_comentario" class="form-label fw-semibold text-success mb-0">Comentário</label>
                                        </div>
                                        <textarea name="comentario" id="aula_comentario" class="form-control"
                                            placeholder="Observações sobre a aula"
                                            style="resize:none;min-height:200px;max-width:100%;" maxlength="2000"></textarea>
                                        <div class="invalid-feedback">
                                            O comentário não pode ter mais de 2000 caracteres.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="card border-2 border-warning shadow-sm h-100" style="border-radius:14px;">
                                    <div class="card-body d-flex flex-column align-items-center">
                                        <span class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center mb-2"
                                            style="width:44px;height:44px;font-size:1.7rem;">
                                            <i class="fa-solid fa-lightbulb"></i>
                                        </span>
                                        <label class="form-label fw-semibold text-warning mb-2">Tópicos personalizados</label>
                                        <div id="topicos_personalizados_box" style="width:100%;max-height:180px;overflow-y:auto;"></div>
                                        <button type="button" class="btn btn-outline-warning btn-sm mt-2"
                                            onclick="adicionarTopicoPersonalizado()">
                                            <i class="fa-solid fa-plus me-1"></i> Adicionar tópico
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light justify-content-center" style="border-radius: 0 0 18px 18px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i
                            class="fa-solid fa-xmark me-1"></i>Cancelar</button>
                    <button type="button" class="btn btn-outline-primary" id="btn-modal-prev" style="display:none;"><i class="fa-solid fa-arrow-left"></i> Voltar</button>
                    <button type="button" class="btn btn-primary" id="btn-modal-next"><i class="fa-solid fa-arrow-right"></i> Próximo</button>
                    <button type="submit" class="btn btn-success" id="btn-modal-save" style="display:none;"><i class="fa-solid fa-floppy-disk me-1"></i>Salvar Aula</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
// Stepper dos capítulos no modal
function renderStepperCapitulos(plano, capitulos, currentStep) {
    if (!capitulos || capitulos.length === 0) {
        document.getElementById('stepper_capitulos_modal').innerHTML = '';
        document.getElementById('nome_capitulo_modal').innerText = '';
        return;
    }
    let html = '<div class="d-flex flex-row align-items-center justify-content-center gap-0 mb-2" style="gap:0!important;">';
    html += '<button type="button" class="btn btn-outline-primary me-2" id="modal-stepper-prev" style="min-width:70px;"><i class="fa fa-arrow-left"></i></button>';
    html += '<div class="d-flex flex-row align-items-center gap-0">';
    capitulos.forEach((cap, idx) => {
        // Step circle
        const isActive = idx === currentStep;
        const isDone = cap.status === 'concluido';
        let circleClass = '';
        let iconClass = 'fa-book';
        let iconColor = '';
        let bgColor = '';
        if (isDone) {
            circleClass = 'border-3 border-success bg-success text-white';
            iconClass = 'fa-circle-check';
            iconColor = 'text-white';
            bgColor = '#198754';
        } else if (idx < currentStep) {
            circleClass = 'border-3 border-primary bg-primary text-white';
            iconClass = 'fa-book';
            iconColor = 'text-white';
            bgColor = '#0d6efd';
        } else if (isActive) {
            circleClass = 'border-3 border-primary shadow bg-primary text-white';
            iconClass = 'fa-book';
            iconColor = 'text-white';
            bgColor = '#0d6efd';
        } else {
            circleClass = 'border border-secondary bg-light text-secondary';
            iconClass = 'fa-book';
            iconColor = 'text-secondary';
            bgColor = '#dee2e6';
        }
        html += `<div class="d-flex flex-column align-items-center" style="min-width:60px;">
            <div class="wizard-step-circle position-relative ${circleClass}" data-step="${idx}" style="width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.3rem;font-weight:bold;cursor:pointer;transition:box-shadow .2s;box-shadow:0 2px 8px #0001;background:${bgColor};">
                <i class="fa-solid ${iconClass} ${iconColor}"></i>
            </div>
            <div class="small mt-1 text-center" style="font-size:0.98em;color:${isDone ? '#198754' : (isActive || idx < currentStep ? '#0d6efd' : '#888')};max-width:80px;white-space:normal;word-break:break-word;">
                ${cap.titulo}
            </div>
        </div>`;
        // Progress bar between steps
        if (idx < capitulos.length - 1) {
            // Verde se o capítulo atual está concluído, azul se já passou ou está no step, cinza se futuro
            let barColor = '#dee2e6';
            if (cap.status === 'concluido') {
                barColor = '#198754';
            } else if (idx < currentStep) {
                // Se o anterior está concluído, verde, senão azul
                barColor = capitulos[idx].status === 'concluido' ? '#198754' : '#0d6efd';
            } else if (idx === currentStep) {
                barColor = '#0d6efd';
            }
            html += `<div style="height:6px;width:48px;background:${barColor};border-radius:3px;margin:0 2px;transition:.2s;"></div>`;
        }
    });
    html += '</div>';
    html += '<button type="button" class="btn btn-outline-primary ms-2" id="modal-stepper-next" style="min-width:70px;"><i class="fa fa-arrow-right"></i></button>';
    html += '</div>';
    document.getElementById('stepper_capitulos_modal').innerHTML = html;
    // Nome do capítulo centralizado abaixo do stepper
    document.getElementById('nome_capitulo_modal').innerText = capitulos[currentStep].titulo;
}

// Stepper visual bonito para steps do modal
function setModalProgressStep(step) {
    const step1 = document.getElementById('progress-step-1');
    const step2 = document.getElementById('progress-step-2');
    const label1 = document.getElementById('progress-label-1');
    const label2 = document.getElementById('progress-label-2');
    const line = document.getElementById('progress-line');
    if (step === 1) {
        step1.className = "stepper-circle bg-warning text-dark border border-warning mx-auto";
        step2.className = "stepper-circle bg-light text-secondary border border-secondary mx-auto";
        label1.className = "fw-bold text-warning mt-2";
        label2.className = "fw-bold text-secondary mt-2";
        line.style.background = "#ffc107";
    } else {
        step1.className = "stepper-circle bg-success text-white border border-success mx-auto";
        step2.className = "stepper-circle bg-success text-white border border-success mx-auto";
        label1.className = "fw-bold text-success mt-2";
        label2.className = "fw-bold text-success mt-2";
        line.style.background = "#28a745";
    }
}

// Função para abrir o modal da aula
function abrirModalAula(disc_id, disc_nome) {
    document.getElementById('modalDisciplinaNome').innerText = disc_nome;
    document.getElementById('aula_disciplina_id').value = disc_id;
    const plano = planos[disc_id];
    if (plano && capitulos[plano.id]) {
        const capitulosArr = capitulos[plano.id];
        let currentStep = 0;
        // Mantém os tópicos selecionados entre steps (persistente durante o modal)
        // CORREÇÃO: manter topicosSelecionados fora das funções internas e no escopo do modal
        if (!window._topicosSelecionados) window._topicosSelecionados = {};
        let topicosSelecionados = window._topicosSelecionados;

        // Função para renderizar os tópicos do capítulo atual, mantendo os selecionados
        function renderCapituloStep(idx) {
            const cap = capitulosArr[idx];
            document.getElementById('nome_capitulo_modal').innerText = cap.titulo;
            let html = `<div class="row g-3">`;
            (topicos[cap.id] || []).forEach(top => {
                const topStatus = top.status;
                const topBg = topStatus === 'concluido' ? 'bg-success-subtle' : 'bg-light';
                const topIcon = topStatus === 'concluido' ? 'fa-circle-check text-success' : 'fa-circle text-primary';
                const topBorder = topStatus === 'concluido' ? 'border-success' : 'border-primary';
                // CORREÇÃO: checked deve ser true se topicosSelecionados[top.id] === true
                const checked = (topicosSelecionados[top.id] || topStatus === 'concluido') ? 'checked' : '';
                const disabled = topStatus === 'concluido' ? 'disabled' : '';
                html += `
                    <div class="col-12 col-md-6">
                        <div class="p-3 rounded ${topBg} ${topBorder} mb-2" style="border:2px solid; border-radius:12px; min-height:90px;">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="fa-solid ${topIcon}" style="font-size:1.3em;"></i>
                                <span class="fw-semibold" style="font-size:1.13em;">
                                    <i class="fa-solid fa-lightbulb text-warning me-1"></i>
                                    ${top.titulo}
                                </span>
                                ${topStatus === 'concluido'
                                    ? '<span class="badge bg-success ms-2"><i class="fa-solid fa-check"></i> Concluído</span>'
                                    : '<span class="badge bg-primary ms-2"><i class="fa-solid fa-hourglass-half"></i> Em andamento</span>'}
                            </div>
                            ${top.descricao ? `<div class="topico-desc"><i class="fa-solid fa-align-left me-1 text-secondary"></i> ${top.descricao.replace(/\n/g, '<br>')}</div>` : ''}
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="topicos[]" value="${top.id}" id="topico_${top.id}" ${checked} ${disabled}>
                                <label class="form-check-label" for="topico_${top.id}">Selecionar</label>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            document.getElementById('topicos_aula_box').innerHTML = html;

            // listeners para manter seleção ao trocar de step
            (topicos[cap.id] || []).forEach(top => {
                if (top.status === 'concluido') return;
                const el = document.getElementById('topico_' + top.id);
                if (el) {
                    el.onchange = function() {
                        if (el.checked) {
                            topicosSelecionados[top.id] = true;
                            console.log('Selecionado:', top.id, top.titulo);
                        } else {
                            delete topicosSelecionados[top.id];
                            console.log('Desmarcado:', top.id, top.titulo);
                        }
                        // LOG: Mostra o objeto de seleção ao clicar
                        console.log('topicosSelecionados (onchange):', JSON.stringify(topicosSelecionados));
                    };
                    // Garante que o estado visual do checkbox reflete o objeto topicosSelecionados
                    el.checked = !!topicosSelecionados[top.id];
                }
            });

            // LOG: Mostra todos os tópicos selecionados após renderizar
            console.log('Tópicos selecionados após renderizar:', Object.keys(topicosSelecionados));
        }

        // Inicializa stepper e renderização
        renderStepperCapitulos(plano, capitulosArr, currentStep);

        // Salva seleção antes de trocar de step e mantém seleção ao voltar
        function saveCurrentStepSelections(idx) {
            const cap = capitulosArr[idx];
            (topicos[cap.id] || []).forEach(top => {
                if (top.status === 'concluido') return;
                const el = document.getElementById('topico_' + top.id);
                if (el) {
                    topicosSelecionados[top.id] = !!el.checked;
                }
            });
            // LOG: Mostra o objeto de seleção ao salvar step
            console.log('topicosSelecionados (saveCurrentStepSelections):', JSON.stringify(topicosSelecionados));
        }

        renderCapituloStep(currentStep);

        setTimeout(() => {
            const steps = document.querySelectorAll('#stepper_capitulos_modal .wizard-step-circle');
            const btnPrev = document.getElementById('modal-stepper-prev');
            const btnNext = document.getElementById('modal-stepper-next');
            function updateStep(idx) {
                // Salva seleção do step atual antes de trocar
                saveCurrentStepSelections(currentStep);
                currentStep = idx;
                steps.forEach((el, i) => el.classList.toggle('border-3', i === idx));
                renderStepperCapitulos(plano, capitulosArr, idx);
                renderCapituloStep(idx);
                // LOG: Mostra tópicos selecionados ao trocar de step
                console.log('Tópicos selecionados ao trocar step:', Object.keys(topicosSelecionados));
                if (btnPrev) btnPrev.disabled = idx === 0;
                if (btnNext) btnNext.disabled = idx === steps.length - 1;
            }
            steps.forEach((el, idx) => {
                el.onclick = () => { updateStep(idx); };
            });
            if (btnPrev) btnPrev.onclick = () => { if (currentStep > 0) { updateStep(currentStep - 1); } };
            if (btnNext) btnNext.onclick = () => { if (currentStep < steps.length - 1) { updateStep(currentStep + 1); } };
            updateStep(currentStep);
        }, 100);
    } else {
        document.getElementById('stepper_capitulos_modal').innerHTML = '';
        document.getElementById('topicos_aula_box').innerHTML = '<div class="text-muted">Nenhum capítulo/tópico disponível.</div>';
        document.getElementById('nome_capitulo_modal').innerText = '';
    }
    new bootstrap.Modal(document.getElementById('modalAula')).show();

    // Navegação entre os steps do modal
    const btnPrev = document.getElementById('btn-modal-prev');
    const btnNext = document.getElementById('btn-modal-next');
    const btnSave = document.getElementById('btn-modal-save');
    let currentStepModal = 1;
    setModalProgressStep(currentStepModal);

    btnPrev.onclick = function() {
        if (currentStepModal > 1) {
            currentStepModal--;
            setModalProgressStep(currentStepModal);
            document.getElementById('modal-step-' + (currentStepModal + 1)).style.display = 'none';
            document.getElementById('modal-step-' + currentStepModal).style.display = 'block';
            btnNext.style.display = currentStepModal === 1 ? 'inline-block' : 'none';
            btnSave.style.display = currentStepModal === 2 ? 'inline-block' : 'none';
        }
    };

    btnNext.onclick = function() {
        if (currentStepModal < 2) {
            currentStepModal++;
            setModalProgressStep(currentStepModal);
            document.getElementById('modal-step-' + (currentStepModal - 1)).style.display = 'none';
            document.getElementById('modal-step-' + currentStepModal).style.display = 'block';
            btnPrev.style.display = currentStepModal === 1 ? 'none' : 'inline-block';
            btnSave.style.display = currentStepModal === 2 ? 'inline-block' : 'none';
        }
    };

    // Resetar formulário e estado do modal ao fechar
    document.getElementById('modalAula').addEventListener('hidden.bs.modal', function () {
        document.getElementById('formAula').reset();
        document.getElementById('stepper_capitulos_modal').innerHTML = '';
        document.getElementById('topicos_aula_box').innerHTML = '';
        document.getElementById('nome_capitulo_modal').innerText = '';
        currentStepModal = 1;
        setModalProgressStep(currentStepModal);
        btnPrev.style.display = 'none';
        btnNext.style.display = 'inline-block';
        btnSave.style.display = 'none';
    });
}

// Validação do formulário
document.getElementById('formAula').addEventListener('submit', function(event) {
    if (!this.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    // Validação adicional para tópicos selecionados
    const topicosSelecionados = document.querySelectorAll('input[name="topicos[]"]:checked');
    if (topicosSelecionados.length === 0) {
        event.preventDefault();
        event.stopPropagation();
        document.querySelector('#modal-step-1 .invalid-feedback').style.display = 'block';
        return;
    } else {
        document.querySelector('#modal-step-1 .invalid-feedback').style.display = 'none';
    }
    
    this.classList.add('was-validated');
});

// Validação em tempo real do comentário
document.getElementById('aula_comentario').addEventListener('input', function() {
    if (this.value.length > 2000) {
        this.setCustomValidity('O comentário não pode ter mais de 2000 caracteres.');
    } else {
        this.setCustomValidity('');
    }
});

// Validação da data
document.getElementById('aula_data').addEventListener('change', function() {
    const dataSelecionada = new Date(this.value);
    const hoje = new Date();
    
    if (dataSelecionada > hoje) {
        this.setCustomValidity('A data da aula não pode ser futura.');
    } else {
        this.setCustomValidity('');
    }
});

// Função para validar tópicos personalizados
function validarTopicoPersonalizado(input) {
    if (input.value.length < 3) {
        input.setCustomValidity('O tópico deve ter pelo menos 3 caracteres.');
    } else if (input.value.length > 100) {
        input.setCustomValidity('O tópico não pode ter mais de 100 caracteres.');
    } else {
        input.setCustomValidity('');
    }
}

// Modificar a função adicionarTopicoPersonalizado para incluir validação
function adicionarTopicoPersonalizado() {
    const box = document.getElementById('topicos_personalizados_box');
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <input type="text" class="form-control" placeholder="Novo tópico" required
            minlength="3" maxlength="100" oninput="validarTopicoPersonalizado(this)">
        <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">
            <i class="fa-solid fa-trash"></i>
        </button>
    `;
    box.appendChild(div);
}
</script>
<style>
/* Stepper visual extra */
.stepper-circle {
    box-shadow: 0 2px 8px #0001;
    transition: background .2s, color .2s, border .2s;
}
.stepper-line {
    transition: background .2s;
}
/* Personalização dos cards do step 2 */
#modal-step-2 .card {
    min-height: 260px;
}
#modal-step-2 label {
    font-size: 1.13em;
}
#modal-step-2 textarea {
    min-height: 200px;
    font-size: 1.12em;
}
#topicos_personalizados_box {
    max-height: 180px;
    overflow-y: auto;
}
</style>
</style>
