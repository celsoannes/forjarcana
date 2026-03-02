ALTER TABLE fornecedores
    ADD COLUMN nome_fantasia VARCHAR(150) NULL AFTER id,
    ADD COLUMN razao_social VARCHAR(200) NULL AFTER nome_fantasia,
    ADD COLUMN cnpj_cpf VARCHAR(30) NULL AFTER razao_social,
    ADD COLUMN categoria_ramo VARCHAR(150) NULL AFTER cnpj_cpf,
    ADD COLUMN vendedor VARCHAR(150) NULL AFTER categoria_ramo,
    ADD COLUMN whatsapp VARCHAR(60) NULL AFTER vendedor,
    ADD COLUMN telefone_fixo VARCHAR(60) NULL AFTER whatsapp,
    ADD COLUMN email_pedidos VARCHAR(150) NULL AFTER telefone_fixo,
    ADD COLUMN site VARCHAR(255) NULL AFTER email_pedidos,
    ADD COLUMN cep VARCHAR(9) NULL AFTER site,
    ADD COLUMN logradouro VARCHAR(255) NULL AFTER cep,
    ADD COLUMN numero VARCHAR(20) NULL AFTER logradouro,
    ADD COLUMN complemento VARCHAR(120) NULL AFTER numero,
    ADD COLUMN bairro VARCHAR(120) NULL AFTER complemento,
    ADD COLUMN cidade VARCHAR(120) NULL AFTER bairro,
    ADD COLUMN estado_uf CHAR(2) NULL AFTER cidade,
    ADD COLUMN endereco TEXT NULL AFTER estado_uf,
    ADD COLUMN prazo_entrega_medio VARCHAR(120) NULL AFTER endereco,
    ADD COLUMN pedido_minimo VARCHAR(120) NULL AFTER prazo_entrega_medio,
    ADD COLUMN condicoes_pagamento TEXT NULL AFTER pedido_minimo,
    ADD COLUMN dados_bancarios TEXT NULL AFTER condicoes_pagamento,
    ADD COLUMN chave_pix VARCHAR(180) NULL AFTER dados_bancarios,
    ADD COLUMN qualidade TINYINT UNSIGNED NULL AFTER chave_pix,
    ADD COLUMN observacoes_gerais TEXT NULL AFTER qualidade;

UPDATE fornecedores
SET
    nome_fantasia = COALESCE(NULLIF(nome_fantasia, ''), nome),
    vendedor = COALESCE(NULLIF(vendedor, ''), contato),
    whatsapp = COALESCE(NULLIF(whatsapp, ''), telefone),
    email_pedidos = COALESCE(NULLIF(email_pedidos, ''), email),
    observacoes_gerais = COALESCE(NULLIF(observacoes_gerais, ''), observacoes)
WHERE 1=1;

ALTER TABLE fornecedores
    MODIFY COLUMN nome_fantasia VARCHAR(150) NOT NULL;