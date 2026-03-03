CREATE TABLE IF NOT EXISTS miniaturas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_sku VARCHAR(50) NOT NULL,      -- O código identificador final
    produto_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    id_impressao INT UNSIGNED,                  -- FK para impressoes.id
    nome_original VARCHAR(150),                     -- Nome da miniatura
    id_estudio INT UNSIGNED NOT NULL,         -- FK para estudios.id
    id_colecao INT UNSIGNED,                  -- FK para colecoes.id
    id_tematica INT UNSIGNED NULL,
    tematica VARCHAR(50),                  -- Ex: Fantasy, Sci-Fi, Horror
    raca VARCHAR(50),                      -- Ex: Elfo, Orc, Humano
    classe VARCHAR(50),                    -- Ex: Guerreiro, Mago, Bardo
    genero VARCHAR(20),                    -- Ex: Masculino, Feminino, Neutro
    criatura VARCHAR(100),                 -- Nome específico (ex: Dragão Vermelho)
    papel VARCHAR(50),                     -- Ex: Herói, PNJ, Chefe, Lacaio
    tamanho VARCHAR(30),                   -- Ex: Pequeno, Médio, Grande, Gigantesco
    base VARCHAR(50),                      -- Ex: Redonda, Quadrada
    pintada BOOLEAN DEFAULT FALSE,         -- 0 para Não, 1 para Sim
    arma_principal VARCHAR(150),           -- Ex: Espada, Arco, Magia
    arma_secundaria VARCHAR(150),          -- Ex: Escudo, Adaga, Orbe
    armadura VARCHAR(150),                 -- Ex: Armadura Completa, Couro, Mágica
    capa VARCHAR(255),                     -- Caminho da capa
    fotos VARCHAR(255),                    -- Caminho das fotos
    outras_caracteristicas TEXT,           -- Outras características adicionais
    observacoes TEXT,                      -- Observações
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_miniaturas_produto FOREIGN KEY (produto_id) REFERENCES produtos(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_miniaturas_impressao FOREIGN KEY (id_impressao) REFERENCES impressoes(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_miniaturas_estudio FOREIGN KEY (id_estudio) REFERENCES estudios(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_miniaturas_colecao FOREIGN KEY (id_colecao) REFERENCES colecoes(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_miniaturas_tematica FOREIGN KEY (id_tematica) REFERENCES tematicas(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_miniaturas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_miniaturas_sku FOREIGN KEY (id_sku, usuario_id) REFERENCES sku(sku, usuario_id) ON UPDATE CASCADE ON DELETE RESTRICT,
    UNIQUE KEY uk_miniaturas_sku_usuario (id_sku, usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;