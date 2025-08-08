<?php
// $thumb, $media, $grande devem ser definidos antes de incluir este arquivo
?>
<span class="thumb-preview-wrapper" style="position:relative;display:inline-block;">
    <img src="/forjarcana/uploads/<?= htmlspecialchars($thumb) ?>"
         alt="Thumb"
         style="max-width:80px;max-height:80px;border-radius:8px;cursor:pointer;"
         onmouseenter="showThumbPopup(event, '/forjarcana/uploads/<?= htmlspecialchars($thumb) ?>')"
         onmouseleave="hideThumbPopup()"
         onclick="showModalImagem('/forjarcana/uploads/<?= htmlspecialchars($media) ?>', '/forjarcana/uploads/<?= htmlspecialchars($grande) ?>')">
</span>