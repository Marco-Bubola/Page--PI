<!-- Modal de Confirmar Exclusão de Plano extraído de planos.php -->
<div id="modalExcluirPlano"
    style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);">
    <div
        style="background:#fff;padding:0;border-radius:14px;max-width:400px;margin:100px auto;position:relative;box-shadow:0 8px 32px rgba(0,0,0,0.18);overflow:hidden;">
        <div
            style="background:linear-gradient(90deg,#dc3545 60%,#ff6f91 100%);padding:16px 24px 12px 24px;display:flex;align-items:center;gap:10px;">
            <i class="bi bi-trash-fill text-white" style="font-size:1.7rem;"></i>
            <h4 class="mb-0 text-white">Excluir Plano</h4>
            <span onclick="fecharModalExcluirPlano()"
                style="position:absolute;top:10px;right:18px;font-size:26px;cursor:pointer;color:#fff;opacity:0.8;">&times;</span>
        </div>
        <div style="padding:24px 24px 18px 24px;">
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" style="font-size:1.1em;">
                <i class="bi bi-exclamation-triangle-fill" style="font-size:1.5em;"></i>
                Atenção! Esta ação não poderá ser desfeita.
            </div>
            <form action="../controllers/excluir_plano_ajax.php" method="POST" id="formExcluirPlano">
                <input type="hidden" name="id_plano" id="excluir_id_plano">
                <input type="hidden" name="redirect" value="">
                <p id="excluir_nome_plano" style="margin:15px 0;"></p>
                <div class="d-flex justify-content-end gap-2 pt-2">
                    <button type="submit" class="btn btn-danger d-flex align-items-center gap-1"><i
                            class="bi bi-trash"></i> Confirmar Exclusão</button>
                    <button type="button" class="btn btn-secondary d-flex align-items-center gap-1"
                        onclick="fecharModalExcluirPlano()"><i class="bi bi-x-circle"></i> Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>
