<?php
// Modelo do arquivo de segredos. Copie para segredos.php (ou, melhor ainda, para um nível ACIMA da
// pasta pública, com o nome studioverso_segredos.php) e preencha. NUNCA envie esse arquivo a ninguém.
return [
    'DB_HOST' => 'localhost',
    'DB_NAME' => '',
    'DB_USER' => '',
    'DB_PASS' => '',

    // ----- Pix manual (sem API) -----
    // Sua chave Pix: e-mail, CPF/CNPJ (só números), celular (+5585999999999) ou chave aleatória.
    'PIX_CHAVE' => '',
    // Nome e cidade que aparecem para o cliente na hora de pagar (sem precisar de acento)
    'PIX_NOME' => 'Studio Verso',
    'PIX_CIDADE' => 'Fortaleza',
    // WhatsApp que recebe o comprovante: DDI + DDD + número, só dígitos
    'WHATSAPP_LOJA' => '5585999999999',
];
