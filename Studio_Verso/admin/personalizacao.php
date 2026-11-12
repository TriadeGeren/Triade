<?php
require __DIR__ . '/_boot.php';
require_once __DIR__ . '/../includes/personalizacao.php';
garantirTabelasPersonalizacao($conn);

const TIPOS_OPCAO = [
    'escolha'  => 'Escolher UMA opção (ex.: tamanho)',
    'multipla' => 'Marcar VÁRIAS opções (ex.: extras)',
    'texto'    => 'Campo de texto (ex.: nome bordado)',
];

/** Lê as linhas "Rótulo | 15,00" do campo de texto e devolve [[rotulo, acrescimo], ...]. */
function lerValores(string $texto): array {
    $saida = [];
    foreach (preg_split('/\r\n|\r|\n/', $texto) as $linha) {
        $linha = trim($linha);
        if ($linha === '') continue;
        $partes = array_map('trim', explode('|', $linha, 2));
        $rotulo = mb_substr($partes[0], 0, 80);
        $acr = isset($partes[1]) && $partes[1] !== '' ? lerPreco($partes[1]) : 0.0;
        if ($rotulo === '') continue;
        $saida[] = [$rotulo, max(0.0, $acr)];
    }
    return $saida;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfExigir();
    $acao = $_POST['acao'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($acao === 'salvar') {
        $nome = trim($_POST['nome'] ?? '');
        $tipo = isset(TIPOS_OPCAO[$_POST['tipo'] ?? '']) ? $_POST['tipo'] : 'escolha';
        $obrig = isset($_POST['obrigatorio']) ? 1 : 0;
        $ordem = (int)($_POST['ordem'] ?? 0);
        $acrTexto = $tipo === 'texto' ? max(0.0, lerPreco($_POST['acrescimo'] ?? '0')) : 0.0;
        $valores = $tipo === 'texto' ? [] : lerValores($_POST['valores'] ?? '');

        [$escopo, $escopoId] = array_pad(explode(':', $_POST['escopo'] ?? '', 2), 2, '');
        $escopoId = (int)$escopoId;
        $produtoId = $escopo === 'prod' ? $escopoId : null;
        $categoriaId = $escopo === 'cat' ? $escopoId : null;

        $erro = null;
        if ($nome === '') $erro = 'Informe o nome da opção.';
        elseif (!$escopoId || !in_array($escopo, ['prod', 'cat'], true)) $erro = 'Escolha a que produto ou categoria essa opção se aplica.';
        elseif ($tipo !== 'texto' && !$valores) $erro = 'Informe pelo menos uma escolha (uma por linha).';
        elseif ($tipo === 'texto' && $acrTexto < 0) $erro = 'Acréscimo inválido.';

        if ($erro) {
            flash('erro', $erro);
            redirecionar('/admin/personalizacao.php' . ($id ? '?editar=' . $id : ''));
        }

        $conn->begin_transaction();
        if ($id) {
            $stmt = $conn->prepare('UPDATE personalizacao_opcoes SET produto_id=?, categoria_id=?, nome=?, tipo=?, obrigatorio=?, acrescimo=?, ordem=? WHERE id=?');
            $stmt->bind_param('iissidii', $produtoId, $categoriaId, $nome, $tipo, $obrig, $acrTexto, $ordem, $id);
            $stmt->execute();
            $conn->query('DELETE FROM personalizacao_valores WHERE opcao_id = ' . $id);
        } else {
            $stmt = $conn->prepare('INSERT INTO personalizacao_opcoes (produto_id, categoria_id, nome, tipo, obrigatorio, acrescimo, ordem) VALUES (?,?,?,?,?,?,?)');
            $stmt->bind_param('iissidi', $produtoId, $categoriaId, $nome, $tipo, $obrig, $acrTexto, $ordem);
            $stmt->execute();
            $id = $conn->insert_id;
        }
        $stmtV = $conn->prepare('INSERT INTO personalizacao_valores (opcao_id, rotulo, acrescimo, ordem) VALUES (?,?,?,?)');
        foreach ($valores as $i => [$rot, $acr]) {
            $stmtV->bind_param('isdi', $id, $rot, $acr, $i);
            $stmtV->execute();
        }
        $conn->commit();
        flash('ok', 'Opção de personalização salva!');
    }

    if ($acao === 'excluir') {
        $stmt = $conn->prepare('DELETE FROM personalizacao_opcoes WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        flash('ok', 'Opção excluída (pedidos já feitos não são afetados).');
    }
    redirecionar('/admin/personalizacao.php');
}

$cats = consultar('SELECT id, nome FROM categorias ORDER BY nome');
$prods = consultar('SELECT id, nome FROM produtos ORDER BY nome');
$opcoes = consultar('SELECT o.*, p.nome AS prod_nome, c.nome AS cat_nome
    FROM personalizacao_opcoes o
    LEFT JOIN produtos p ON p.id = o.produto_id
    LEFT JOIN categorias c ON c.id = o.categoria_id
    ORDER BY COALESCE(c.nome, p.nome), o.ordem, o.id');
$valoresPorOpcao = [];
foreach (consultar('SELECT * FROM personalizacao_valores ORDER BY ordem, id') as $v) $valoresPorOpcao[(int)$v['opcao_id']][] = $v;

$ed = null; $edValores = '';
if (isset($_GET['editar'])) {
    $ed = consultar('SELECT * FROM personalizacao_opcoes WHERE id = ?', 'i', [(int)$_GET['editar']])[0] ?? null;
    if ($ed) {
        foreach ($valoresPorOpcao[(int)$ed['id']] ?? [] as $v) {
            $edValores .= $v['rotulo'] . ((float)$v['acrescimo'] > 0 ? ' | ' . number_format((float)$v['acrescimo'], 2, ',', '') : '') . "\n";
        }
    }
}
$escopoAtual = $ed ? ($ed['produto_id'] ? 'prod:' . $ed['produto_id'] : 'cat:' . $ed['categoria_id']) : '';

adminHeader('Personalização', 'personalizacao');
?>
<div class="cartao">
  <p style="margin-top:0;color:var(--tinta-suave);font-size:.92rem">
    Aqui você define o que o cliente pode escolher nos produtos <strong>sob encomenda</strong> e quanto cada escolha acrescenta ao preço.
    O preço final é: <strong>preço do produto + acréscimos escolhidos</strong>. Todo produto sob encomenda já permite que o cliente envie
    descrição e fotos de referência, mesmo sem nenhuma opção cadastrada.
  </p>
</div>

<div class="grade-2">
  <div class="cartao">
    <h2><?= $ed ? 'Editar opção' : 'Nova opção de personalização' ?></h2>
    <form method="POST" class="form-coluna">
      <?= csrfField() ?><input type="hidden" name="acao" value="salvar"><input type="hidden" name="id" value="<?= (int)($ed['id'] ?? 0) ?>">
      <div class="campo"><label>Vale para</label>
        <select name="escopo" required>
          <option value="">— escolha —</option>
          <optgroup label="Uma categoria inteira (todos os produtos dela)">
            <?php foreach ($cats as $c): ?><option value="cat:<?= (int)$c['id'] ?>" <?= $escopoAtual === 'cat:' . $c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option><?php endforeach; ?>
          </optgroup>
          <optgroup label="Só um produto">
            <?php foreach ($prods as $p): ?><option value="prod:<?= (int)$p['id'] ?>" <?= $escopoAtual === 'prod:' . $p['id'] ? 'selected' : '' ?>><?= e($p['nome']) ?></option><?php endforeach; ?>
          </optgroup>
        </select>
      </div>
      <div class="campo"><label>Nome da opção <small>(o que o cliente vê, ex.: Tamanho, Cor da capa, Nome bordado)</small></label>
        <input type="text" name="nome" required maxlength="80" value="<?= e($ed['nome'] ?? '') ?>"></div>
      <div class="campo"><label>Tipo</label>
        <select name="tipo" id="tipoOpcao">
          <?php foreach (TIPOS_OPCAO as $k => $r): ?><option value="<?= $k ?>" <?= ($ed['tipo'] ?? 'escolha') === $k ? 'selected' : '' ?>><?= e($r) ?></option><?php endforeach; ?>
        </select></div>
      <div class="campo" id="boxValores"><label>Escolhas <small>(uma por linha: <code>Nome | acréscimo</code>. Sem acréscimo, escreva só o nome)</small></label>
        <textarea name="valores" rows="6" placeholder="Pequeno&#10;Médio | 20,00&#10;Grande | 45,00"><?= e($edValores) ?></textarea></div>
      <div class="campo" id="boxAcrescimo"><label>Acréscimo se o cliente preencher (R$)</label>
        <input type="text" name="acrescimo" inputmode="decimal" placeholder="0,00" value="<?= $ed ? e(number_format((float)$ed['acrescimo'], 2, ',', '')) : '' ?>"></div>
      <div class="campo campo-check"><label><input type="checkbox" name="obrigatorio" <?= !empty($ed['obrigatorio']) ? 'checked' : '' ?>> Obrigatória (o cliente precisa preencher)</label></div>
      <div class="campo"><label>Ordem de exibição <small>(menor aparece primeiro)</small></label>
        <input type="number" name="ordem" value="<?= (int)($ed['ordem'] ?? 0) ?>"></div>
      <div class="acoes-linha">
        <button class="btn btn-primario" type="submit">Salvar opção</button>
        <?php if ($ed): ?><a class="btn btn-claro" href="<?= BASE_PATH ?>/admin/personalizacao.php">Cancelar</a><?php endif; ?>
      </div>
    </form>
  </div>

  <div class="cartao">
    <h2>Opções cadastradas (<?= count($opcoes) ?>)</h2>
    <?php if (!$opcoes): ?><p class="vazio">Nenhuma opção ainda. Crie a primeira ao lado — por exemplo, "Tamanho" para a categoria Amigurumis.</p><?php else: ?>
    <div class="tabela-wrap"><table class="tabela">
      <tr><th>Vale para</th><th>Opção</th><th>Escolhas</th><th></th></tr>
      <?php foreach ($opcoes as $o): ?>
        <tr>
          <td><?= $o['categoria_id'] ? '🏷️ ' . e($o['cat_nome']) . '<br><small>(categoria)</small>' : '📓 ' . e($o['prod_nome']) ?></td>
          <td><strong><?= e($o['nome']) ?></strong><br><small><?= e(TIPOS_OPCAO[$o['tipo']] ?? $o['tipo']) ?><?= $o['obrigatorio'] ? ' · obrigatória' : '' ?></small></td>
          <td><small>
            <?php if ($o['tipo'] === 'texto'): ?>
              texto livre<?= (float)$o['acrescimo'] > 0 ? ' (+' . brl($o['acrescimo']) . ')' : '' ?>
            <?php else: foreach ($valoresPorOpcao[(int)$o['id']] ?? [] as $v): ?>
              <?= e($v['rotulo']) ?><?= (float)$v['acrescimo'] > 0 ? ' (+' . brl($v['acrescimo']) . ')' : '' ?><br>
            <?php endforeach; endif; ?>
          </small></td>
          <td><div class="acoes-linha">
            <a class="btn btn-claro btn-peq" href="?editar=<?= (int)$o['id'] ?>">Editar</a>
            <form method="POST" onsubmit="return confirm('Excluir esta opção?')"><?= csrfField() ?><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?= (int)$o['id'] ?>"><button class="btn btn-perigo btn-peq" type="submit">Excluir</button></form>
          </div></td>
        </tr>
      <?php endforeach; ?>
    </table></div>
    <?php endif; ?>
  </div>
</div>
<script>
  const tipo = document.getElementById('tipoOpcao');
  function ajustar() {
    const texto = tipo.value === 'texto';
    document.getElementById('boxValores').style.display = texto ? 'none' : '';
    document.getElementById('boxAcrescimo').style.display = texto ? '' : 'none';
  }
  tipo.addEventListener('change', ajustar); ajustar();
</script>
<?php adminFooter(); ?>
