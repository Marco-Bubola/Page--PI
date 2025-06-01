
<div style="width:100%; background:#007bff; padding:10px 0; text-align:right; display:flex; align-items:center;">
    <img src="../assets/img/LOGO_PAGE.png" alt="Logo PAGE" style="height:40px; margin-left:20px; margin-right:auto;">
    <?php if (isset($_SESSION['usuario_tipo']) && ($_SESSION['usuario_tipo'] === 'admin' || $_SESSION['usuario_tipo'] === 'coordenador')): ?>
        <a href="gerenciar_usuarios.php" style="margin-right:20px; color:#fff; text-decoration:none; font-weight:bold; font-size:16px; background:#198754; padding:8px 18px; border-radius:5px;">Gerenciar Usuários</a>
        <a href="home_coordenador.php" style="margin-right:20px; color:#fff; text-decoration:none; font-weight:bold; font-size:16px; background:#0d6efd; padding:8px 18px; border-radius:5px;">Home Coordenador</a>
    <?php endif; ?>
    <?php if (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'professor'): ?>
        <a href="home_professor.php" style="margin-right:20px; color:#fff; text-decoration:none; font-weight:bold; font-size:16px; background:#0d6efd; padding:8px 18px; border-radius:5px;">Home Professor</a>
    <?php endif; ?>
    <a href="logout.php" style="margin-right:30px; color:#fff; text-decoration:none; font-weight:bold; font-size:16px; background:#dc3545; padding:8px 18px; border-radius:5px;">Sair</a>
</div> 