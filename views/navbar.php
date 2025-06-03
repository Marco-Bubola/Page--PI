<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Detectar página atual
$pagina_atual = basename($_SERVER['PHP_SELF']);
?>
<style>
.navbar .nav-link {
    font-weight: 600;
    font-size: 1.08em;
    border-radius: 6px;
    margin-left: 4px;
    margin-right: 4px;
    padding: 8px 18px !important;
    transition: background 0.2s, color 0.2s;
}
.navbar .nav-link.active, .navbar .nav-link:hover {
    background: #fff !important;
    color: #0d6efd !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.07);
}
.navbar .nav-link.text-danger.active, .navbar .nav-link.text-danger:hover {
    background: #dc3545 !important;
    color: #fff !important;
}
</style>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary" style="min-height: 56px;">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="#">
      <img src="../assets/img/LOGO_PAGE.png" alt="Logo PAGE" style="height:40px; margin-right:10px;">
      PAGE
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNavDropdown">
      <ul class="navbar-nav ms-auto">
        <?php if (isset($_SESSION['usuario_tipo'])): ?>
          <?php if ($_SESSION['usuario_tipo'] === 'admin'): ?>
            <li class="nav-item">
              <a class="nav-link<?php if ($pagina_atual == 'gerenciar_usuarios.php') echo ' active'; ?>" href="gerenciar_usuarios.php">Gerenciar Usuários</a>
            </li>
            <?php if ($pagina_atual != 'home_coordenador.php'): ?>
            <li class="nav-item">
              <a class="nav-link<?php if ($pagina_atual == 'home_coordenador.php') echo ' active'; ?>" href="home_coordenador.php">Home</a>
            </li>
            <?php endif; ?>
          <?php elseif ($_SESSION['usuario_tipo'] === 'coordenador'): ?>
            <?php if ($pagina_atual != 'home_coordenador.php'): ?>
            <li class="nav-item">
              <a class="nav-link<?php if ($pagina_atual == 'home_coordenador.php') echo ' active'; ?>" href="home_coordenador.php">Home</a>
            </li>
            <?php endif; ?>
          <?php elseif ($_SESSION['usuario_tipo'] === 'professor'): ?>
            <?php if ($pagina_atual != 'home_professor.php'): ?>
            <li class="nav-item">
              <a class="nav-link<?php if ($pagina_atual == 'home_professor.php') echo ' active'; ?>" href="home_professor.php">Home Professor</a>
            </li>
            <?php endif; ?>
          <?php endif; ?>
          <li class="nav-item">
            <a class="nav-link<?php if ($pagina_atual == 'planos.php') echo ' active'; ?>" href="planos.php">Planos</a>
          </li>
          <li class="nav-item">
            <a class="nav-link<?php if ($pagina_atual == 'disciplinas.php') echo ' active'; ?>" href="disciplinas.php">Disciplinas</a>
          </li>
          <li class="nav-item">
            <a class="nav-link<?php if ($pagina_atual == 'turmas.php') echo ' active'; ?>" href="turmas.php">Turmas</a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-danger fw-bold<?php if ($pagina_atual == 'logout.php') echo ' active'; ?>" href="logout.php">Sair</a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link<?php if ($pagina_atual == 'login.php') echo ' active'; ?>" href="login.php">Entrar</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
