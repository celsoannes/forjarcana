CREATE TABLE impressoes_trigger_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    impressao_id INT UNSIGNED,
    evento VARCHAR(10), -- 'INSERT' ou 'UPDATE'
    custo_energia DECIMAL(10,2),
    potencia_watts INT,
    tempo_horas DECIMAL(10,4),
    fator_uso DECIMAL(4,2),
    custo_kwh DECIMAL(10,8),
    data_log DATETIME DEFAULT CURRENT_TIMESTAMP
);