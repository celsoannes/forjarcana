CREATE TABLE componentes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    nome_material VARCHAR(255) NOT NULL,
    tipo_material VARCHAR(255) NOT NULL,
    descricao TEXT,
    unidade_medida ENUM('un','m','cm','mm','kg','g','L','mL') NOT NULL,
    valor_unitario DECIMAL(10,2) NOT NULL,
    fornecedor VARCHAR(255),
    observacoes TEXT,
    imagem VARCHAR(255),
    ultima_atualizacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;