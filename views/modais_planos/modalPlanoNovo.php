<!-- Modal de Criar/Editar Plano (novo estilo) extraído de planos.php -->
<div id="modalPlanoNovo"
    style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);">
    <div
        style="background:#fff;padding:0;border-radius:18px;max-width:700px;width:95vw;margin:60px auto;position:relative;box-shadow:0 8px 32px rgba(0,0,0,0.18);overflow:hidden;">
        <div
            style="background:linear-gradient(90deg,#0d6efd 60%,#4f8cff 100%);padding:18px 32px 14px 32px;display:flex;align-items:center;gap:12px;">
            <i class="bi bi-journal-bookmark-fill text-white" style="font-size:2rem;"></i>
            <h4 id="tituloModalPlanoNovo" class="mb-0 text-white">Criar Plano de Aula</h4>
            <span onclick="fecharModalPlanoNovo()"
                style="position:absolute;top:14px;right:22px;font-size:28px;cursor:pointer;color:#fff;opacity:0.8;">&times;</span>
        </div>
        <div style="padding:30px 32px 18px 32px;">
            <form id="formPlanoNovo" action="../controllers/criar_plano_ajax.php" method="POST">
                <input type="hidden" name="id_plano" id="id_plano_novo">
                <?php if ($turma_id): ?>
                <input type="hidden" name="turma_id" id="turma_id_plano_novo" value="<?= $turma_id ?>">
                <input type="hidden" name="disciplina_id" id="disciplina_id_plano_novo" value="">
                <!-- O campo de nome da disciplina só para exibir -->
                <input type="text" id="disciplina_nome_plano_novo" class="form-control mb-2" value="" readonly
                    style="display:none;">
                <?php else: ?>
                <input type="hidden" name="disciplina_id" id="disciplina_id_plano_novo" value="">
                <?php endif; ?>
                <div class="input-group mb-2">
                    <span class="input-group-text bg-white"><i class="bi bi-type-bold"></i></span>
                    <input type="text" name="titulo" id="titulo_plano_novo" placeholder="Título do plano" required
                        class="form-control">
                </div>
                <input type="hidden" name="status" id="status_plano_novo" value="em_andamento">
                <div class="row mb-2">
                    <div class="col-md-6 mb-2 mb-md-0">
                        <label>Data início:</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-calendar-event"></i></span>
                            <input type="date" name="data_inicio" id="data_inicio_plano_novo" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label>Data fim:</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-calendar2-week"></i></span>
                            <input type="date" name="data_fim" id="data_fim_plano_novo" class="form-control">
                        </div>
                    </div>
                </div>
                <input type="hidden" name="redirect"
                    value="planos.php<?= $turma_id ? '?turma_id=' . $turma_id : '' ?>">
            </form>
            <div class="d-flex justify-content-end gap-2 pt-3 border-top mt-4" style="background:transparent;">
                <button type="button" class="btn btn-secondary d-flex align-items-center gap-1"
                    onclick="fecharModalPlanoNovo()"><i class="bi bi-x-circle"></i> Cancelar</button>
                <button type="submit" class="btn btn-primary d-flex align-items-center gap-1"
                    id="btnSalvarPlanoNovo" form="formPlanoNovo"><i class="bi bi-check-circle"></i>
                    Salvar</button>
            </div>
        </div>
    </div>
</div>
