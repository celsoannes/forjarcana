CREATE TABLE IF NOT EXISTS miniaturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150),                     -- Nome da miniatura
    sku VARCHAR(100) UNIQUE NOT NULL,      -- O código identificador final
    estudio VARCHAR(50) NOT NULL,          -- Ex: Loot Studios, Archvillain
    tematica VARCHAR(50),                  -- Ex: Fantasy, Sci-Fi, Horror
    colecao VARCHAR(100),                  -- Ex: "The Curse of Hollow"
    raca VARCHAR(50),                      -- Ex: Elfo, Orc, Humano
    classe VARCHAR(50),                    -- Ex: Guerreiro, Mago, Bardo
    genero VARCHAR(20),                    -- Ex: Masculino, Feminino, Neutro
    criatura VARCHAR(100),                 -- Nome específico (ex: Dragão Vermelho)
    papel VARCHAR(50),                     -- Ex: Herói, PNJ, Chefe, Lacaio
    tamanho VARCHAR(30),                   -- Ex: Pequeno, Médio, Grande, Gigantesco
    base VARCHAR(50),                      -- Ex: Redonda, Quadrada
    material VARCHAR(50),                  -- Ex: Plástico, Resina
    pintada BOOLEAN DEFAULT FALSE,         -- 0 para Não, 1 para Sim
    arma_principal VARCHAR(150),           -- Ex: Espada, Arco, Magia
    arma_secundaria VARCHAR(150),          -- Ex: Escudo, Adaga, Orbe
    armadura VARCHAR(150),                 -- Ex: Armadura Completa, Couro, Mágica
    outras_caracteristicas TEXT,           -- Outras características adicionais
    foto VARCHAR(255),                     -- Caminho da foto
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);