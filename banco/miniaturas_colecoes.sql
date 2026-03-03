CREATE TABLE IF NOT EXISTS miniaturas_colecoes (
    miniatura_id INT UNSIGNED NOT NULL,
    colecao_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    data_cadastro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (miniatura_id, colecao_id),
    KEY idx_miniaturas_colecoes_usuario (usuario_id),
    KEY idx_miniaturas_colecoes_colecao (colecao_id),
    CONSTRAINT fk_miniaturas_colecoes_miniatura FOREIGN KEY (miniatura_id) REFERENCES miniaturas(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_miniaturas_colecoes_colecao FOREIGN KEY (colecao_id) REFERENCES colecoes(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_miniaturas_colecoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
