CREATE TABLE impressoras (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    marca VARCHAR(100) NOT NULL,
    modelo VARCHAR(100) NOT NULL,
    tipo ENUM('FDM', 'Resina') NOT NULL,
    preco_aquisicao DECIMAL(10,2) NOT NULL,
    consumo INT NOT NULL,
    depreciacao INT NOT NULL, -- porcentagem, ex: 10 para 10%
    tempo_vida_util INT NOT NULL,
    custo_hora DECIMAL(10,4) AS ((preco_aquisicao / tempo_vida_util) * (depreciacao / 100)) STORED,
    ultima_atualizacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    usuario_id INT UNSIGNED NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;