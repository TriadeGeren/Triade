<?php
require __DIR__ . '/auth.php';
encerrar_sessao();
header('Cache-Control: no-store');
header('Location: ' . PAGINA_LOGIN);
exit;
