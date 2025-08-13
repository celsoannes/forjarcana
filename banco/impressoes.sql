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
    valor_energia DECIMAL(10,8) NOT NULL,
    peso_material INT NOT NULL,
    custo_material DECIMAL(10,2) NOT NULL,
    custo_lavagem_alcool DECIMAL(10,2),
    custo_energia DECIMAL(10,2) NOT NULL,
    depreciacao DECIMAL(10,2) NOT NULL,
    custo_total_impressao DECIMAL(10,2) NOT NULL,
    custo_por_unidade DECIMAL(10,2) NOT NULL,
    lucro DECIMAL(10,2) NOT NULL,
    porcentagem_lucro INT NOT NULL,
    preco_venda_sugerido DECIMAL(10,2) NOT NULL,
    preco_venda_sugerido_unidade DECIMAL(10,2) NOT NULL,
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
    DECLARE custo_kwh DECIMAL(10,8);
    DECLARE tempo_horas DECIMAL(10,4);

    -- Busca os valores necessários
    SELECT potencia, fator_uso INTO potencia_watts, fator_uso
    FROM impressoras WHERE id = NEW.impressora_id;

    SELECT valor_kwh INTO custo_kwh
    FROM energia WHERE usuario_id = NEW.usuario_id;

    -- Converte tempo de minutos para horas
    SET tempo_horas = NEW.tempo_impressao / 60;

    -- Converte fator_uso de porcentagem para decimal
    SET fator_uso = fator_uso / 100;

    -- Calcula o custo de energia
    SET NEW.custo_energia = (potencia_watts * tempo_horas * fator_uso * custo_kwh) / 1000;
END;
//

CREATE TRIGGER impressoes_before_update
BEFORE UPDATE ON impressoes
FOR EACH ROW
BEGIN
    DECLARE potencia_watts INT;
    DECLARE fator_uso DECIMAL(5,2);
    DECLARE custo_kwh DECIMAL(10,8);
    DECLARE tempo_horas DECIMAL(10,4);

    -- Busca os valores necessários
    SELECT potencia, fator_uso INTO potencia_watts, fator_uso
    FROM impressoras WHERE id = NEW.impressora_id;

    SELECT valor_kwh INTO custo_kwh
    FROM energia WHERE usuario_id = NEW.usuario_id;

    -- Converte tempo de minutos para horas
    SET tempo_horas = NEW.tempo_impressao / 60;

    -- Converte fator_uso de porcentagem para decimal
    SET fator_uso = fator_uso / 100;

    -- Calcula o custo de energia
    SET NEW.custo_energia = (potencia_watts * tempo_horas * fator_uso * custo_kwh) / 1000;
END;
//

CREATE TRIGGER impressoes_after_insert
AFTER INSERT ON impressoes
FOR EACH ROW
BEGIN
    DECLARE potencia_watts INT;
    DECLARE fator_uso DECIMAL(5,2);
    DECLARE custo_kwh DECIMAL(10,8);
    DECLARE tempo_horas DECIMAL(10,4);
    DECLARE custo_energia_calc DECIMAL(10,2);

    SELECT potencia, fator_uso INTO potencia_watts, fator_uso
    FROM impressoras WHERE id = NEW.impressora_id;

    SELECT valor_kwh INTO custo_kwh
    FROM energia WHERE usuario_id = NEW.usuario_id;

    SET tempo_horas = NEW.tempo_impressao / 60;
    SET fator_uso = fator_uso / 100;

    SET custo_energia_calc = (potencia_watts * tempo_horas * fator_uso * custo_kwh) / 1000;

    UPDATE impressoes SET custo_energia = custo_energia_calc WHERE id = NEW.id;
END;
//

CREATE TRIGGER impressoes_after_update
AFTER UPDATE ON impressoes
FOR EACH ROW
BEGIN
    DECLARE potencia_watts INT;
    DECLARE fator_uso DECIMAL(5,2);
    DECLARE custo_kwh DECIMAL(10,8);
    DECLARE tempo_horas DECIMAL(10,4);
    DECLARE custo_energia_calc DECIMAL(10,2);

    SELECT potencia, fator_uso INTO potencia_watts, fator_uso
    FROM impressoras WHERE id = NEW.impressora_id;

    SELECT valor_kwh INTO custo_kwh
    FROM energia WHERE usuario_id = NEW.usuario_id;

    SET tempo_horas = NEW.tempo_impressao / 60;
    SET fator_uso = fator_uso / 100;

    SET custo_energia_calc = (potencia_watts * tempo_horas * fator_uso * custo_kwh) / 1000;

    UPDATE impressoes SET custo_energia = custo_energia_calc WHERE id = NEW.id;
END;
//

CREATE TRIGGER impressoes_set_depreciacao_after_insert
AFTER INSERT ON impressoes
FOR EACH ROW
BEGIN
    DECLARE valor_depreciacao INT;
    SELECT depreciacao INTO valor_depreciacao
    FROM impressoras WHERE id = NEW.impressora_id;
    UPDATE impressoes SET depreciacao = valor_depreciacao WHERE id = NEW.id;
END;
//

CREATE TRIGGER impressoes_set_depreciacao_after_update
AFTER UPDATE ON impressoes
FOR EACH ROW
BEGIN
    DECLARE valor_depreciacao INT;
    SELECT depreciacao INTO valor_depreciacao
    FROM impressoras WHERE id = NEW.impressora_id;
    UPDATE impressoes SET depreciacao = valor_depreciacao WHERE id = NEW.id;
END;
//

CREATE TRIGGER impressoes_set_custo_material_after_insert
AFTER INSERT ON impressoes
FOR EACH ROW
BEGIN
    DECLARE tipo_impressora ENUM('FDM', 'Resina');
    DECLARE valor_material DECIMAL(10,2);
    DECLARE custo_material_calc DECIMAL(10,2);

    -- Busca o tipo da impressora
    SELECT tipo INTO tipo_impressora FROM impressoras WHERE id = NEW.impressora_id;

    -- Busca o valor do material conforme o tipo
    IF tipo_impressora = 'FDM' THEN
        SELECT preco_kilo INTO valor_material FROM filamento WHERE id = NEW.material_id;
    ELSEIF tipo_impressora = 'Resina' THEN
        SELECT preco_litro INTO valor_material FROM resinas WHERE id = NEW.material_id;
    END IF;

    -- Calcula o custo do material
    SET custo_material_calc = NEW.peso_material * valor_material;

    -- Atualiza o valor na tabela impressoes
    UPDATE impressoes SET custo_material = custo_material_calc WHERE id = NEW.id;
END;
//

CREATE TRIGGER impressoes_set_custo_material_after_update
AFTER UPDATE ON impressoes
FOR EACH ROW
BEGIN
    DECLARE tipo_impressora ENUM('FDM', 'Resina');
    DECLARE valor_material DECIMAL(10,2);
    DECLARE custo_material_calc DECIMAL(10,2);

    -- Busca o tipo da impressora
    SELECT tipo INTO tipo_impressora FROM impressoras WHERE id = NEW.impressora_id;

    -- Busca o valor do material conforme o tipo
    IF tipo_impressora = 'FDM' THEN
        SELECT preco_kilo INTO valor_material FROM filamento WHERE id = NEW.material_id;
    ELSEIF tipo_impressora = 'Resina' THEN
        SELECT preco_litro INTO valor_material FROM resinas WHERE id = NEW.material_id;
    END IF;

    -- Calcula o custo do material
    SET custo_material_calc = NEW.peso_material * valor_material;

    -- Atualiza o valor na tabela impressoes
    UPDATE impressoes SET custo_material = custo_material_calc WHERE id = NEW.id;
END;
//

CREATE TRIGGER impressoes_set_custo_lavagem_alcool_after_insert
AFTER INSERT ON impressoes
FOR EACH ROW
BEGIN
    DECLARE tipo_impressora ENUM('FDM', 'Resina');
    DECLARE preco_litro DECIMAL(10,2);
    DECLARE custo_ml DECIMAL(10,4);
    DECLARE custo_lavagem DECIMAL(10,2);

    -- Busca o tipo da impressora
    SELECT tipo INTO tipo_impressora FROM impressoras WHERE id = NEW.impressora_id;

    IF tipo_impressora = 'Resina' THEN
        -- Busca o preço do litro do álcool do usuário
        SELECT preco_litro INTO preco_litro FROM alcool WHERE usuario_id = NEW.usuario_id;
        SET custo_ml = preco_litro / 1000;
        SET custo_lavagem = custo_ml * NEW.peso_material;
        UPDATE impressoes SET custo_lavagem_alcool = custo_lavagem WHERE id = NEW.id;
    END IF;
END;
//

CREATE TRIGGER impressoes_set_custo_lavagem_alcool_after_update
AFTER UPDATE ON impressoes
FOR EACH ROW
BEGIN
    DECLARE tipo_impressora ENUM('FDM', 'Resina');
    DECLARE preco_litro DECIMAL(10,2);
    DECLARE custo_ml DECIMAL(10,4);
    DECLARE custo_lavagem DECIMAL(10,2);

    -- Busca o tipo da impressora
    SELECT tipo INTO tipo_impressora FROM impressoras WHERE id = NEW.impressora_id;

    IF tipo_impressora = 'Resina' THEN
        -- Busca o preço do litro do álcool do usuário
        SELECT preco_litro INTO preco_litro FROM alcool WHERE usuario_id = NEW.usuario_id;
        SET custo_ml = preco_litro / 1000;
        SET custo_lavagem = custo_ml * NEW.peso_material;
        UPDATE impressoes SET custo_lavagem_alcool = custo_lavagem WHERE id = NEW.id;
    END IF;
END;
//

DELIMITER ;