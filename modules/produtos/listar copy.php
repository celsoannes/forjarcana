<div class="produtos-grid">
  <div class="produto-card">
    <div class="produto-icon"><i class="fas fa-dragon"></i></div>
    <h2>Miniaturas</h2>
    <p>Gerenciar catálogo de miniaturas de RPG, figuras de ação e colecionáveis</p>
    <div class="produto-actions">
      <a href="?pagina=miniaturas&acao=adicionar" class="btn-sku">+ Adicionar</a>
      <a href="?pagina=miniaturas" class="btn-lista">📋 Listar</a>
    </div>
  </div>

  <div class="produto-card disabled">
    <div class="coming-soon">Em breve</div>
    <div class="produto-icon"><i class="fas fa-toolbox"></i></div>
    <h2>Acessórios</h2>
    <p>Gerencie acessórios relacionados ao seu catálogo de produtos</p>
    <div class="produto-actions">
      <span class="btn-sku disabled-btn">+ Adicionar</span>
      <span class="btn-lista disabled-btn">📋 Listar</span>
    </div>
  </div>

  <div class="produto-card disabled">
    <div class="coming-soon">Em breve</div>
    <div class="produto-icon"><i class="fas fa-map-marked-alt"></i></div>
    <h2>Mapas</h2>
    <p>Gerencie mapas para campanhas, cenários e ambientações</p>
    <div class="produto-actions">
      <span class="btn-sku disabled-btn">+ Adicionar</span>
      <span class="btn-lista disabled-btn">📋 Listar</span>
    </div>
  </div>
</div>

<style>
.produtos-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 20px;
}

.produto-card {
  position: relative;
  background: #fff;
  border-radius: 12px;
  padding: 28px 24px;
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid #e9ecef;
  transition: all 0.25s ease;
  display: flex;
  flex-direction: column;
  min-height: 320px;
}

.produto-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 14px 26px rgba(0, 0, 0, 0.12);
}

.produto-icon {
  font-size: 2.2rem;
  color: #007bff;
  margin-bottom: 14px;
}

.produto-card h2 {
  font-size: 1.35rem;
  font-weight: 600;
  margin-bottom: 10px;
  color: #343a40;
}

.produto-card p {
  color: #6c757d;
  font-size: 0.95rem;
  margin-bottom: 18px;
}

.produto-actions {
  margin-top: auto;
  display: flex;
  gap: 10px;
}

.btn-sku,
.btn-lista {
  flex: 1;
  text-align: center;
  padding: 10px;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  text-decoration: none;
}

.btn-sku {
  background: #007bff;
  color: #fff;
}

.btn-sku:hover {
  background: #0069d9;
  color: #fff;
}

.btn-lista {
  background: #f1f3f5;
  color: #495057;
}

.btn-lista:hover {
  background: #e9ecef;
  color: #343a40;
}

.produto-card.disabled {
  opacity: 0.75;
}

.coming-soon {
  position: absolute;
  top: 12px;
  right: 12px;
  background: #ffc107;
  color: #212529;
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 0.72rem;
  font-weight: 600;
}

.disabled-btn {
  opacity: 0.7;
  cursor: not-allowed;
}
</style>
