CREATE TABLE impressoras (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    marca VARCHAR(100) NOT NULL,
    modelo VARCHAR(100) NOT NULL,
    tipo ENUM('FDM', 'Resina') NOT NULL,
    preco_aquisicao DECIMAL(10,2) NOT NULL,
    consumo INT NOT NULL,
    depreciacao INT NOT NULL,
    tempo_vida_util INT NOT NULL,
    valor_alcool_limpeza DECIMAL(10,2) NOT NULL,
    ultima_atualizacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;