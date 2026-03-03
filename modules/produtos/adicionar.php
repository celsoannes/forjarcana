<div class="card">
  <div class="card-header">
    <h3 class="card-title">Escolha a categoria do produto</h3>
  </div>
  <div class="card-body">
    <div class="produtos-grid">
      <div class="produto-card">
        <div class="produto-icon"><i class="fas fa-dragon"></i></div>
        <h2>Miniaturas</h2>
        <p>Escolha esta categoria para miniaturas de RPG, figuras e colecionáveis.</p>
        <div class="produto-actions">
          <a href="?pagina=impressoes&fluxo=miniaturas" class="btn-escolher">Escolher</a>
        </div>
      </div>

      <div class="produto-card">
        <div class="produto-icon"><i class="fas fa-map-marked-alt"></i></div>
        <h2>Mapas</h2>
        <p>Categoria para mapas de campanha, cenários e ambientações.</p>
        <div class="produto-actions">
          <a href="?pagina=mapas&acao=adicionar" class="btn-escolher">Escolher</a>
        </div>
      </div>

      <div class="produto-card disabled">
        <div class="coming-soon">Em breve</div>
        <div class="produto-icon"><i class="fas fa-dice-d20"></i></div>
        <h2>Dados</h2>
        <p>Categoria para conjuntos de dados e acessórios relacionados.</p>
        <div class="produto-actions">
          <span class="btn-escolher disabled-btn">Escolher</span>
        </div>
      </div>

      <div class="produto-card">
        <div class="produto-icon"><i class="fas fa-dungeon"></i></div>
        <h2>Torre de Dados</h2>
        <p>Categoria para torres de dados em diferentes estilos e materiais.</p>
        <div class="produto-actions">
          <a href="?pagina=impressoes&fluxo=torres" class="btn-escolher">Escolher</a>
        </div>
      </div>

      <div class="produto-card disabled">
        <div class="coming-soon">Em breve</div>
        <div class="produto-icon"><i class="fas fa-key"></i></div>
        <h2>Chaveiros</h2>
        <p>Categoria para chaveiros temáticos e personalizados.</p>
        <div class="produto-actions">
          <span class="btn-escolher disabled-btn">Escolher</span>
        </div>
      </div>

      <div class="produto-card disabled">
        <div class="coming-soon">Em breve</div>
        <div class="produto-icon"><i class="fas fa-image"></i></div>
        <h2>Quadros</h2>
        <p>Categoria para quadros decorativos e artes para coleção.</p>
        <div class="produto-actions">
          <span class="btn-escolher disabled-btn">Escolher</span>
        </div>
      </div>
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
  min-height: 280px;
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

.btn-escolher {
  flex: 1;
  text-align: center;
  padding: 10px;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  text-decoration: none;
  background: #007bff;
  color: #fff;
}

.btn-escolher:hover {
  background: #0069d9;
  color: #fff;
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
