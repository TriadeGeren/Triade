<?php
/**
 * Pix manual (sem API, sem taxa de intermediário).
 *
 * Gera o "Pix Copia e Cola" (padrão BR Code do Banco Central) já com o valor do pedido.
 * O cliente paga no app do banco e envia o comprovante pelo WhatsApp; você confirma o
 * pagamento em Admin > Pedidos (botão "Confirmar pagamento").
 *
 * Configure no segredos.php: PIX_CHAVE, PIX_NOME e PIX_CIDADE (veja segredos.exemplo.php).
 */

function pixConfigurado(): bool {
    return PIX_CHAVE !== '';
}

/** Deixa o texto como o Pix exige: sem acento, maiúsculo, só letras/números/espaço. */
function pixTexto(string $s, int $max): string {
    $s = strtr($s, [
        'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
        'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i','ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o',
        'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n',
        'Á'=>'A','À'=>'A','Ã'=>'A','Â'=>'A','Ä'=>'A','É'=>'E','È'=>'E','Ê'=>'E','Ë'=>'E',
        'Í'=>'I','Ì'=>'I','Î'=>'I','Ï'=>'I','Ó'=>'O','Ò'=>'O','Õ'=>'O','Ô'=>'O','Ö'=>'O',
        'Ú'=>'U','Ù'=>'U','Û'=>'U','Ü'=>'U','Ç'=>'C','Ñ'=>'N',
    ]);
    $s = strtoupper(preg_replace('/[^A-Za-z0-9 ]+/', '', $s));
    return trim(substr($s, 0, $max));
}

/** Monta um campo no formato ID + tamanho (2 dígitos) + valor. */
function pixCampo(string $id, string $valor): string {
    return $id . str_pad((string)strlen($valor), 2, '0', STR_PAD_LEFT) . $valor;
}

/** Verificador CRC16 (CCITT-FALSE) exigido no final do código Pix. */
function pixCrc16(string $dados): string {
    $crc = 0xFFFF;
    for ($i = 0, $n = strlen($dados); $i < $n; $i++) {
        $crc ^= ord($dados[$i]) << 8;
        for ($b = 0; $b < 8; $b++) {
            $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
            $crc &= 0xFFFF;
        }
    }
    return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}

/** Devolve o texto do "Pix Copia e Cola" (e do QR Code) para um valor. */
function pixPayload(string $chave, string $nome, string $cidade, float $valor, string $txid = ''): string {
    $nome = pixTexto($nome, 25) ?: 'LOJA';
    $cidade = pixTexto($cidade, 15) ?: 'BRASIL';
    $txid = substr(preg_replace('/[^A-Za-z0-9]/', '', $txid), 0, 25) ?: '***';

    $conta = pixCampo('00', 'br.gov.bcb.pix') . pixCampo('01', trim($chave));
    $codigo = '000201'
        . '010211'
        . pixCampo('26', $conta)
        . '52040000'
        . '5303986'
        . pixCampo('54', number_format($valor, 2, '.', ''))
        . '5802BR'
        . pixCampo('59', $nome)
        . pixCampo('60', $cidade)
        . pixCampo('62', pixCampo('05', $txid))
        . '6304';
    return $codigo . pixCrc16($codigo);
}
