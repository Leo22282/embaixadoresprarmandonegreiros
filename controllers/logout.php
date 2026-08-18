<?php
session_start();

unset($_SESSION['logado']);
unset($_SESSION['id_usuario']);
unset($_SESSION['login']);
unset($_SESSION['nome']);
unset($_SESSION['nivel']);
unset($_SESSION['id_pessoa']);
unset($_SESSION['tipo_pessoa']);

header('Location: ../index.php');
exit;
