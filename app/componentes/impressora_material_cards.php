<?php

if (!function_exists('renderImpressoraMaterialCardsStyles')) {
    function renderImpressoraMaterialCardsStyles(): void
    {
        static $stylesJaRenderizados = false;

        if ($stylesJaRenderizados) {
            return;
        }

        $stylesJaRenderizados = true;

        echo '<style>
          .impressora-material-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
          }

          .impressora-material-card {
            position: relative;
            background: #fff;
            border-radius: 12px;
            padding: 28px 24px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
            display: flex;
            flex-direction: row;
            align-items: flex-start;
            gap: 16px;
            min-height: 180px;
            width: 100%;
          }

          .impressora-material-icon {
            font-size: 2.2rem;
            color: #007bff;
            margin-top: 2px;
            flex-shrink: 0;
          }

          .impressora-material-content {
            flex: 1;
          }

          .impressora-material-content h2 {
            font-size: 1.35rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #343a40;
          }

          .impressora-material-content p {
            color: #6c757d;
            font-size: 0.95rem;
            margin-bottom: 0;
          }
        </style>';
    }
}

if (!function_exists('renderImpressoraMaterialCards')) {
    function renderImpressoraMaterialCards(array $dados, string $classesWrapper = 'mb-4'): void
    {
        renderImpressoraMaterialCardsStyles();

        $impressoraNome = htmlspecialchars((string) ($dados['impressora_nome'] ?? '-'));
        $impressoraTipoBruto = trim((string) ($dados['impressora_tipo'] ?? ''));
        $impressoraTipoNormalizado = strtoupper($impressoraTipoBruto) === 'FDM'
          ? 'Filamento'
          : (strtoupper($impressoraTipoBruto) === 'RESINA'
            ? 'Resina'
            : ($impressoraTipoBruto !== '' ? $impressoraTipoBruto : '-'));
        $impressoraTipo = htmlspecialchars($impressoraTipoNormalizado);
        $impressoraDetalheLabel = htmlspecialchars((string) ($dados['impressora_detalhe_label'] ?? ''));
        $impressoraDetalheValor = htmlspecialchars((string) ($dados['impressora_detalhe_valor'] ?? ''));

        $materialNome = htmlspecialchars((string) ($dados['material_nome'] ?? '-'));
        $materialTipo = (string) ($dados['material_tipo'] ?? '-');
        $materialTipoEscapado = htmlspecialchars($materialTipo);
        $materialMarca = htmlspecialchars((string) ($dados['material_marca'] ?? '-'));
        $materialCor = htmlspecialchars((string) ($dados['material_cor'] ?? '-'));
        $materialSubtipo = trim((string) ($dados['material_subtipo'] ?? ''));
        $materialSubtipoEscapado = htmlspecialchars($materialSubtipo);

        $iconeMaterial = strcasecmp($materialTipo, 'Resina') === 0
            ? 'fa-solid fa-bottle-water'
            : 'fas fa-compact-disc';

        $classesWrapperEscapadas = htmlspecialchars(trim($classesWrapper));

        echo '<div class="impressora-material-grid ' . $classesWrapperEscapadas . '">
          <div class="impressora-material-card h-100">
            <div class="impressora-material-icon"><i class="fas fa-microscope"></i></div>
            <div class="impressora-material-content">
              <h2>' . $impressoraNome . '</h2>
              <p>
                <strong>Tipo:</strong> ' . $impressoraTipo;

        if ($impressoraDetalheLabel !== '' && $impressoraDetalheValor !== '') {
            echo '<br><strong>' . $impressoraDetalheLabel . ':</strong> ' . $impressoraDetalheValor;
        }

        echo '</p>
            </div>
          </div>

          <div class="impressora-material-card h-100">
            <div class="impressora-material-icon">
              <i class="' . $iconeMaterial . '"></i>
            </div>
            <div class="impressora-material-content">
              <h2>' . $materialNome . '</h2>
              <p>
                <strong>Tipo:</strong> ' . $materialTipoEscapado . '<br>
                <strong>Marca:</strong> ' . $materialMarca . '<br>
                <strong>Cor:</strong> ' . $materialCor;

        if ($materialSubtipo !== '') {
            echo '<br><strong>Subtipo:</strong> ' . $materialSubtipoEscapado;
        }

        echo '</p>
            </div>
          </div>
        </div>';
    }
}
