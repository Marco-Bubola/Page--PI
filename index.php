<?php
session_start();
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_tipo'])) {
    header('Location: views/login.php');
    exit();
}
if ($_SESSION['usuario_tipo'] === 'professor') {
    header('Location: views/home_professor.php');
    exit();
} else {
    header('Location: views/home_coordenador.php');
    exit();
}
