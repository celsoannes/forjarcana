<?php
require_once __DIR__ . '/../../app/db.php';

$produtos = [];
$erro_consulta = null;
$usuario_id = (int) ($_SESSION['usuario_id'] ?? 0);

if ($usuario_id <= 0) {
	$erro_consulta = 'Não foi possível identificar o usuário logado para listar os produtos.';
} else {
	try {
		$stmt = $pdo->prepare("SELECT
				p.id,
				mp.id AS mapa_id,
				s.sku AS sku_codigo,
				c.nome AS categoria_nome,
				COALESCE(NULLIF(p.nome, ''), m.nome_original) AS miniatura_nome,
				p.markup,
				COALESCE(i_m.unidades_produzidas, i_t.unidades_produzidas, 0) AS unidades_produzidas,
				cu.custo_total AS custo_total,
				cu.custo_por_unidade AS custo_unidade,
				p.preco_lojista,
				p.preco_consumidor_final,
				p.lucro_lojista,
				p.lucro_consumidor_final,
				p.imagem_capa,
				p.data_cadastro
			FROM produtos p
			LEFT JOIN sku s ON s.produto_id = p.id
			LEFT JOIN categorias c ON c.id = p.categoria
			LEFT JOIN custos cu ON cu.produto_id = p.id
			LEFT JOIN miniaturas m ON m.produto_id = p.id
			LEFT JOIN mapas mp ON mp.produto_id = p.id AND mp.usuario_id = p.usuario_id
			LEFT JOIN torres t ON t.produto_id = p.id
			LEFT JOIN impressoes i_m ON i_m.id = m.id_impressao
			LEFT JOIN impressoes i_t ON i_t.id = t.id_impressao
			WHERE p.usuario_id = ?
			ORDER BY p.id DESC");
		$stmt->execute([$usuario_id]);
		$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
	} catch (Throwable $e) {
		$erro_consulta = 'Não foi possível carregar os produtos no momento.';
	}
}
?>

<style>
	.preview-capa-hover {
		position: fixed;
		display: none;
		z-index: 1080;
		pointer-events: none;
		background: #fff;
		padding: 6px;
		border: 1px solid #dee2e6;
		border-radius: 6px;
		box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
	}

	.preview-capa-hover img {
		display: block;
		width: 220px;
		height: 220px;
		object-fit: cover;
		border-radius: 4px;
	}

	.img-capa-thumb {
		cursor: zoom-in;
	}
</style>

<div class="card">
	<div class="card-header">
		<h3 class="card-title">Produtos</h3>
		<div class="card-tools">
			<a href="?pagina=produtos&acao=adicionar" class="btn btn-primary float-right">
				<i class="fas fa-plus"></i> Adicionar produto
			</a>
		</div>
	</div>

	<div class="card-body table-responsive p-0">
		<?php if ($erro_consulta): ?>
			<div class="alert alert-warning m-3 mb-0"><?= htmlspecialchars($erro_consulta) ?></div>
		<?php endif; ?>

		<?php if ($produtos): ?>
			<table class="table table-hover text-nowrap">
				<thead>
					<tr>
						<th>Capa</th>
						<th>SKU</th>
						<th>Nome</th>
						<th>Categoria</th>
						<th>Custo</th>
						<th>Markup</th>
						<th>Preço Lojista</th>
						<th>Lucro Lojista</th>
						<th>Preço Consumidor Final</th>
						<th>Lucro Consumidor Final</th>
						<th>Cadastro</th>
						<th class="text-right">Ações</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($produtos as $produto): ?>
						<?php
						$categoriaNome = trim((string) ($produto['categoria_nome'] ?? ''));
						$categoriaNormalizada = function_exists('mb_strtolower')
							? mb_strtolower($categoriaNome, 'UTF-8')
							: strtolower($categoriaNome);

						$hrefEditar = '?pagina=produtos&acao=editar&id=' . (int) $produto['id'];
						if ($categoriaNormalizada === 'mapas' && (int) ($produto['mapa_id'] ?? 0) > 0) {
							$hrefEditar = '?pagina=mapas&acao=editar&id=' . (int) $produto['mapa_id'] . '&fluxo=mapas';
						}
						?>
						<tr>
							<td>
								<?php if (!empty($produto['imagem_capa'])): ?>
									<img src="<?= htmlspecialchars((string) $produto['imagem_capa']) ?>" data-preview-src="<?= htmlspecialchars((string) $produto['imagem_capa']) ?>" alt="Capa" class="img-capa-thumb" style="width:48px; height:48px; object-fit:cover; border-radius:4px; border:1px solid #dee2e6;">
								<?php else: ?>
									<span class="text-muted">-</span>
								<?php endif; ?>
							</td>
							<td><?= htmlspecialchars((string) ($produto['sku_codigo'] ?? '-')) ?></td>
							<td><?= htmlspecialchars((string) ($produto['miniatura_nome'] ?? 'Produto sem nome')) ?></td>
							<td><?= htmlspecialchars((string) ($produto['categoria_nome'] ?? '-')) ?></td>
							<td><span class="badge badge-secondary">R$ <?= number_format((float) ($produto['custo_unidade'] ?? 0), 2, ',', '.') ?></span></td>
							<td><?= number_format((float) ($produto['markup'] ?? 0), 2, ',', '.') ?></td>
							<td><span class="badge badge-primary">R$ <?= number_format((float) ($produto['preco_lojista'] ?? 0), 2, ',', '.') ?></span></td>
							<td><span class="badge badge-info">R$ <?= number_format((float) ($produto['lucro_lojista'] ?? 0), 2, ',', '.') ?></span></td>
							<td><span class="badge badge-success">R$ <?= number_format((float) ($produto['preco_consumidor_final'] ?? 0), 2, ',', '.') ?></span></td>
							<td><span class="badge badge-warning">R$ <?= number_format((float) ($produto['lucro_consumidor_final'] ?? 0), 2, ',', '.') ?></span></td>
							<td>
								<?php if (!empty($produto['data_cadastro'])): ?>
									<?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $produto['data_cadastro']))) ?>
								<?php else: ?>
									<span class="text-muted">-</span>
								<?php endif; ?>
							</td>
							<td class="text-right">
								<a class="btn btn-info btn-sm" href="<?= htmlspecialchars($hrefEditar, ENT_QUOTES, 'UTF-8') ?>">
									<i class="fas fa-pencil-alt"></i> Editar
								</a>
								<a class="btn btn-danger btn-sm btn-excluir-produto" href="?pagina=produtos&acao=excluir&id=<?= (int) $produto['id'] ?>">
									<i class="fas fa-trash"></i> Excluir
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else: ?>
			<div class="text-center p-4">Nenhum produto cadastrado.</div>
		<?php endif; ?>
	</div>
</div>

<div id="preview-capa-hover" class="preview-capa-hover">
	<img src="" alt="Pré-visualização da capa">
</div>

<div class="modal fade" id="modal-danger-excluir-produto" tabindex="-1" role="dialog" aria-labelledby="modalDangerLabelProduto" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content bg-danger">
			<div class="modal-header">
				<h4 class="modal-title" id="modalDangerLabelProduto">Excluir Produto</h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<p id="modal-excluir-texto-produto">Tem certeza que deseja excluir este produto? Esta ação não pode ser desfeita.</p>
				<div id="modal-excluir-erro-produto" class="alert alert-warning d-none"></div>
			</div>
			<div class="modal-footer justify-content-between">
				<button type="button" class="btn btn-outline-light" data-dismiss="modal">Cancelar</button>
				<button type="button" class="btn btn-outline-light" id="btn-confirmar-excluir-produto">Excluir</button>
			</div>
		</div>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
	var preview = document.getElementById('preview-capa-hover');
	if (!preview) {
		return;
	}

	var previewImg = preview.querySelector('img');
	if (!previewImg) {
		return;
	}

	var posicionarPreview = function (evento) {
		var offsetX = 18;
		var offsetY = 18;
		var largura = 232;
		var altura = 232;
		var x = evento.clientX + offsetX;
		var y = evento.clientY + offsetY;

		if (x + largura > window.innerWidth) {
			x = evento.clientX - largura - 12;
		}

		if (y + altura > window.innerHeight) {
			y = evento.clientY - altura - 12;
		}

		preview.style.left = x + 'px';
		preview.style.top = y + 'px';
	};

	document.querySelectorAll('.img-capa-thumb[data-preview-src]').forEach(function (thumb) {
		thumb.addEventListener('mouseenter', function (evento) {
			var src = this.getAttribute('data-preview-src') || '';
			if (!src) {
				return;
			}

			previewImg.src = src;
			preview.style.display = 'block';
			posicionarPreview(evento);
		});

		thumb.addEventListener('mousemove', function (evento) {
			if (preview.style.display === 'block') {
				posicionarPreview(evento);
			}
		});

		thumb.addEventListener('mouseleave', function () {
			preview.style.display = 'none';
			previewImg.src = '';
		});
	});

	var produtoExcluirId = null;
	document.querySelectorAll('.btn-excluir-produto').forEach(function(btn) {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			produtoExcluirId = this.href.split('id=')[1];
			document.getElementById('modal-excluir-erro-produto').classList.add('d-none');
			if (window.jQuery && typeof window.jQuery.fn.modal === 'function') {
				window.jQuery('#modal-danger-excluir-produto').modal('show');
			}
		});
	});

	var btnConfirmarExcluir = document.getElementById('btn-confirmar-excluir-produto');
	if (btnConfirmarExcluir) {
		btnConfirmarExcluir.addEventListener('click', function() {
			if (!produtoExcluirId) {
				return;
			}

			fetch('modules/produtos/excluir.php?id=' + encodeURIComponent(produtoExcluirId), {
				method: 'GET'
			})
			.then(function(response) { return response.text(); })
			.then(function(result) {
				if (result.trim() === '' || result.includes('window.location.href')) {
					location.href = '?pagina=produtos';
				} else {
					document.getElementById('modal-excluir-erro-produto').textContent = result;
					document.getElementById('modal-excluir-erro-produto').classList.remove('d-none');
				}
			});
		});
	}
});
</script>
