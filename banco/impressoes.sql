CREATE TABLE impressoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    estudio_id INT UNSIGNED,
    colecao_id INT UNSIGNED,
    nome_original VARCHAR(150),
    arquivo_impressao VARCHAR(255),
    impressora_id INT UNSIGNED NOT NULL,
    material_id INT UNSIGNED NOT NULL,
    tempo_impressao INT NOT NULL,
    imagem_capa VARCHAR(255),
    unidades_produzidas INT NOT NULL,
    margem_lucro INT NOT NULL,
    taxa_falha INT NOT NULL,
    componente INT,
    imagens_impressao INT,
    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultima_atualizacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    valor_energia DECIMAL(10,8),
    peso_material INT NOT NULL,
    custo_material DECIMAL(10,2),
    custo_lavagem_alcool DECIMAL(10,2),
    custo_energia DECIMAL(10,2),
    depreciacao DECIMAL(10,2),
    custo_total_impressao DECIMAL(10,2),
    custo_por_unidade DECIMAL(10,2),
    lucro DECIMAL(10,2),
    porcentagem_lucro INT,
    preco_venda_sugerido DECIMAL(10,2),
    preco_venda_sugerido_unidade DECIMAL(10,2),
    observacoes TEXT,
    sku VARCHAR(50) UNIQUE,
    usuario_id INT UNSIGNED NOT NULL,
    FOREIGN KEY (estudio_id) REFERENCES estudios(id) ON DELETE SET NULL,
    FOREIGN KEY (colecao_id) REFERENCES colecoes(id) ON DELETE SET NULL,
    FOREIGN KEY (impressora_id) REFERENCES impressoras(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES filamento(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DELIMITER //

CREATE TRIGGER impressoes_before_insert
BEFORE INSERT ON impressoes
FOR EACH ROW
BEGIN
    DECLARE potencia_watts INT;
    DECLARE fator_uso DECIMAL(5,2);
    DECLARE tipo_impressora ENUM('FDM', 'Resina');
    DECLARE valor_material DECIMAL(10,2);
    DECLARE preco_litro_alcool DECIMAL(10,2);
    DECLARE custo_kwh DECIMAL(10,8);
    DECLARE tempo_horas DECIMAL(10,4);
    DECLARE valor_depreciacao DECIMAL(10,2);

    -- Busca os valores necessários
    SELECT potencia, fator_uso, tipo, depreciacao INTO potencia_watts, fator_uso, tipo_impressora, valor_depreciacao
    FROM impressoras WHERE id = NEW.impressora_id;

    SET NEW.depreciacao = valor_depreciacao;

    SELECT valor_kwh INTO custo_kwh
    FROM energia WHERE usuario_id = NEW.usuario_id;

    -- Converte tempo de minutos para horas
    SET tempo_horas = NEW.tempo_impressao / 60;

    -- Converte fator_uso de porcentagem para decimal
    SET fator_uso = fator_uso / 100;

    -- Preenche valor_energia
    SET NEW.valor_energia = custo_kwh;

    -- Calcula o custo de energia
    SET NEW.custo_energia = (potencia_watts * tempo_horas * fator_uso * custo_kwh) / 1000;

    -- Calcula custo_material
    IF tipo_impressora = 'FDM' THEN
        SELECT preco_kilo INTO valor_material FROM filamento WHERE id = NEW.material_id;
        SET NEW.custo_material = NEW.peso_material * (valor_material/1000);
    ELSEIF tipo_impressora = 'Resina' THEN
        SELECT preco_litro INTO valor_material FROM resinas WHERE id = NEW.material_id;
        SET NEW.custo_material = NEW.peso_material * (valor_material/1000);

        -- Calcula custo_lavagem_alcool
        SELECT preco_litro INTO preco_litro_alcool FROM alcool WHERE usuario_id = NEW.usuario_id;
        SET NEW.custo_lavagem_alcool = (preco_litro_alcool / 1000) * NEW.peso_material;
    ELSE
        SET NEW.custo_lavagem_alcool = NULL;
    END IF;
END;
//

CREATE TRIGGER impressoes_before_update
BEFORE UPDATE ON impressoes
FOR EACH ROW
BEGIN
    DECLARE potencia_watts INT;
    DECLARE fator_uso DECIMAL(5,2);
    DECLARE tipo_impressora ENUM('FDM', 'Resina');
    DECLARE valor_material DECIMAL(10,2);
    DECLARE preco_litro_alcool DECIMAL(10,2);
    DECLARE custo_kwh DECIMAL(10,8);
    DECLARE tempo_horas DECIMAL(10,4);
    DECLARE valor_depreciacao DECIMAL(10,2);

    -- Busca os valores necessários
    SELECT potencia, fator_uso, tipo, depreciacao INTO potencia_watts, fator_uso, tipo_impressora, valor_depreciacao
    FROM impressoras WHERE id = NEW.impressora_id;

    SET NEW.depreciacao = valor_depreciacao;

    SELECT valor_kwh INTO custo_kwh
    FROM energia WHERE usuario_id = NEW.usuario_id;

    -- Converte tempo de minutos para horas
    SET tempo_horas = NEW.tempo_impressao / 60;

    -- Converte fator_uso de porcentagem para decimal
    SET fator_uso = fator_uso / 100;

    -- Preenche valor_energia
    SET NEW.valor_energia = custo_kwh;

    -- Calcula o custo de energia
    SET NEW.custo_energia = (potencia_watts * tempo_horas * fator_uso * custo_kwh) / 1000;

    -- Calcula custo_material
    IF tipo_impressora = 'FDM' THEN
        SELECT preco_kilo INTO valor_material FROM filamento WHERE id = NEW.material_id;
        SET NEW.custo_material = NEW.peso_material * (valor_material/1000);
    ELSEIF tipo_impressora = 'Resina' THEN
        SELECT preco_litro INTO valor_material FROM resinas WHERE id = NEW.material_id;
        SET NEW.custo_material = NEW.peso_material * (valor_material/1000);

        -- Calcula custo_lavagem_alcool
        SELECT preco_litro INTO preco_litro_alcool FROM alcool WHERE usuario_id = NEW.usuario_id;
        SET NEW.custo_lavagem_alcool = (preco_litro_alcool / 1000) * NEW.peso_material;
    ELSE
        SET NEW.custo_lavagem_alcool = NULL;
    END IF;
END;
//

DELIMITER ;