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

CREATE TABLE IF NOT EXISTS manufacturers (
    id VARCHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    logo_url TEXT,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create products table
CREATE TABLE IF NOT EXISTS products (
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
CREATE TABLE IF NOT EXISTS inventory_movements (
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

-- Tabela de Notificações
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('warning', 'success', 'info', 'danger') NOT NULL DEFAULT 'info',
    message TEXT NOT NULL,
    link VARCHAR(255) NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create indexes for better query performance
-- (Check if index exists logic is not standard SQL, dropping if exists or just ignoring errors is easier. 
-- For simplicity, we assume if tables are new, indexes are needed. if tables exist, indexes might exist.
-- MySQL CREATE INDEX IF NOT EXISTS is only available in recent versions.
-- We will suppress errors for indexes in the execution script or leave them as is, 
-- but since the user error is TABLE NOT FOUND, we assume fresh start for these tables.)

-- Inserir usuário admin padrão, senha: admin123
INSERT IGNORE INTO usuarios (nome, email, senha, nivel) VALUES 
('Administrador', 'admin@example.com', '$2y$10$0yrX4r7jZWJVaHZEF4LqDuWZaKo4xDibV9mE.dnxPykwDnhVr1TKy', 'admin'); 

-- Inicializar notificação de boas vindas
INSERT INTO notifications (type, message, created_at) 
SELECT 'info', 'Bem-vindo ao novo sistema de Notificações!', NOW()
WHERE NOT EXISTS (SELECT 1 FROM notifications LIMIT 1);
 