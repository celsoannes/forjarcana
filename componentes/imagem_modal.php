<!-- Modal para imagem média/grande -->
<div class="modal fade" id="modalImagemMedia" tabindex="-1" aria-labelledby="modalImagemMediaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:#2c223b;color:#ffd700;">
      <div class="modal-header">
        <h5 class="modal-title" id="modalImagemMediaLabel">Visualização da Imagem</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body text-center" style="position:relative;">
        <div id="lupa-container" style="display:inline-block;position:relative;">
          <img id="imagemMediaModal"
               src=""
               alt="Imagem média"
               style="max-width:100%;max-height:400px;border-radius:12px;border:2px solid #ffce54;box-shadow:0 4px 24px #000;background:#2c223b;cursor:zoom-in;"
               onmousemove="lupaMove(event)"
               onmouseenter="lupaShow()"
               onmouseleave="lupaHide()">
          <div id="lupa" style="display:none;position:absolute;pointer-events:none;width:120px;height:120px;border-radius:50%;border:2px solid #ffd700;box-shadow:0 2px 12px #000;background:#fff;overflow:hidden;z-index:10;">
            <img id="imagemLupa" src="" style="position:absolute;top:0;left:0;min-width:100%;min-height:100%;">
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="thumb-global-popup" style="display:none;position:absolute;z-index:99999;">
  <img id="thumb-global-img" src="" alt="Preview" style="max-width:220px;max-height:220px;border-radius:10px;border:2px solid #ffce54;box-shadow:0 4px 24px #000;background:#2c223b;">
</div>

<script>
var imagemMediaSrc = '';
var imagemGrandeSrc = '';
var mostrandoGrande = false;

function showThumbPopup(e, src) {
  var popup = document.getElementById('thumb-global-popup');
  var img = document.getElementById('thumb-global-img');
  img.src = src;
  popup.style.display = 'block';
  var x = e.clientX + 20 + window.scrollX;
  var y = e.clientY - 20 + window.scrollY;
  popup.style.left = x + 'px';
  popup.style.top = y + 'px';
}
function hideThumbPopup() {
  document.getElementById('thumb-global-popup').style.display = 'none';
}

function showModalImagem(srcMedia, srcGrande) {
  var img = document.getElementById('imagemMediaModal');
  var imgLupa = document.getElementById('imagemLupa');
  img.src = srcMedia;
  imgLupa.src = srcGrande;
  imagemMediaSrc = srcMedia;
  imagemGrandeSrc = srcGrande;
  mostrandoGrande = false;
  var modal = new bootstrap.Modal(document.getElementById('modalImagemMedia'));
  modal.show();
}

function trocarImagemModal() {
  var img = document.getElementById('imagemMediaModal');
  if (!mostrandoGrande) {
    img.src = imagemGrandeSrc;
    mostrandoGrande = true;
  } else {
    img.src = imagemMediaSrc;
    mostrandoGrande = false;
  }
}

// Lupa
function lupaShow() {
  document.getElementById('lupa').style.display = 'block';
}
function lupaHide() {
  document.getElementById('lupa').style.display = 'none';
}
function lupaMove(e) {
  var img = document.getElementById('imagemMediaModal');
  var lupa = document.getElementById('lupa');
  var imgLupa = document.getElementById('imagemLupa');
  var rect = img.getBoundingClientRect();
  var x = e.clientX - rect.left;
  var y = e.clientY - rect.top;

  var lupaSize = 120;
  var zoom = 2.5;

  lupa.style.left = (x - lupaSize/2) + "px";
  lupa.style.top = (y - lupaSize/2) + "px";

  var imgWidth = img.width;
  var imgHeight = img.height;

  var propX = x / imgWidth;
  var propY = y / imgHeight;

  imgLupa.style.width = (imgWidth * zoom) + "px";
  imgLupa.style.height = (imgHeight * zoom) + "px";
  imgLupa.style.left = -(propX * imgWidth * zoom - lupaSize/2) + "px";
  imgLupa.style.top = -(propY * imgHeight * zoom - lupaSize/2) + "px";
}
</script>