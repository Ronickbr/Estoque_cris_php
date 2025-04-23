-- Cria o banco de dados se não existir
CREATE DATABASE IF NOT EXISTS estoque;
USE estoque;

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    nivel ENUM('admin', 'usuario') NOT NULL DEFAULT 'usuario',
    token_reset VARCHAR(255) DEFAULT NULL,
    token_expiracao DATETIME DEFAULT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE manufacturers (
    id VARCHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    logo_url TEXT,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create products table
CREATE TABLE products (
    id VARCHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    manufacturer_id VARCHAR(36) NOT NULL,
    stock INT NOT NULL,
    min_stock INT NOT NULL,
    image_url TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (manufacturer_id) REFERENCES manufacturers(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create inventory_movements table
CREATE TABLE inventory_movements (
    id VARCHAR(36) NOT NULL,
    product_id VARCHAR(36) NOT NULL,
    type ENUM('entrada', 'saida') NOT NULL,
    quantity INT NOT NULL,
    notes TEXT,
    created_at DATETIME NOT NULL,
    created_by VARCHAR(36) NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create indexes for better query performance
CREATE INDEX idx_products_manufacturer_id ON products(manufacturer_id);
CREATE INDEX idx_inventory_movements_product_id ON inventory_movements(product_id);
CREATE INDEX idx_inventory_movements_type ON inventory_movements(type);
CREATE INDEX idx_inventory_movements_created_at ON inventory_movements(created_at);

-- Optional: Add trigger to enforce type values if ENUM is not preferred
DELIMITER //
CREATE TRIGGER inventory_movements_type_check
BEFORE INSERT ON inventory_movements
FOR EACH ROW
BEGIN
    IF NEW.type NOT IN ('entrada', 'saida') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Valor de tipo inválido. Deve ser entrada ou saida.';
    END IF;
END //
DELIMITER ;

-- Inserir usuário admin padrão, senha: admin123
INSERT INTO usuarios (nome, email, senha, nivel) VALUES 
('Administrador', 'admin@example.com', '$2y$10$0yrX4r7jZWJVaHZEF4LqDuWZaKo4xDibV9mE.dnxPykwDnhVr1TKy', 'admin'); 