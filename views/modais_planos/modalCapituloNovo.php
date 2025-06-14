<!-- Modal de Criar/Editar Capítulo (novo estilo) extraído de planos.php -->
<div id="modalCapituloNovo"
    style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);">
    <div
        style="background:#fff;padding:0;border-radius:18px;max-width:700px;width:95vw;margin:60px auto;position:relative;box-shadow:0 8px 32px rgba(0,0,0,0.18);overflow:hidden;">
        <div
            style="background:linear-gradient(90deg,#0d6efd 60%,#4f8cff 100%);padding:18px 32px 14px 32px;display:flex;align-items:center;gap:12px;">
            <i class="bi bi-journal-text text-white" style="font-size:2rem;"></i>
            <h4 id="tituloModalCapituloNovo" class="mb-0 text-white">Adicionar Capítulo</h4>
            <span onclick="fecharModalCapituloNovo()"
                style="position:absolute;top:14px;right:22px;font-size:28px;cursor:pointer;color:#fff;opacity:0.8;">&times;</span>
        </div>
        <div style="padding:30px 32px 18px 32px;">
            <form id="formCapituloNovo" action="../controllers/criar_capitulo_ajax.php" method="POST">
                <input type="hidden" name="plano_id" id="plano_id_capitulo_novo">
                <input type="hidden" name="id_capitulo" id="id_capitulo_novo">
                <input type="hidden" name="status" id="status_capitulo_novo" value="em_andamento">
                <div class="input-group mb-2">
                    <span class="input-group-text bg-white"><i class="bi bi-type-bold"></i></span>
                    <input type="text" name="titulo" id="titulo_capitulo_novo" placeholder="Título do capítulo"
                        required class="form-control">
                </div>
                <div class="mb-2">
                    <label>Descrição do capítulo:</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-card-text"></i></span>
                        <textarea name="descricao" id="descricao_capitulo_novo" placeholder="Descrição do capítulo"
                            class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </form>
            <div class="d-flex justify-content-end gap-2 pt-3 border-top mt-4" style="background:transparent;">
                <button type="button" class="btn btn-secondary d-flex align-items-center gap-1"
                    onclick="fecharModalCapituloNovo()"><i class="bi bi-x-circle"></i> Cancelar</button>
                <button type="submit" class="btn btn-primary d-flex align-items-center gap-1"
                    id="btnSalvarCapituloNovo" form="formCapituloNovo"><i class="bi bi-check-circle"></i>
                    Salvar</button>
            </div>
        </div>
    </div>
</div>
