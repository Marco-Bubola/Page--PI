<!-- Toast de Notificação Bootstrap -->
<div aria-live="polite" aria-atomic="true" style="position: fixed; top: 20px; right: 20px; min-width: 300px; z-index: 9999;">
  <div id="toastNotificacao" class="toast align-items-center text-bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000">
    <div class="d-flex">
      <div class="toast-body" id="toastMensagem">
        <!-- Mensagem dinâmica aqui -->
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fechar"></button>
    </div>
  </div>
</div>
<script>
function mostrarNotificacao(mensagem, tipo = 'primary') {
  const toast = document.getElementById('toastNotificacao');
  const toastMensagem = document.getElementById('toastMensagem');
  toast.className = 'toast align-items-center text-bg-' + tipo + ' border-0';
  toastMensagem.innerHTML = mensagem;
  const bsToast = new bootstrap.Toast(toast);
  bsToast.show();
}
</script> 