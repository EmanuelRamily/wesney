CREATE DATABASE IF NOT EXISTS autopecas
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE autopecas;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('varejo', 'atacado', 'admin')
        NOT NULL DEFAULT 'varejo',
    cnpj VARCHAR(14) UNIQUE,
    nome_empresa VARCHAR(150),
    status_empresa ENUM(
        'nao_se_aplica',
        'pendente',
        'aprovada',
        'recusada'
    ) NOT NULL DEFAULT 'nao_se_aplica',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT,
    preco_varejo DECIMAL(10,2) NOT NULL,
    preco_atacado DECIMAL(10,2) NOT NULL,
    estoque INT NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'recebido',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE IF NOT EXISTS itens_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
);

INSERT INTO produtos
(nome, descricao, preco_varejo, preco_atacado, estoque)
SELECT 'Filtro de óleo', 'Filtro para manutenção automotiva', 35.00, 25.00, 20
WHERE NOT EXISTS (
    SELECT 1 FROM produtos WHERE nome = 'Filtro de óleo'
);

INSERT INTO produtos
(nome, descricao, preco_varejo, preco_atacado, estoque)
SELECT 'Pastilha de freio', 'Jogo de pastilhas de freio', 120.00, 95.00, 15
WHERE NOT EXISTS (
    SELECT 1 FROM produtos WHERE nome = 'Pastilha de freio'
);

INSERT INTO produtos
(nome, descricao, preco_varejo, preco_atacado, estoque)
SELECT 'Vela de ignição', 'Vela para motor automotivo', 28.00, 20.00, 30
WHERE NOT EXISTS (
    SELECT 1 FROM produtos WHERE nome = 'Vela de ignição'
);