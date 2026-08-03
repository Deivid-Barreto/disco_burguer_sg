CREATE DATABASE IF NOT EXISTS `discoburguer`;

USE `discoburguer`;

CREATE TABLE IF NOT EXISTS `funcionarios` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `nome` VARCHAR(100) NOT NULL,
    `cargo` ENUM('Garcom', 'Cozinheiro', 'Administrador', 'Balconista') NOT NULL,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `senha` VARCHAR(255) NOT NULL,
    `numero_telefone` VARCHAR(20) NULL,
     PRIMARY KEY (`id`)
);

INSERT INTO `funcionarios` (nome, cargo, email, senha) VALUES 
('Administrador FIXO', 'Administrador', 'gerentearearestrita@discoburguer.com', SHA2('admin123', 256));


CREATE TABLE IF NOT EXISTS `categorias` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(50) NOT NULL UNIQUE,
  `principal` TINYINT(1) NOT NULL DEFAULT 0,
   PRIMARY KEY (`id`)
);

INSERT INTO categorias (nome,principal) VALUES 
('burguers', 1),
('combos', 0),
('shakes', 0),
('bebidas', 0),
('porcoes', 0);



CREATE TABLE IF NOT EXISTS `produtos` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `categoria_id` INT NOT NULL,
  `nome` VARCHAR(200) NOT NULL,
  `descricao` TEXT NOT NULL,
  `preco_venda` DECIMAL(10,2) NOT NULL,
  `imagemcaminho` VARCHAR(255) NULL,
  `mostrar_no_cardapio` TINYINT(1) NOT NULL DEFAULT 0,
  `eh_revenda` TINYINT(1) NOT NULL DEFAULT 0,
  `eh_combo` TINYINT(1) NOT NULL DEFAULT 0,
   PRIMARY KEY (`id`),
   FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS `ingredientes` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(20) NOT NULL UNIQUE,
  `nome` VARCHAR(250) NOT NULL,
  `quantidade_estoque` DECIMAL(10,3) NOT NULL DEFAULT 0.000,
  `estoque_minimo` DECIMAL(10,3) NOT NULL DEFAULT 0.000,
  `unidade_medida` ENUM('g', 'un') NOT NULL,
   PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `alertas_estoque` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `ingrediente_id` INT NOT NULL,
  `mensagem` VARCHAR(255) NOT NULL,
  `data_alerta` DATETIME DEFAULT CURRENT_TIMESTAMP,
   PRIMARY KEY (`id`),
   FOREIGN KEY (`ingrediente_id`) REFERENCES `ingredientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

DELIMITER //
CREATE TRIGGER `trigger_checar_estoqueminimo`
AFTER UPDATE ON `ingredientes`
FOR EACH ROW
BEGIN
    
    DECLARE margem_seguranca DECIMAL(10,3) DEFAULT 10.000;

    IF NEW.quantidade_estoque <= (NEW.estoque_minimo + margem_seguranca) 
       AND OLD.quantidade_estoque > (NEW.estoque_minimo + margem_seguranca) THEN
        INSERT INTO alertas_estoque (ingrediente_id, mensagem) VALUES (
            NEW.id,
            CONCAT('ATENÇÃO: O ingrediente "', NEW.nome, '" está próximo do mínimo. Atual: ', NEW.quantidade_estoque, ' ', NEW.unidade_medida)
        );
    END IF;
END //
DELIMITER ;

CREATE TABLE IF NOT EXISTS `receitas` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `produto_id` INT NOT NULL,
  `ingrediente_id` INT NOT NULL,
  `quantidade_usada` DECIMAL(10,3) NOT NULL DEFAULT 0.000,
   PRIMARY KEY (`id`),
   FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
   FOREIGN KEY (`ingrediente_id`) REFERENCES `ingredientes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS `combo_itens` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `combo_id` INT NOT NULL,
    `produto_filho_id` INT NOT NULL,
    `quantidade` INT NOT NULL DEFAULT 1,
     PRIMARY KEY (`id`),
     FOREIGN KEY (`combo_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
     FOREIGN KEY (`produto_filho_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS `mesas` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `numero` INT NOT NULL UNIQUE,
  `status` ENUM('Livre', 'Em_atendimento') DEFAULT 'Livre',
  `ativa` TINYINT(1) NOT NULL DEFAULT 1,
   PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `pedidos` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `mesa_id` INT NULL,
  `atendente_id` INT NOT NULL,
  `nome_cliente` VARCHAR(200) NOT NULL,
  `eh_delivery` TINYINT(1) NOT NULL DEFAULT 0,
  `codigo_recibo` VARCHAR(50) NOT NULL UNIQUE,
  `status` ENUM('Aberto', 'Finalizado', 'Cancelado') DEFAULT 'Aberto',
  `data_pedido` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
   PRIMARY KEY (`id`),
   FOREIGN KEY (`mesa_id`) REFERENCES `mesas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
   FOREIGN KEY (`atendente_id`) REFERENCES `funcionarios` (`id`)
);

CREATE TABLE IF NOT EXISTS `feedbacks` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nota` TINYINT UNSIGNED NOT NULL CHECK (nota BETWEEN 1 AND 5),
  `pedido_id` INT NOT NULL,
  `nome` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `comentario` TEXT NOT NULL,
  `data_envio` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notificado` TINYINT(1) NOT NULL DEFAULT 0,
  `visivel` TINYINT(1) NOT NULL DEFAULT 0,
   PRIMARY KEY (`id`),
   FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS `itens_pedido` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `pedido_id` INT NOT NULL,
  `produto_id` INT NOT NULL,
  `quantidade` INT NOT NULL,
  `preco_unitario` DECIMAL(10,2) NOT NULL,
  `status_item` ENUM('Pendente', 'Em_preparo', 'Pedido_pronto', 'Cancelado') DEFAULT 'Pendente',
   PRIMARY KEY (`id`),
   FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
   FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS `exclusoes_ingrediente` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `item_pedido_id` INT NOT NULL,
  `ingrediente_id` INT NOT NULL,
   PRIMARY KEY (`id`),
   FOREIGN KEY (`item_pedido_id`) REFERENCES `itens_pedido` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
   FOREIGN KEY (`ingrediente_id`) REFERENCES `ingredientes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS `ocorrencias_pedido` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `pedido_id` INT NOT NULL,
  `item_pedido_id` INT NULL, 
  `tipo` ENUM('Devolução', 'Cancelamento', 'Troca') NOT NULL,
  `motivo` TEXT NOT NULL,
  `retornou_estoque` TINYINT(1) NOT NULL DEFAULT 0,
  `data_ocorrencia` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
   PRIMARY KEY (`id`),
   FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
   FOREIGN KEY (`item_pedido_id`) REFERENCES `itens_pedido` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);