<?php
/**
 * Personalização de produtos sob encomenda.
 *
 * Como funciona (resumo):
 *  - No admin você cria "opções" (ex.: Tamanho, Cor, Nome bordado) com "valores" e um ACRÉSCIMO de preço.
 *  - Na página do produto o cliente escolhe as opções, escreve o que imagina e envia fotos de referência.
 *  - O servidor soma o preço (preço base + acréscimos) — o navegador nunca decide o valor.
 *  - Tudo é guardado numa "personalização" com um código (token). O carrinho leva só esse código.
 *  - No checkout o servidor lê o preço guardado e liga a personalização ao pedido.
 */

/** Cria (se não existirem) as tabelas e a coluna usadas pela personalização. */
function garantirTabelasPersonalizacao(mysqli $conn): void {
    static $feito = false;
    if ($feito) return;
    $feito = true;

    $conn->query("CREATE TABLE IF NOT EXISTS personalizacao_opcoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        produto_id INT NULL,
        categoria_id INT NULL,
        nome VARCHAR(80) NOT NULL,
        tipo VARCHAR(12) NOT NULL DEFAULT 'escolha',
        obrigatorio TINYINT(1) NOT NULL DEFAULT 0,
        acrescimo DECIMAL(10,2) NOT NULL DEFAULT 0,
        ordem INT NOT NULL DEFAULT 0,
        FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
        FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS personalizacao_valores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        opcao_id INT NOT NULL,
        rotulo VARCHAR(80) NOT NULL,
        acrescimo DECIMAL(10,2) NOT NULL DEFAULT 0,
        ordem INT NOT NULL DEFAULT 0,
        FOREIGN KEY (opcao_id) REFERENCES personalizacao_opcoes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS personalizacoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        token CHAR(32) NOT NULL UNIQUE,
        produto_id INT NOT NULL,
        preco_final DECIMAL(10,2) NOT NULL,
        escolhas TEXT,
        descricao TEXT,
        arquivos TEXT,
        pedido_id INT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (pedido_id),
        FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Liga o item do pedido à personalização (se a coluna ainda não existir)
    $tem = $conn->query("SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedido_itens' AND COLUMN_NAME = 'personalizacao_id'")->fetch_row()[0];
    if (!(int)$tem) {
        $conn->query('ALTER TABLE pedido_itens ADD COLUMN personalizacao_id INT NULL');
    }
}

/** Pasta onde ficam as fotos de referência (criada e protegida automaticamente). */
function pastaReferencias(): string {
    $dir = __DIR__ . '/../referencias';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
        @file_put_contents($dir . '/.htaccess', "# Fotos dos clientes: só o painel admin pode ver (via admin/referencia.php)\nRequire all denied\nOptions -Indexes\n");
        @file_put_contents($dir . '/index.html', '');
    }
    return $dir;
}

/** Opções (com seus valores) que valem para um produto: as dele + as da categoria dele. */
function opcoesDoProduto(mysqli $conn, int $produtoId, int $categoriaId): array {
    $stmt = $conn->prepare('SELECT * FROM personalizacao_opcoes WHERE produto_id = ? OR categoria_id = ? ORDER BY ordem, id');
    $stmt->bind_param('ii', $produtoId, $categoriaId);
    $stmt->execute();
    $opcoes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    if (!$opcoes) return [];

    $ids = array_map(fn($o) => (int)$o['id'], $opcoes);
    $in = implode(',', $ids); // só inteiros, seguro
    $vals = $conn->query("SELECT * FROM personalizacao_valores WHERE opcao_id IN ($in) ORDER BY ordem, id")->fetch_all(MYSQLI_ASSOC);
    $porOpcao = [];
    foreach ($vals as $v) $porOpcao[(int)$v['opcao_id']][] = $v;
    foreach ($opcoes as &$o) $o['valores'] = $porOpcao[(int)$o['id']] ?? [];
    return $opcoes;
}

/**
 * Valida o que o cliente escolheu e calcula o acréscimo.
 * Devolve ['erro' => '...'] ou ['escolhas' => [...], 'acrescimo' => float].
 */
function processarEscolhas(array $opcoes, array $post): array {
    $escolhas = [];
    $total = 0.0;
    foreach ($opcoes as $o) {
        $id = (int)$o['id'];
        $item = ['opcao' => $o['nome'], 'tipo' => $o['tipo'], 'itens' => [], 'texto' => null, 'acrescimo' => 0.0];

        if ($o['tipo'] === 'texto') {
            $txt = trim((string)($post['tx'][$id] ?? ''));
            if (mb_strlen($txt) > 200) return ['erro' => 'Texto muito longo em "' . $o['nome'] . '" (máx. 200 letras).'];
            if ($txt !== '') {
                $item['texto'] = $txt;
                $item['acrescimo'] = (float)$o['acrescimo'];
            }
        } else {
            $enviado = $post['op'][$id] ?? ($o['tipo'] === 'multipla' ? [] : null);
            $marcados = is_array($enviado) ? $enviado : (($enviado === null || $enviado === '') ? [] : [$enviado]);
            if ($o['tipo'] === 'escolha' && count($marcados) > 1) return ['erro' => 'Escolha só uma opção em "' . $o['nome'] . '".'];
            $permitidos = [];
            foreach ($o['valores'] as $v) $permitidos[(int)$v['id']] = $v;
            foreach (array_unique($marcados) as $m) {
                $m = (int)$m;
                if (!isset($permitidos[$m])) return ['erro' => 'Opção inválida em "' . $o['nome'] . '".'];
                $item['itens'][] = ['rotulo' => $permitidos[$m]['rotulo'], 'acrescimo' => (float)$permitidos[$m]['acrescimo']];
                $item['acrescimo'] += (float)$permitidos[$m]['acrescimo'];
            }
        }

        $preenchido = $item['texto'] !== null || $item['itens'];
        if ($o['obrigatorio'] && !$preenchido) return ['erro' => 'Preencha "' . $o['nome'] . '" para continuar.'];
        if ($preenchido) { $escolhas[] = $item; $total += $item['acrescimo']; }
    }
    return ['escolhas' => $escolhas, 'acrescimo' => round($total, 2)];
}

/** Texto curto para mostrar no carrinho/pedido: "Tamanho: Médio · Cor: Lilás" */
function resumoEscolhas(array $escolhas): string {
    $partes = [];
    foreach ($escolhas as $e) {
        $valor = $e['texto'] !== null ? $e['texto'] : implode(', ', array_map(fn($i) => $i['rotulo'], $e['itens']));
        $partes[] = $e['opcao'] . ': ' . $valor;
    }
    return implode(' · ', $partes);
}

/** Apaga as fotos de uma personalização. */
function apagarArquivosPersonalizacao(?string $arquivosJson): void {
    foreach ((array)json_decode((string)$arquivosJson, true) as $a) {
        if (is_string($a) && preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $a)) {
            @unlink(pastaReferencias() . '/' . $a);
        }
    }
}
