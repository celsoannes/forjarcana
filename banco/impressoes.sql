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
    markup INT NOT NULL,
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
    DECLARE fator_uso_impressora DECIMAL(4,2);
    DECLARE tipo_impressora ENUM('FDM', 'Resina');
    DECLARE valor_material DECIMAL(10,2);
    DECLARE preco_litro_alcool DECIMAL(10,2);
    DECLARE custo_kwh DECIMAL(10,8);
    DECLARE tempo_horas DECIMAL(10,4);
    DECLARE custo_hora_impressora DECIMAL(10,4);
    DECLARE custo_minuto DECIMAL(10,6);
    DECLARE custo_depreciacao DECIMAL(10,2);

    -- Busca dados da impressora
    SELECT potencia, fator_uso, tipo, custo_hora INTO potencia_watts, fator_uso_impressora, tipo_impressora, custo_hora_impressora
    FROM impressoras WHERE id = NEW.impressora_id;

    -- Calcula custo por minuto e custo depreciação
    SET custo_minuto = custo_hora_impressora / 60;
    SET custo_depreciacao = custo_minuto * NEW.tempo_impressao;
    SET NEW.depreciacao = custo_depreciacao;

    -- Busca valor do kWh
    SELECT valor_kwh INTO custo_kwh
    FROM energia WHERE usuario_id = NEW.usuario_id;

    SET tempo_horas = NEW.tempo_impressao / 60;

    SET NEW.valor_energia = custo_kwh;
    SET NEW.custo_energia = (potencia_watts * tempo_horas * fator_uso_impressora * custo_kwh) / 1000;

    -- Log dos valores usados no cálculo
    INSERT INTO impressoes_trigger_log
    (impressao_id, evento, custo_energia, potencia_watts, tempo_horas, fator_uso, custo_kwh, custo_hora, custo_minuto, custo_depreciacao, tempo_impressao)
    VALUES (
        0,
        'INSERT',
        NEW.custo_energia,
        potencia_watts,
        tempo_horas,
        fator_uso_impressora,
        custo_kwh,
        custo_hora_impressora,
        custo_minuto,
        custo_depreciacao,
        NEW.tempo_impressao
    );

    IF tipo_impressora = 'FDM' THEN
        SELECT preco_kilo INTO valor_material FROM filamento WHERE id = NEW.material_id;
        SET NEW.custo_material = NEW.peso_material * (valor_material/1000);
    ELSEIF tipo_impressora = 'Resina' THEN
        SELECT preco_litro INTO valor_material FROM resinas WHERE id = NEW.material_id;
        SET NEW.custo_material = NEW.peso_material * (valor_material/1000);

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
    DECLARE fator_uso_impressora DECIMAL(4,2);
    DECLARE tipo_impressora ENUM('FDM', 'Resina');
    DECLARE valor_material DECIMAL(10,2);
    DECLARE preco_litro_alcool DECIMAL(10,2);
    DECLARE custo_kwh DECIMAL(10,8);
    DECLARE tempo_horas DECIMAL(10,4);
    DECLARE custo_hora_impressora DECIMAL(10,4);
    DECLARE custo_minuto DECIMAL(10,6);
    DECLARE custo_depreciacao DECIMAL(10,2);

    -- Busca dados da impressora
    SELECT potencia, fator_uso, tipo, custo_hora INTO potencia_watts, fator_uso_impressora, tipo_impressora, custo_hora_impressora
    FROM impressoras WHERE id = NEW.impressora_id;

    -- Calcula custo por minuto e custo depreciação
    SET custo_minuto = custo_hora_impressora / 60;
    SET custo_depreciacao = custo_minuto * NEW.tempo_impressao;
    SET NEW.depreciacao = custo_depreciacao;

    -- Busca valor do kWh
    SELECT valor_kwh INTO custo_kwh
    FROM energia WHERE usuario_id = NEW.usuario_id;

    SET tempo_horas = NEW.tempo_impressao / 60;

    SET NEW.valor_energia = custo_kwh;
    SET NEW.custo_energia = (potencia_watts * tempo_horas * fator_uso_impressora * custo_kwh) / 1000;

    -- Log dos valores usados no cálculo
    INSERT INTO impressoes_trigger_log
    (impressao_id, evento, custo_energia, potencia_watts, tempo_horas, fator_uso, custo_kwh, custo_hora, custo_minuto, custo_depreciacao, tempo_impressao)
    VALUES (
        NEW.id,
        'UPDATE',
        NEW.custo_energia,
        potencia_watts,
        tempo_horas,
        fator_uso_impressora,
        custo_kwh,
        custo_hora_impressora,
        custo_minuto,
        custo_depreciacao,
        NEW.tempo_impressao
    );

    IF tipo_impressora = 'FDM' THEN
        SELECT preco_kilo INTO valor_material FROM filamento WHERE id = NEW.material_id;
        SET NEW.custo_material = NEW.peso_material * (valor_material/1000);
    ELSEIF tipo_impressora = 'Resina' THEN
        SELECT preco_litro INTO valor_material FROM resinas WHERE id = NEW.material_id;
        SET NEW.custo_material = NEW.peso_material * (valor_material/1000);

        SELECT preco_litro INTO preco_litro_alcool FROM alcool WHERE usuario_id = NEW.usuario_id;
        SET NEW.custo_lavagem_alcool = (preco_litro_alcool / 1000) * NEW.peso_material;
    ELSE
        SET NEW.custo_lavagem_alcool = NULL;
    END IF;
END;
//

DELIMITER ;