-- Active: 1789385460340@@127.0.0.1@3306
CREATE DATABASE IF NOT EXISTS `discoburguer` DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci;

USE `discoburguer`;

-- DOMÍNIO E USUÁRIOS
CREATE TABLE IF NOT EXISTS `funcionarios` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `cargo` ENUM(
        'administrador',
        'atendente',
        'cozinheiro'
    ) NOT NULL,
    `nome` VARCHAR(100) NOT NULL,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1 CHECK (`ativo` IN (0, 1)),
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `senha_hash` VARCHAR(255) NOT NULL,
    `numero_telefone` VARCHAR(20) NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `categorias_financeiras` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `nome` VARCHAR(100) NOT NULL,
    `tipo` ENUM('entrada', 'saida') NOT NULL,
    `tipo_saida` ENUM(
        'fixo',
        'variavel',
        'nao_aplicavel'
    ) NOT NULL,
    `grupo_dre` ENUM(
        'receita_bruta',
        'deducao_venda',
        'imposto_venda',
        'custo_mercadoria',
        'despesa_operacional',
        'despesa_financeira',
        'receita_financeira',
        'imposto_lucro',
        'nao_aplicavel'
    ) NOT NULL DEFAULT 'nao_aplicavel',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `fornecedores` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `nome_razao_social` VARCHAR(200) NOT NULL,
    `cnpj_cpf` VARCHAR(20) UNIQUE NULL,
    `telefone` VARCHAR(20) NULL,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1 CHECK (`ativo` IN (0, 1)),
    `funcionario_id` INT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_fornecedores_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB;

-- PRODUTOS E ESTOQUE
CREATE TABLE IF NOT EXISTS `produtos` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `categorias` ENUM(
        'hamburguer',
        'porcoes',
        'bebidas',
        'combos',
        'sobremesa_gelada',
        'extras'
    ) NOT NULL DEFAULT 'hamburguer',
    `nome` VARCHAR(200) NOT NULL,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1 CHECK (`ativo` IN (0, 1)),
    `descricao` TEXT NOT NULL,
    `preco_venda` DECIMAL(10, 2) NOT NULL CHECK (`preco_venda` >= 0),
    `imagem_caminho` VARCHAR(255) NULL,
    `visivel` TINYINT(1) NOT NULL DEFAULT 0 CHECK (`visivel` IN (0, 1)),
    `make_or_buy` ENUM('produto_acabado', 'revenda') NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_produtos_categorias` (`categorias`),
    INDEX `idx_produtos_status` (`ativo`, `visivel`)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `estoque` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `nome` VARCHAR(250) NOT NULL,
    `codigo` VARCHAR(250) NOT NULL UNIQUE,
    `unidade_medida` ENUM('g', 'un') NOT NULL,
    `estoque_minimo` DECIMAL(10, 2) NOT NULL DEFAULT 0.00 CHECK (`estoque_minimo` >= 0),
    `eh_revenda` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `lotes_estoque` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `estoque_id` INT NOT NULL,
    `fornecedor_id` INT NULL,
    `numero_lote` VARCHAR(50) NOT NULL,
    `data_validade` DATE NOT NULL,
    `quantidade_estoque` DECIMAL(10, 2) NOT NULL DEFAULT 0.00 CHECK (`quantidade_estoque` >= 0),
    `preco_custo` DECIMAL(10, 2) NOT NULL DEFAULT 0.00 CHECK (`preco_custo` >= 0),
    `data_entrada` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_lote_estoque_validade` (
        `estoque_id`,
        `data_validade`,
        `quantidade_estoque`
    ),
    CONSTRAINT `fk_lotes_estoque` FOREIGN KEY (`estoque_id`) REFERENCES `estoque` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_lotes_fornecedor` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `movimentacoes_estoque` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `lote_estoque_id` INT NOT NULL,
    `funcionario_id` INT NULL,
    `tipo_movimentacao` ENUM(
        'entrada',
        'saida_venda',
        'perda_validade',
        'ajuste',
        'descarte'
    ) NOT NULL,
    `quantidade` DECIMAL(10, 2) NOT NULL,
    `observacao` VARCHAR(255) NULL,
    `data_movimentacao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_mov_lote` (`lote_estoque_id`),
    INDEX `idx_mov_funcionario` (`funcionario_id`),
    INDEX `idx_mov_tipo_data` (
        `tipo_movimentacao`,
        `data_movimentacao`
    ),
    CONSTRAINT `fk_mov_lote` FOREIGN KEY (`lote_estoque_id`) REFERENCES `lotes_estoque` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_mov_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `alertas_estoque` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `estoque_id` INT NOT NULL,
    `tipo_alerta` ENUM('estoque_minimo', 'validade') NOT NULL,
    `mensagem` VARCHAR(255) NOT NULL,
    `data_alerta` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_alertas_busca` (
        `estoque_id`,
        `tipo_alerta`,
        `data_alerta`
    ),
    CONSTRAINT `fk_alertas_estoque` FOREIGN KEY (`estoque_id`) REFERENCES `estoque` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `receitas` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `produto_id` INT NOT NULL,
    `ingrediente_id` INT NOT NULL,
    `quantidade_usada` DECIMAL(10, 2) NOT NULL DEFAULT 0.00 CHECK (`quantidade_usada` > 0),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_receita_produto_ingrediente` (
        `produto_id`,
        `ingrediente_id`
    ),
    INDEX `idx_receitas_produto` (`produto_id`),
    INDEX `idx_receitas_ingrediente` (`ingrediente_id`),
    CONSTRAINT `fk_receitas_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_receitas_ingrediente` FOREIGN KEY (`ingrediente_id`) REFERENCES `estoque` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `itens_combo` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `combo_id` INT NOT NULL,
    `produto_filho_id` INT NOT NULL,
    `quantidade` INT NOT NULL DEFAULT 1 CHECK (`quantidade` > 0),
    PRIMARY KEY (`id`),
    INDEX `idx_combo_pai` (`combo_id`),
    INDEX `idx_combo_filho` (`produto_filho_id`),
    CONSTRAINT `fk_combo_pai` FOREIGN KEY (`combo_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_combo_filho` FOREIGN KEY (`produto_filho_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- MESAS E OPERAÇÃO
CREATE TABLE IF NOT EXISTS `mesas` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `numero` INT NOT NULL UNIQUE,
    `status` ENUM(
        'livre',
        'em_atendimento',
        'reservada'
    ) NOT NULL DEFAULT 'livre',
    `quantidade_lugares` INT NOT NULL DEFAULT 2 CHECK (`quantidade_lugares` > 0),
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `mesas_reservadas` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `mesa_id` INT NOT NULL,
    `nome_cliente` VARCHAR(100) NOT NULL,
    `telefone` VARCHAR(20) NOT NULL,
    `quantidade_pessoas` INT NOT NULL DEFAULT 1 CHECK (`quantidade_pessoas` > 0),
    `data_reserva` DATETIME NOT NULL,
    `status_reserva` ENUM(
        'ativa',
        'concluida',
        'cancelada'
    ) NOT NULL DEFAULT 'ativa',
    PRIMARY KEY (`id`),
    INDEX `idx_reserva_mesa_data` (`mesa_id`, `data_reserva`),
    CONSTRAINT `fk_reserva_id_mesa` FOREIGN KEY (`mesa_id`) REFERENCES `mesas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `pedidos` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `mesa_id` INT NULL,
    `atendente_id` INT NOT NULL,
    `nome_cliente` VARCHAR(200) NOT NULL,
    `tipo` ENUM('delivery', 'local') NOT NULL,
    `status` ENUM(
        'aberto',
        'em_preparo',
        'pedido_pronto',
        'finalizado',
        'cancelado'
    ) NOT NULL DEFAULT 'aberto',
    `valor_total` DECIMAL(10, 2) NOT NULL DEFAULT 0.00 CHECK (`valor_total` >= 0),
    `chave_validacao` VARCHAR(64) NULL UNIQUE,
    `data_avaliacao` DATETIME NULL,
    `data_pedido` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_pedidos_atendente` (`atendente_id`),
    INDEX `idx_pedidos_mesa` (`mesa_id`),
    INDEX `idx_pedidos_status_data` (`status`, `data_pedido`),
    INDEX `idx_pedidos_chave` (`chave_validacao`),
    CONSTRAINT `fk_pedidos_mesa` FOREIGN KEY (`mesa_id`) REFERENCES `mesas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_pedidos_atendente` FOREIGN KEY (`atendente_id`) REFERENCES `funcionarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `pagamentos_pedido` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `pedido_id` INT NOT NULL,
    `forma_pagamento` ENUM(
        'cartao_debito',
        'cartao_credito',
        'dinheiro',
        'pix'
    ) NOT NULL,
    `valor` DECIMAL(10, 2) NOT NULL CHECK (`valor` > 0),
    `data_pagamento` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `data_compensacao_prevista` DATE NULL,
    `status_compensacao` ENUM(
        'liquidado',
        'pendente_repasse'
    ) NOT NULL DEFAULT 'liquidado',
    PRIMARY KEY (`id`),
    INDEX `idx_pagamentos_pedido` (`pedido_id`),
    CONSTRAINT `fk_pagamentos_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `itens_pedidos` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `pedido_id` INT NOT NULL,
    `produto_id` INT NOT NULL,
    `quantidade` INT NOT NULL CHECK (`quantidade` > 0),
    `status_item` ENUM(
        'pendente',
        'em_preparo',
        'pedido_pronto',
        'cancelado'
    ) NOT NULL DEFAULT 'pendente',
    PRIMARY KEY (`id`),
    INDEX `idx_itens_pedido_rel` (`pedido_id`, `status_item`),
    INDEX `idx_itens_produto` (`produto_id`),
    CONSTRAINT `fk_itens_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_itens_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `customizacoes_item_pedido` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `item_pedido_id` INT NOT NULL,
    `produto_id` INT NOT NULL,
    `tipo_acao` ENUM('adicionar', 'remover') NOT NULL,
    `quantidade` DECIMAL(10, 2) NOT NULL DEFAULT 1.00 CHECK (`quantidade` > 0),
    PRIMARY KEY (`id`),
    INDEX `idx_custom_item` (`item_pedido_id`),
    INDEX `idx_custom_produto_extra` (`produto_id`),
    CONSTRAINT `fk_custom_item` FOREIGN KEY (`item_pedido_id`) REFERENCES `itens_pedidos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_custom_ingrediente` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `ocorrencias_pedido` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `pedido_id` INT NOT NULL,
    `item_pedido_id` INT NOT NULL,
    `tipo` ENUM(
        'cancelamento',
        'troca'
    ) NOT NULL,
    `motivo` TEXT NOT NULL,
    `retornou_estoque` TINYINT(1) NOT NULL DEFAULT 0 CHECK (`retornou_estoque` IN (0, 1)),
    `data_ocorrencia` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_ocorrencias_pedido` (`pedido_id`),
    CONSTRAINT `fk_ocorrencias_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ocorrencias_item` FOREIGN KEY (`item_pedido_id`) REFERENCES `itens_pedidos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `feedbacks` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `pedido_id` INT NULL,
    `clicou_para_avaliar` TINYINT(1) NOT NULL DEFAULT 0 CHECK (
        `clicou_para_avaliar` IN (0, 1)
    ),
    `google_review_id` VARCHAR(128) NULL UNIQUE,
    `nome_avaliador` VARCHAR(150) NULL,
    `nota` TINYINT UNSIGNED NOT NULL CHECK (`nota` BETWEEN 1 AND 5),
    `comentario` TEXT NULL,
    `data_envio_google` DATETIME NULL,
    `data_sincronizacao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_feedback_pedido` (`pedido_id`),
    INDEX `idx_exibicao_site` (`nota`, `data_envio_google`),
    CONSTRAINT `fk_feedbacks_item` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `fluxo_caixa` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `pedido_id` INT NULL,
    `categoria_id` INT NOT NULL,
    `funcionario_id` INT NOT NULL,
    `descricao` VARCHAR(255) NOT NULL,
    `valor` DECIMAL(10, 2) NOT NULL CHECK (valor >= 0),
    `data_movimentacao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_fluxo_pedido` (`pedido_id`),
    INDEX `idx_fluxo_categoria` (`categoria_id`),
    INDEX `idx_fluxo_funcionario` (`funcionario_id`),
    INDEX `idx_fluxo_data` (`data_movimentacao`),
    CONSTRAINT `fk_fluxo_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_fluxo_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_financeiras` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_fluxo_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `contas_a_pagar` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `fornecedor_id` INT NULL,
    `categoria_id` INT NOT NULL,
    `descricao` VARCHAR(255) NOT NULL,
    `valor` DECIMAL(10, 2) NOT NULL CHECK (`valor` > 0),
    `data_vencimento` DATE NOT NULL,
    `data_pagamento` DATETIME NULL,
    `status_pagamento` ENUM(
        'pendente',
        'pago',
        'cancelado'
    ) NOT NULL DEFAULT 'pendente',
    PRIMARY KEY (`id`),
    INDEX `idx_vencimento_status` (
        `data_vencimento`,
        `status_pagamento`
    ),
    CONSTRAINT `fk_contas_fornecedor` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_contas_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_financeiras` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `caixas_diarios` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `funcionario_abertura_id` INT NOT NULL,
    `funcionario_fechamento_id` INT NULL,
    `data_abertura` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `data_fechamento` DATETIME NULL,
    `saldo_inicial` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `saldo_final_sistema` DECIMAL(10, 2) NULL,
    `saldo_final_gaveta` DECIMAL(10, 2) NULL,
    `status` ENUM('aberto', 'fechado') NOT NULL DEFAULT 'aberto',
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_caixa_abertura` FOREIGN KEY (`funcionario_abertura_id`) REFERENCES `funcionarios` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_caixa_fechamento` FOREIGN KEY (`funcionario_fechamento_id`) REFERENCES `funcionarios` (`id`) ON DELETE RESTRICT
) ENGINE = InnoDB;

