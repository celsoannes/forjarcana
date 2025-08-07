CREATE TABLE energia (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL UNIQUE,
    valor_ultima_conta DECIMAL(10,2) NOT NULL,
    energia_eletrica INT NOT NULL,
    valor_wh DECIMAL(10,8) NOT NULL,
    ultima_atualizacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DELIMITER //

CREATE TRIGGER energia_before_insert
BEFORE INSERT ON energia
FOR EACH ROW
BEGIN
    SET NEW.valor_wh = NEW.valor_ultima_conta / (NEW.energia_eletrica * 1000);
END;
//

CREATE TRIGGER energia_before_update
BEFORE UPDATE ON energia
FOR EACH ROW
BEGIN
    SET NEW.valor_wh = NEW.valor_ultima_conta / (NEW.energia_eletrica * 1000);
END;
//

DELIMITER ;