<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/autoload.php';

use App\Fornecedores\FornecedorController;

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$erro = '';
$fornecedorController = new FornecedorController($pdo);
$dadosFormulario = $fornecedorController->montarEstadoFormularioAdicao($_POST ?? []);

function old(string $key, string $default = ''): string {
  global $dadosFormulario;
  return htmlspecialchars((string) ($dadosFormulario[$key] ?? $default), ENT_QUOTES, 'UTF-8');
}

function selected(string $key, string $value, string $default = ''): string {
  global $dadosFormulario;
  $current = (string) ($dadosFormulario[$key] ?? $default);
  return $current === $value ? 'selected' : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $resultado = $fornecedorController->processarCriacao((int) $usuario_id, $_POST);
  if (!empty($resultado['sucesso'])) {
    echo '<script>window.location.href="?pagina=fornecedores";</script>';
    exit;
  }

  $erro = (string) ($resultado['erro'] ?? 'Erro ao cadastrar.');
}
?>
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Adicionar Fornecedor</h3>
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
          <input type="text" class="form-control" id="nome_fantasia" name="nome_fantasia" required value="<?= old('nome_fantasia') ?>">
        </div>
        <div class="form-group col-md-6">
          <label for="razao_social">Razão Social</label>
          <input type="text" class="form-control" id="razao_social" name="razao_social" value="<?= old('razao_social') ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group col-md-6">
          <label for="cnpj_cpf">CNPJ/CPF</label>
          <input type="text" class="form-control" id="cnpj_cpf" name="cnpj_cpf" maxlength="18" inputmode="numeric" placeholder="000.000.000-00 ou 00.000.000/0000-00" value="<?= old('cnpj_cpf') ?>">
        </div>
        <div class="form-group col-md-6">
          <label for="categoria_ramo">Categoria/Ramo de Atividade</label>
          <input type="text" class="form-control" id="categoria_ramo" name="categoria_ramo" placeholder="Ex: Embalagens, Matéria-prima, Manutenção" value="<?= old('categoria_ramo') ?>">
        </div>
      </div>

      <hr>
      <h5>Informações de Contato</h5>
      <div class="form-row">
        <div class="form-group col-md-6">
          <label for="vendedor">Nome do Consultor/Vendedor</label>
          <input type="text" class="form-control" id="vendedor" name="vendedor" value="<?= old('vendedor') ?>">
        </div>
        <div class="form-group col-md-6">
          <label for="whatsapp">WhatsApp/Telefone Direto</label>
          <input type="text" class="form-control" id="whatsapp" name="whatsapp" placeholder="(00) 00000-0000" maxlength="15" inputmode="numeric" value="<?= old('whatsapp') ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group col-md-6">
          <label for="telefone_fixo">Telefone Fixo</label>
          <input type="text" class="form-control" id="telefone_fixo" name="telefone_fixo" placeholder="(00) 0000-0000" maxlength="14" inputmode="numeric" value="<?= old('telefone_fixo') ?>">
        </div>
        <div class="form-group col-md-6">
          <label for="email_pedidos">E-mail de Pedidos</label>
          <input type="email" class="form-control" id="email_pedidos" name="email_pedidos" placeholder="compras@fornecedor.com.br" value="<?= old('email_pedidos') ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group col-md-12">
          <label for="site">Site</label>
          <input type="url" class="form-control" id="site" name="site" placeholder="https://www.fornecedor.com.br" value="<?= old('site') ?>">
        </div>
      </div>

      <hr>
      <h5>Endereço Completo</h5>
      <div class="form-row">
        <div class="form-group col-md-4">
          <label for="cep">CEP</label>
          <input type="text" class="form-control" id="cep" name="cep" placeholder="00000-000" maxlength="9" inputmode="numeric" value="<?= old('cep') ?>">
        </div>
        <div class="form-group col-md-8">
          <label for="logradouro">Logradouro</label>
          <input type="text" class="form-control" id="logradouro" name="logradouro" placeholder="Rua, Avenida, Praça..." value="<?= old('logradouro') ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group col-md-2">
          <label for="numero">Número</label>
          <input type="text" class="form-control" id="numero" name="numero" placeholder="Ex: 123 ou S/N" value="<?= old('numero') ?>">
        </div>
        <div class="form-group col-md-4">
          <label for="complemento">Complemento</label>
          <input type="text" class="form-control" id="complemento" name="complemento" placeholder="Apto, bloco, sala..." value="<?= old('complemento') ?>">
        </div>
        <div class="form-group col-md-3">
          <label for="bairro">Bairro</label>
          <input type="text" class="form-control" id="bairro" name="bairro" value="<?= old('bairro') ?>">
        </div>
        <div class="form-group col-md-2">
          <label for="cidade">Cidade</label>
          <input type="text" class="form-control" id="cidade" name="cidade" value="<?= old('cidade') ?>">
        </div>
        <div class="form-group col-md-1">
          <label for="estado_uf">UF</label>
          <select class="form-control" id="estado_uf" name="estado_uf">
            <option value="" <?= selected('estado_uf', '') ?>></option>
            <option value="AC" <?= selected('estado_uf', 'AC') ?>>AC</option>
            <option value="AL" <?= selected('estado_uf', 'AL') ?>>AL</option>
            <option value="AP" <?= selected('estado_uf', 'AP') ?>>AP</option>
            <option value="AM" <?= selected('estado_uf', 'AM') ?>>AM</option>
            <option value="BA" <?= selected('estado_uf', 'BA') ?>>BA</option>
            <option value="CE" <?= selected('estado_uf', 'CE') ?>>CE</option>
            <option value="DF" <?= selected('estado_uf', 'DF') ?>>DF</option>
            <option value="ES" <?= selected('estado_uf', 'ES') ?>>ES</option>
            <option value="GO" <?= selected('estado_uf', 'GO') ?>>GO</option>
            <option value="MA" <?= selected('estado_uf', 'MA') ?>>MA</option>
            <option value="MT" <?= selected('estado_uf', 'MT') ?>>MT</option>
            <option value="MS" <?= selected('estado_uf', 'MS') ?>>MS</option>
            <option value="MG" <?= selected('estado_uf', 'MG') ?>>MG</option>
            <option value="PA" <?= selected('estado_uf', 'PA') ?>>PA</option>
            <option value="PB" <?= selected('estado_uf', 'PB') ?>>PB</option>
            <option value="PR" <?= selected('estado_uf', 'PR') ?>>PR</option>
            <option value="PE" <?= selected('estado_uf', 'PE') ?>>PE</option>
            <option value="PI" <?= selected('estado_uf', 'PI') ?>>PI</option>
            <option value="RJ" <?= selected('estado_uf', 'RJ') ?>>RJ</option>
            <option value="RN" <?= selected('estado_uf', 'RN') ?>>RN</option>
            <option value="RS" <?= selected('estado_uf', 'RS') ?>>RS</option>
            <option value="RO" <?= selected('estado_uf', 'RO') ?>>RO</option>
            <option value="RR" <?= selected('estado_uf', 'RR') ?>>RR</option>
            <option value="SC" <?= selected('estado_uf', 'SC') ?>>SC</option>
            <option value="SP" <?= selected('estado_uf', 'SP') ?>>SP</option>
            <option value="SE" <?= selected('estado_uf', 'SE') ?>>SE</option>
            <option value="TO" <?= selected('estado_uf', 'TO') ?>>TO</option>
          </select>
        </div>
      </div>

      <hr>
      <h5>Dados Logísticos e Comerciais</h5>
      <div class="form-row">
        <div class="form-group col-md-6">
          <label for="prazo_entrega_medio">Prazo de Entrega Médio</label>
          <input type="text" class="form-control" id="prazo_entrega_medio" name="prazo_entrega_medio" placeholder="Ex: 7 dias úteis" value="<?= old('prazo_entrega_medio') ?>">
        </div>
        <div class="form-group col-md-6">
          <label for="pedido_minimo">Pedido Mínimo (Lote Mínimo)</label>
          <input type="text" class="form-control" id="pedido_minimo" name="pedido_minimo" placeholder="Ex: R$ 500,00 ou 100 unidades" value="<?= old('pedido_minimo') ?>">
        </div>
      </div>
      <div class="form-group">
        <label for="condicoes_pagamento">Condições de Pagamento</label>
        <textarea class="form-control" id="condicoes_pagamento" name="condicoes_pagamento" placeholder="Ex: Boleto 30 dias, PIX com desconto, Cartão"><?= old('condicoes_pagamento') ?></textarea>
      </div>
      <div class="form-row">
        <div class="form-group col-md-6">
          <label for="dados_bancarios">Dados Bancários</label>
          <textarea class="form-control" id="dados_bancarios" name="dados_bancarios"><?= old('dados_bancarios') ?></textarea>
        </div>
        <div class="form-group col-md-6">
          <label for="chave_pix">Chave PIX</label>
          <input type="text" class="form-control" id="chave_pix" name="chave_pix" value="<?= old('chave_pix') ?>">
        </div>
      </div>

      <hr>
      <h5>Notas e Avaliação</h5>
      <div class="form-row">
        <div class="form-group col-md-4">
          <label for="qualidade">Qualidade (Escala 1 a 5)</label>
          <select class="form-control" id="qualidade" name="qualidade">
            <option value="0" <?= selected('qualidade', '0', '0') ?>>Não avaliado</option>
            <option value="1" <?= selected('qualidade', '1', '0') ?>>1</option>
            <option value="2" <?= selected('qualidade', '2', '0') ?>>2</option>
            <option value="3" <?= selected('qualidade', '3', '0') ?>>3</option>
            <option value="4" <?= selected('qualidade', '4', '0') ?>>4</option>
            <option value="5" <?= selected('qualidade', '5', '0') ?>>5</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label for="observacoes_gerais">Observações Gerais</label>
        <textarea class="form-control" id="observacoes_gerais" name="observacoes_gerais" placeholder="Ex: não entrega às sextas; falar com a Maria para descontos"><?= old('observacoes_gerais') ?></textarea>
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

  if (!inputCep) {
    return;
  }

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

  inputCep.addEventListener('input', function () {
    this.value = aplicarMascaraCep(this.value);
    if (this.value.replace(/\D/g, '').length < 8) {
      limparEndereco();
    }
  });

  inputCep.addEventListener('blur', buscarCep);

  inputCep.value = aplicarMascaraCep(inputCep.value);
  if (inputCep.value.replace(/\D/g, '').length === 8) {
    buscarCep();
  }

  if (inputWhatsapp) {
    inputWhatsapp.addEventListener('input', function () {
      this.value = aplicarMascaraTelefone(this.value);
    });
    inputWhatsapp.value = aplicarMascaraTelefone(inputWhatsapp.value);
  }

  if (inputCnpjCpf) {
    inputCnpjCpf.addEventListener('input', function () {
      this.value = aplicarMascaraCnpjCpf(this.value);
    });
    inputCnpjCpf.value = aplicarMascaraCnpjCpf(inputCnpjCpf.value);
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
