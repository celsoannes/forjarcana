<?php
require_once __DIR__ . '/../../app/db.php';

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$id = (int) ($_GET['id'] ?? 0);
$erro = '';

if ($id <= 0) {
	header('Location: /404.php');
	exit;
}

$stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE id = ? AND usuario_id = ? LIMIT 1");
$stmt->execute([$id, $usuario_id]);
$fornecedor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$fornecedor) {
	header('Location: /404.php');
	exit;
}

function valorCampo(string $key, array $fornecedor, string $default = ''): string {
	$valor = $_POST[$key] ?? ($fornecedor[$key] ?? $default);
	return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function selectedCampo(string $key, string $value, array $fornecedor, string $default = ''): string {
	$current = (string) ($_POST[$key] ?? ($fornecedor[$key] ?? $default));
	return $current === $value ? 'selected' : '';
}

function validarCpf(string $cpf): bool {
	$cpf = preg_replace('/\D/', '', $cpf);
	if (strlen($cpf) !== 11) {
		return false;
	}
	if (preg_match('/^(\d)\1{10}$/', $cpf)) {
		return false;
	}

	for ($t = 9; $t < 11; $t++) {
		$soma = 0;
		for ($i = 0; $i < $t; $i++) {
			$soma += (int) $cpf[$i] * (($t + 1) - $i);
		}
		$digito = ((10 * $soma) % 11) % 10;
		if ((int) $cpf[$t] !== $digito) {
			return false;
		}
	}

	return true;
}

function validarCnpj(string $cnpj): bool {
	$cnpj = preg_replace('/\D/', '', $cnpj);
	if (strlen($cnpj) !== 14) {
		return false;
	}
	if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
		return false;
	}

	$pesos1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
	$pesos2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

	$soma = 0;
	for ($i = 0; $i < 12; $i++) {
		$soma += (int) $cnpj[$i] * $pesos1[$i];
	}
	$resto = $soma % 11;
	$digito1 = $resto < 2 ? 0 : 11 - $resto;
	if ((int) $cnpj[12] !== $digito1) {
		return false;
	}

	$soma = 0;
	for ($i = 0; $i < 13; $i++) {
		$soma += (int) $cnpj[$i] * $pesos2[$i];
	}
	$resto = $soma % 11;
	$digito2 = $resto < 2 ? 0 : 11 - $resto;

	return (int) $cnpj[13] === $digito2;
}

function validarCpfCnpj(string $documento): bool {
	$somenteDigitos = preg_replace('/\D/', '', $documento);
	if ($somenteDigitos === '') {
		return true;
	}
	if (strlen($somenteDigitos) === 11) {
		return validarCpf($somenteDigitos);
	}
	if (strlen($somenteDigitos) === 14) {
		return validarCnpj($somenteDigitos);
	}
	return false;
}

function validarEmailPedidos(string $email): bool {
	$email = trim($email);
	if ($email === '') {
		return true;
	}
	if (strlen($email) > 150) {
		return false;
	}
	return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$nome_fantasia = trim($_POST['nome_fantasia'] ?? '');
	$razao_social = trim($_POST['razao_social'] ?? '');
	$cnpj_cpf = trim($_POST['cnpj_cpf'] ?? '');
	$categoria_ramo = trim($_POST['categoria_ramo'] ?? '');
	$vendedor = trim($_POST['vendedor'] ?? '');
	$whatsapp = trim($_POST['whatsapp'] ?? '');
	$telefone_fixo = trim($_POST['telefone_fixo'] ?? '');
	$email_pedidos = trim($_POST['email_pedidos'] ?? '');
	$site = trim($_POST['site'] ?? '');
	$cep = trim($_POST['cep'] ?? '');
	$logradouro = trim($_POST['logradouro'] ?? '');
	$numero = trim($_POST['numero'] ?? '');
	$complemento = trim($_POST['complemento'] ?? '');
	$bairro = trim($_POST['bairro'] ?? '');
	$cidade = trim($_POST['cidade'] ?? '');
	$estado_uf = trim($_POST['estado_uf'] ?? '');
	$prazo_entrega_medio = trim($_POST['prazo_entrega_medio'] ?? '');
	$pedido_minimo = trim($_POST['pedido_minimo'] ?? '');
	$condicoes_pagamento = trim($_POST['condicoes_pagamento'] ?? '');
	$dados_bancarios = trim($_POST['dados_bancarios'] ?? '');
	$chave_pix = trim($_POST['chave_pix'] ?? '');
	$qualidade = (int) ($_POST['qualidade'] ?? 0);
	$observacoes_gerais = trim($_POST['observacoes_gerais'] ?? '');

	$enderecoPartes = [];
	if ($logradouro !== '') {
		$enderecoPartes[] = $logradouro . ($numero !== '' ? ', ' . $numero : '');
	}
	if ($complemento !== '') {
		$enderecoPartes[] = 'Compl.: ' . $complemento;
	}
	if ($bairro !== '') {
		$enderecoPartes[] = 'Bairro: ' . $bairro;
	}
	if ($cidade !== '' || $estado_uf !== '') {
		$cidadeUf = trim($cidade . ($estado_uf !== '' ? ' - ' . $estado_uf : ''));
		if ($cidadeUf !== '') {
			$enderecoPartes[] = $cidadeUf;
		}
	}
	if ($cep !== '') {
		$enderecoPartes[] = 'CEP: ' . $cep;
	}
	$endereco = implode(' | ', $enderecoPartes);

	if (!$nome_fantasia) {
		$erro = 'Preencha o nome fantasia do fornecedor.';
	} elseif ($cnpj_cpf !== '' && !validarCpfCnpj($cnpj_cpf)) {
		$erro = 'Informe um CPF ou CNPJ válido.';
	} elseif (!validarEmailPedidos($email_pedidos)) {
		$erro = 'Informe um e-mail de pedidos válido.';
	} elseif ($qualidade < 0 || $qualidade > 5) {
		$erro = 'A qualidade deve estar entre 0 e 5.';
	} else {
		try {
			$stmtUpdate = $pdo->prepare("UPDATE fornecedores SET
				nome_fantasia = ?,
				razao_social = ?,
				cnpj_cpf = ?,
				categoria_ramo = ?,
				vendedor = ?,
				whatsapp = ?,
				telefone_fixo = ?,
				email_pedidos = ?,
				site = ?,
				cep = ?,
				logradouro = ?,
				numero = ?,
				complemento = ?,
				bairro = ?,
				cidade = ?,
				estado_uf = ?,
				endereco = ?,
				prazo_entrega_medio = ?,
				pedido_minimo = ?,
				condicoes_pagamento = ?,
				dados_bancarios = ?,
				chave_pix = ?,
				qualidade = ?,
				observacoes_gerais = ?,
				ultima_atualizacao = NOW()
			WHERE id = ? AND usuario_id = ?");

			$stmtUpdate->execute([
				$nome_fantasia,
				$razao_social,
				$cnpj_cpf,
				$categoria_ramo,
				$vendedor,
				$whatsapp,
				$telefone_fixo,
				$email_pedidos,
				$site,
				$cep,
				$logradouro,
				$numero,
				$complemento,
				$bairro,
				$cidade,
				$estado_uf,
				$endereco,
				$prazo_entrega_medio,
				$pedido_minimo,
				$condicoes_pagamento,
				$dados_bancarios,
				$chave_pix,
				$qualidade ?: null,
				$observacoes_gerais,
				$id,
				$usuario_id
			]);

			echo '<script>window.location.href="?pagina=fornecedores";</script>';
			exit;
		} catch (PDOException $e) {
			$erro = 'Erro ao editar: ' . $e->getMessage();
		}
	}
}
?>

<div class="card card-primary">
	<div class="card-header">
		<h3 class="card-title">Editar Fornecedor</h3>
	</div>
	<form method="POST">
		<div class="card-body">
			<?php if ($erro): ?>
				<div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
			<?php endif; ?>

			<h5>Identificação Básica</h5>
			<div class="form-row">
				<div class="form-group col-md-6">
					<label for="nome_fantasia">Nome Fantasia</label>
					<input type="text" class="form-control" id="nome_fantasia" name="nome_fantasia" required value="<?= valorCampo('nome_fantasia', $fornecedor) ?>">
				</div>
				<div class="form-group col-md-6">
					<label for="razao_social">Razão Social</label>
					<input type="text" class="form-control" id="razao_social" name="razao_social" value="<?= valorCampo('razao_social', $fornecedor) ?>">
				</div>
			</div>
			<div class="form-row">
				<div class="form-group col-md-6">
					<label for="cnpj_cpf">CNPJ/CPF</label>
					<input type="text" class="form-control" id="cnpj_cpf" name="cnpj_cpf" maxlength="18" inputmode="numeric" placeholder="000.000.000-00 ou 00.000.000/0000-00" value="<?= valorCampo('cnpj_cpf', $fornecedor) ?>">
				</div>
				<div class="form-group col-md-6">
					<label for="categoria_ramo">Categoria/Ramo de Atividade</label>
					<input type="text" class="form-control" id="categoria_ramo" name="categoria_ramo" placeholder="Ex: Embalagens, Matéria-prima, Manutenção" value="<?= valorCampo('categoria_ramo', $fornecedor) ?>">
				</div>
			</div>

			<hr>
			<h5>Informações de Contato</h5>
			<div class="form-row">
				<div class="form-group col-md-6">
					<label for="vendedor">Nome do Consultor/Vendedor</label>
					<input type="text" class="form-control" id="vendedor" name="vendedor" value="<?= valorCampo('vendedor', $fornecedor) ?>">
				</div>
				<div class="form-group col-md-6">
					<label for="whatsapp">WhatsApp/Telefone Direto</label>
					<input type="text" class="form-control" id="whatsapp" name="whatsapp" placeholder="(00) 00000-0000" maxlength="15" inputmode="numeric" value="<?= valorCampo('whatsapp', $fornecedor) ?>">
				</div>
			</div>
			<div class="form-row">
				<div class="form-group col-md-6">
					<label for="telefone_fixo">Telefone Fixo</label>
					<input type="text" class="form-control" id="telefone_fixo" name="telefone_fixo" placeholder="(00) 0000-0000" maxlength="14" inputmode="numeric" value="<?= valorCampo('telefone_fixo', $fornecedor) ?>">
				</div>
				<div class="form-group col-md-6">
					<label for="email_pedidos">E-mail de Pedidos</label>
					<input type="email" class="form-control" id="email_pedidos" name="email_pedidos" placeholder="compras@fornecedor.com.br" value="<?= valorCampo('email_pedidos', $fornecedor) ?>">
				</div>
			</div>
			<div class="form-row">
				<div class="form-group col-md-12">
					<label for="site">Site</label>
					<input type="url" class="form-control" id="site" name="site" placeholder="https://www.fornecedor.com.br" value="<?= valorCampo('site', $fornecedor) ?>">
				</div>
			</div>

			<hr>
			<h5>Endereço Completo</h5>
			<div class="form-row">
				<div class="form-group col-md-4">
					<label for="cep">CEP</label>
					<input type="text" class="form-control" id="cep" name="cep" placeholder="00000-000" maxlength="9" inputmode="numeric" value="<?= valorCampo('cep', $fornecedor) ?>">
				</div>
				<div class="form-group col-md-8">
					<label for="logradouro">Logradouro</label>
					<input type="text" class="form-control" id="logradouro" name="logradouro" placeholder="Rua, Avenida, Praça..." value="<?= valorCampo('logradouro', $fornecedor) ?>">
				</div>
			</div>
			<div class="form-row">
				<div class="form-group col-md-2">
					<label for="numero">Número</label>
					<input type="text" class="form-control" id="numero" name="numero" placeholder="Ex: 123 ou S/N" value="<?= valorCampo('numero', $fornecedor) ?>">
				</div>
				<div class="form-group col-md-4">
					<label for="complemento">Complemento</label>
					<input type="text" class="form-control" id="complemento" name="complemento" placeholder="Apto, bloco, sala..." value="<?= valorCampo('complemento', $fornecedor) ?>">
				</div>
				<div class="form-group col-md-3">
					<label for="bairro">Bairro</label>
					<input type="text" class="form-control" id="bairro" name="bairro" value="<?= valorCampo('bairro', $fornecedor) ?>">
				</div>
				<div class="form-group col-md-2">
					<label for="cidade">Cidade</label>
					<input type="text" class="form-control" id="cidade" name="cidade" value="<?= valorCampo('cidade', $fornecedor) ?>">
				</div>
				<div class="form-group col-md-1">
					<label for="estado_uf">UF</label>
					<select class="form-control" id="estado_uf" name="estado_uf">
						<option value="" <?= selectedCampo('estado_uf', '', $fornecedor) ?>></option>
						<option value="AC" <?= selectedCampo('estado_uf', 'AC', $fornecedor) ?>>AC</option>
						<option value="AL" <?= selectedCampo('estado_uf', 'AL', $fornecedor) ?>>AL</option>
						<option value="AP" <?= selectedCampo('estado_uf', 'AP', $fornecedor) ?>>AP</option>
						<option value="AM" <?= selectedCampo('estado_uf', 'AM', $fornecedor) ?>>AM</option>
						<option value="BA" <?= selectedCampo('estado_uf', 'BA', $fornecedor) ?>>BA</option>
						<option value="CE" <?= selectedCampo('estado_uf', 'CE', $fornecedor) ?>>CE</option>
						<option value="DF" <?= selectedCampo('estado_uf', 'DF', $fornecedor) ?>>DF</option>
						<option value="ES" <?= selectedCampo('estado_uf', 'ES', $fornecedor) ?>>ES</option>
						<option value="GO" <?= selectedCampo('estado_uf', 'GO', $fornecedor) ?>>GO</option>
						<option value="MA" <?= selectedCampo('estado_uf', 'MA', $fornecedor) ?>>MA</option>
						<option value="MT" <?= selectedCampo('estado_uf', 'MT', $fornecedor) ?>>MT</option>
						<option value="MS" <?= selectedCampo('estado_uf', 'MS', $fornecedor) ?>>MS</option>
						<option value="MG" <?= selectedCampo('estado_uf', 'MG', $fornecedor) ?>>MG</option>
						<option value="PA" <?= selectedCampo('estado_uf', 'PA', $fornecedor) ?>>PA</option>
						<option value="PB" <?= selectedCampo('estado_uf', 'PB', $fornecedor) ?>>PB</option>
						<option value="PR" <?= selectedCampo('estado_uf', 'PR', $fornecedor) ?>>PR</option>
						<option value="PE" <?= selectedCampo('estado_uf', 'PE', $fornecedor) ?>>PE</option>
						<option value="PI" <?= selectedCampo('estado_uf', 'PI', $fornecedor) ?>>PI</option>
						<option value="RJ" <?= selectedCampo('estado_uf', 'RJ', $fornecedor) ?>>RJ</option>
						<option value="RN" <?= selectedCampo('estado_uf', 'RN', $fornecedor) ?>>RN</option>
						<option value="RS" <?= selectedCampo('estado_uf', 'RS', $fornecedor) ?>>RS</option>
						<option value="RO" <?= selectedCampo('estado_uf', 'RO', $fornecedor) ?>>RO</option>
						<option value="RR" <?= selectedCampo('estado_uf', 'RR', $fornecedor) ?>>RR</option>
						<option value="SC" <?= selectedCampo('estado_uf', 'SC', $fornecedor) ?>>SC</option>
						<option value="SP" <?= selectedCampo('estado_uf', 'SP', $fornecedor) ?>>SP</option>
						<option value="SE" <?= selectedCampo('estado_uf', 'SE', $fornecedor) ?>>SE</option>
						<option value="TO" <?= selectedCampo('estado_uf', 'TO', $fornecedor) ?>>TO</option>
					</select>
				</div>
			</div>

			<hr>
			<h5>Dados Logísticos e Comerciais</h5>
			<div class="form-row">
				<div class="form-group col-md-6">
					<label for="prazo_entrega_medio">Prazo de Entrega Médio</label>
					<input type="text" class="form-control" id="prazo_entrega_medio" name="prazo_entrega_medio" placeholder="Ex: 7 dias úteis" value="<?= valorCampo('prazo_entrega_medio', $fornecedor) ?>">
				</div>
				<div class="form-group col-md-6">
					<label for="pedido_minimo">Pedido Mínimo (Lote Mínimo)</label>
					<input type="text" class="form-control" id="pedido_minimo" name="pedido_minimo" placeholder="Ex: R$ 500,00 ou 100 unidades" value="<?= valorCampo('pedido_minimo', $fornecedor) ?>">
				</div>
			</div>
			<div class="form-group">
				<label for="condicoes_pagamento">Condições de Pagamento</label>
				<textarea class="form-control" id="condicoes_pagamento" name="condicoes_pagamento" placeholder="Ex: Boleto 30 dias, PIX com desconto, Cartão"><?= valorCampo('condicoes_pagamento', $fornecedor) ?></textarea>
			</div>
			<div class="form-row">
				<div class="form-group col-md-6">
					<label for="dados_bancarios">Dados Bancários</label>
					<textarea class="form-control" id="dados_bancarios" name="dados_bancarios"><?= valorCampo('dados_bancarios', $fornecedor) ?></textarea>
				</div>
				<div class="form-group col-md-6">
					<label for="chave_pix">Chave PIX</label>
					<input type="text" class="form-control" id="chave_pix" name="chave_pix" value="<?= valorCampo('chave_pix', $fornecedor) ?>">
				</div>
			</div>

			<hr>
			<h5>Notas e Avaliação</h5>
			<div class="form-row">
				<div class="form-group col-md-4">
					<label for="qualidade">Qualidade (Escala 1 a 5)</label>
					<select class="form-control" id="qualidade" name="qualidade">
						<option value="0" <?= selectedCampo('qualidade', '0', $fornecedor, '0') ?>>Não avaliado</option>
						<option value="1" <?= selectedCampo('qualidade', '1', $fornecedor, '0') ?>>1</option>
						<option value="2" <?= selectedCampo('qualidade', '2', $fornecedor, '0') ?>>2</option>
						<option value="3" <?= selectedCampo('qualidade', '3', $fornecedor, '0') ?>>3</option>
						<option value="4" <?= selectedCampo('qualidade', '4', $fornecedor, '0') ?>>4</option>
						<option value="5" <?= selectedCampo('qualidade', '5', $fornecedor, '0') ?>>5</option>
					</select>
				</div>
			</div>
			<div class="form-group">
				<label for="observacoes_gerais">Observações Gerais</label>
				<textarea class="form-control" id="observacoes_gerais" name="observacoes_gerais" placeholder="Ex: não entrega às sextas; falar com a Maria para descontos"><?= valorCampo('observacoes_gerais', $fornecedor) ?></textarea>
			</div>
		</div>
		<div class="card-footer">
			<button type="submit" class="btn btn-primary">Salvar</button>
			<a href="?pagina=fornecedores" class="btn btn-secondary">Cancelar</a>
		</div>
	</form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
	var inputCep = document.getElementById('cep');
	var inputCnpjCpf = document.getElementById('cnpj_cpf');
	var inputWhatsapp = document.getElementById('whatsapp');
	var inputTelefoneFixo = document.getElementById('telefone_fixo');
	var inputEmailPedidos = document.getElementById('email_pedidos');
	var inputLogradouro = document.getElementById('logradouro');
	var inputBairro = document.getElementById('bairro');
	var inputCidade = document.getElementById('cidade');
	var selectUf = document.getElementById('estado_uf');

	var aplicarMascaraCep = function (valor) {
		var apenasNumeros = (valor || '').replace(/\D/g, '').slice(0, 8);
		if (apenasNumeros.length <= 5) {
			return apenasNumeros;
		}
		return apenasNumeros.slice(0, 5) + '-' + apenasNumeros.slice(5);
	};

	var aplicarMascaraTelefone = function (valor) {
		var apenasNumeros = (valor || '').replace(/\D/g, '').slice(0, 11);
		if (apenasNumeros.length <= 2) {
			return apenasNumeros;
		}
		if (apenasNumeros.length <= 6) {
			return '(' + apenasNumeros.slice(0, 2) + ') ' + apenasNumeros.slice(2);
		}
		if (apenasNumeros.length <= 10) {
			return '(' + apenasNumeros.slice(0, 2) + ') ' + apenasNumeros.slice(2, 6) + '-' + apenasNumeros.slice(6);
		}
		return '(' + apenasNumeros.slice(0, 2) + ') ' + apenasNumeros.slice(2, 7) + '-' + apenasNumeros.slice(7);
	};

	var aplicarMascaraCnpjCpf = function (valor) {
		var apenasNumeros = (valor || '').replace(/\D/g, '').slice(0, 14);

		if (apenasNumeros.length <= 11) {
			if (apenasNumeros.length <= 3) return apenasNumeros;
			if (apenasNumeros.length <= 6) return apenasNumeros.slice(0, 3) + '.' + apenasNumeros.slice(3);
			if (apenasNumeros.length <= 9) return apenasNumeros.slice(0, 3) + '.' + apenasNumeros.slice(3, 6) + '.' + apenasNumeros.slice(6);
			return apenasNumeros.slice(0, 3) + '.' + apenasNumeros.slice(3, 6) + '.' + apenasNumeros.slice(6, 9) + '-' + apenasNumeros.slice(9);
		}

		if (apenasNumeros.length <= 2) return apenasNumeros;
		if (apenasNumeros.length <= 5) return apenasNumeros.slice(0, 2) + '.' + apenasNumeros.slice(2);
		if (apenasNumeros.length <= 8) return apenasNumeros.slice(0, 2) + '.' + apenasNumeros.slice(2, 5) + '.' + apenasNumeros.slice(5);
		if (apenasNumeros.length <= 12) return apenasNumeros.slice(0, 2) + '.' + apenasNumeros.slice(2, 5) + '.' + apenasNumeros.slice(5, 8) + '/' + apenasNumeros.slice(8);
		return apenasNumeros.slice(0, 2) + '.' + apenasNumeros.slice(2, 5) + '.' + apenasNumeros.slice(5, 8) + '/' + apenasNumeros.slice(8, 12) + '-' + apenasNumeros.slice(12);
	};

	var limparEndereco = function () {
		if (inputLogradouro) inputLogradouro.value = '';
		if (inputBairro) inputBairro.value = '';
		if (inputCidade) inputCidade.value = '';
		if (selectUf) selectUf.value = '';
	};

	var buscarCep = function () {
		if (!inputCep) {
			return;
		}

		var cepNumerico = inputCep.value.replace(/\D/g, '');
		if (cepNumerico.length !== 8) {
			return;
		}

		fetch('https://viacep.com.br/ws/' + cepNumerico + '/json/')
			.then(function (response) { return response.json(); })
			.then(function (data) {
				if (!data || data.erro) {
					return;
				}

				if (inputLogradouro && !inputLogradouro.value.trim()) {
					inputLogradouro.value = data.logradouro || '';
				}
				if (inputBairro && !inputBairro.value.trim()) {
					inputBairro.value = data.bairro || '';
				}
				if (inputCidade && !inputCidade.value.trim()) {
					inputCidade.value = data.localidade || '';
				}
				if (selectUf && !selectUf.value) {
					selectUf.value = data.uf || '';
				}
			})
			.catch(function () {
			});
	};

	if (inputCep) {
		inputCep.addEventListener('input', function () {
			this.value = aplicarMascaraCep(this.value);
			if (this.value.replace(/\D/g, '').length < 8) {
				limparEndereco();
			}
		});

		inputCep.addEventListener('blur', buscarCep);
		inputCep.value = aplicarMascaraCep(inputCep.value);
	}

	if (inputWhatsapp) {
		inputWhatsapp.addEventListener('input', function () {
			this.value = aplicarMascaraTelefone(this.value);
		});
		inputWhatsapp.value = aplicarMascaraTelefone(inputWhatsapp.value);
	}

	if (inputTelefoneFixo) {
		inputTelefoneFixo.addEventListener('input', function () {
			var apenasNumeros = (this.value || '').replace(/\D/g, '').slice(0, 10);
			if (apenasNumeros.length <= 2) {
				this.value = apenasNumeros;
			} else if (apenasNumeros.length <= 6) {
				this.value = '(' + apenasNumeros.slice(0, 2) + ') ' + apenasNumeros.slice(2);
			} else {
				this.value = '(' + apenasNumeros.slice(0, 2) + ') ' + apenasNumeros.slice(2, 6) + '-' + apenasNumeros.slice(6);
			}
		});
	}

	if (inputCnpjCpf) {
		inputCnpjCpf.addEventListener('input', function () {
			this.value = aplicarMascaraCnpjCpf(this.value);
		});
		inputCnpjCpf.value = aplicarMascaraCnpjCpf(inputCnpjCpf.value);
	}

	if (inputEmailPedidos) {
		inputEmailPedidos.addEventListener('input', function () {
			this.value = (this.value || '').replace(/\s+/g, '').toLowerCase();
			this.setCustomValidity('');
		});

		inputEmailPedidos.addEventListener('blur', function () {
			var valor = (this.value || '').trim();
			if (valor === '') {
				this.setCustomValidity('');
				return;
			}

			var valido = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valor);
			this.setCustomValidity(valido ? '' : 'Informe um e-mail de pedidos válido.');
		});
	}
});
</script>
