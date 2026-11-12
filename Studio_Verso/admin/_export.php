<?php
/**
 * Exportação de dados: CSV e XLSX (sem depender de bibliotecas externas;
 * o XLSX é montado "na mão" com a extensão ZipArchive do PHP).
 *
 * Cada "planilha" é um array:
 *   ['nome' => 'Pedidos', 'cabecalho' => [...], 'tipos' => ['int','data','texto','dinheiro',...], 'linhas' => [[...], ...]]
 * Tipos aceitos: texto | int | dinheiro | data
 * Numa linha, um valor no formato ['f' => 'SUM(C2:C9)'] vira uma fórmula do Excel.
 */

/* ---------------- CSV ---------------- */
function exportarCsv(string $arquivo, array $planilha): never {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $arquivo . '.csv"');
    header('Cache-Control: no-store');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM: faz o Excel reconhecer acentos (UTF-8)

    $protege = function ($v) {
        // Evita "injeção de fórmula": texto que começa com = + - @ viraria fórmula no Excel.
        if (is_string($v) && $v !== '' && strpbrk($v[0], "=+-@\t\r") !== false) return "'" . $v;
        return $v;
    };
    // Separador ";" e vírgula decimal = padrão que o Excel em português abre direto.
    fputcsv($out, $planilha['cabecalho'], ';');
    foreach ($planilha['linhas'] as $linha) {
        $saida = [];
        foreach ($linha as $i => $v) {
            if (is_array($v)) { $v = ''; }
            $tipo = $planilha['tipos'][$i] ?? 'texto';
            if ($tipo === 'dinheiro' && $v !== null && $v !== '') $v = number_format((float)$v, 2, ',', '');
            elseif ($tipo === 'data' && $v) $v = date('d/m/Y H:i', strtotime((string)$v));
            else $v = $protege($v);
            $saida[] = $v;
        }
        fputcsv($out, $saida, ';');
    }
    fclose($out);
    exit;
}

/* ---------------- XLSX ---------------- */
function xlsxColuna(int $i): string { // 0 -> A, 25 -> Z, 26 -> AA
    $s = '';
    for ($i++; $i > 0; $i = intdiv($i - 1, 26)) $s = chr(65 + ($i - 1) % 26) . $s;
    return $s;
}
function xlsxTexto(string $s): string {
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $s) ?? '';
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function xlsxPlanilhaXml(array $pl): string {
    // Estilos (índices em styles.xml): 0 padrão | 1 cabeçalho | 2 dinheiro | 3 data/hora | 4 inteiro | 5 negrito
    $estilo = ['texto' => 0, 'dinheiro' => 2, 'data' => 3, 'int' => 4];
    $n = count($pl['cabecalho']);

    // largura das colunas conforme o conteúdo
    $larg = [];
    foreach ($pl['cabecalho'] as $i => $h) $larg[$i] = mb_strlen((string)$h) + 3;
    foreach ($pl['linhas'] as $linha) {
        foreach ($linha as $i => $v) {
            if (is_array($v)) continue;
            $tam = ($pl['tipos'][$i] ?? 'texto') === 'texto' ? mb_strlen((string)$v) + 2 : 16;
            if ($tam > ($larg[$i] ?? 0)) $larg[$i] = $tam;
        }
    }
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
        . '<cols>';
    for ($i = 0; $i < $n; $i++) {
        $xml .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . min(60, max(10, $larg[$i] ?? 12)) . '" customWidth="1"/>';
    }
    $xml .= '</cols><sheetData>';

    // cabeçalho
    $xml .= '<row r="1">';
    foreach ($pl['cabecalho'] as $i => $h) {
        $xml .= '<c r="' . xlsxColuna($i) . '1" s="1" t="inlineStr"><is><t>' . xlsxTexto((string)$h) . '</t></is></c>';
    }
    $xml .= '</row>';

    $r = 2;
    foreach ($pl['linhas'] as $linha) {
        $xml .= '<row r="' . $r . '">';
        foreach ($linha as $i => $v) {
            $ref = xlsxColuna($i) . $r;
            $tipo = $pl['tipos'][$i] ?? 'texto';
            $s = $estilo[$tipo] ?? 0;
            if (is_array($v) && isset($v['f'])) {
                $xml .= '<c r="' . $ref . '" s="' . ($tipo === 'texto' ? 5 : $s) . '"><f>' . xlsxTexto($v['f']) . '</f></c>';
            } elseif ($v === null || $v === '') {
                continue;
            } elseif ($tipo === 'dinheiro' || $tipo === 'int') {
                $xml .= '<c r="' . $ref . '" s="' . $s . '"><v>' . (0 + $v) . '</v></c>';
            } elseif ($tipo === 'data') {
                $serial = strtotime((string)$v) / 86400 + 25569; // data do Excel
                $xml .= '<c r="' . $ref . '" s="' . $s . '"><v>' . round($serial, 6) . '</v></c>';
            } else {
                $xml .= '<c r="' . $ref . '" s="' . $s . '" t="inlineStr"><is><t xml:space="preserve">' . xlsxTexto((string)$v) . '</t></is></c>';
            }
        }
        $xml .= '</row>';
        $r++;
    }
    $xml .= '</sheetData>';
    if ($r > 2) {
        $xml .= '<autoFilter ref="A1:' . xlsxColuna($n - 1) . ($r - 1) . '"/>';
    }
    return $xml . '</worksheet>';
}

function exportarXlsx(string $arquivo, array $planilhas): never {
    if (!class_exists('ZipArchive')) {
        http_response_code(500);
        die('A extensão "zip" do PHP não está ativa neste servidor, então não dá pra gerar .xlsx. Use a exportação CSV.');
    }
    $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
    $zip = new ZipArchive();
    $zip->open($tmp, ZipArchive::OVERWRITE);

    $tipos = '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
           . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
    $abas = ''; $rels = '';
    foreach ($planilhas as $i => $pl) {
        $id = $i + 1;
        $tipos .= '<Override PartName="/xl/worksheets/sheet' . $id . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        $nome = mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/u', ' ', $pl['nome']), 0, 31);
        $abas .= '<sheet name="' . xlsxTexto($nome) . '" sheetId="' . $id . '" r:id="rId' . $id . '"/>';
        $rels .= '<Relationship Id="rId' . $id . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $id . '.xml"/>';
        $zip->addFromString('xl/worksheets/sheet' . $id . '.xml', xlsxPlanilhaXml($pl));
    }
    $rels .= '<Relationship Id="rIdEstilos" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>' . $tipos . '</Types>');
    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
    $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheets>' . $abas . '</sheets></workbook>');
    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels . '</Relationships>');
    $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<numFmts count="2"><numFmt numFmtId="164" formatCode="&quot;R$&quot;\ #,##0.00"/><numFmt numFmtId="165" formatCode="dd/mm/yyyy\ hh:mm"/></numFmts>'
        . '<fonts count="3"><font><sz val="11"/><name val="Calibri"/></font>'
        . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
        . '<font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
        . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FF7B4FBF"/><bgColor indexed="64"/></patternFill></fill></fills>'
        . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="6">'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
        . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
        . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
        . '<xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
        . '<xf numFmtId="1" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
        . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
        . '</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>');
    $zip->close();

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $arquivo . '.xlsx"');
    header('Content-Length: ' . filesize($tmp));
    header('Cache-Control: no-store');
    readfile($tmp);
    unlink($tmp);
    exit;
}
