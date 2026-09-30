<?php
$file = 'c:/xampp/htdocs/CHIPIPIE_SYSTEM/superadmin-panel.php';
$content = file_get_contents($file);

$modal_html = <<<HTML
  <!-- ── CONFIRMATION MODAL ── -->
  <div class="sa-modal-overlay" id="sa-confirm-modal">
    <div class="sa-modal">
      <div class="sa-modal-header">
        <h3 id="sa-confirm-title">Confirm Action</h3>
        <button class="sa-modal-close" onclick="closeModal('sa-confirm-modal')"><i class="bi bi-x-lg"></i></button>
      </div>
      <div class="sa-modal-body">
        <p id="sa-confirm-msg" style="color:var(--sa-text);font-size:14px;margin-bottom:0;"></p>
      </div>
      <div class="sa-modal-footer">
        <button class="sa-btn sa-btn-ghost" onclick="closeModal('sa-confirm-modal')">Cancel</button>
        <button class="sa-btn sa-btn-primary" id="sa-confirm-btn">Confirm</button>
      </div>
    </div>
  </div>

  <!-- ── TOAST ── -->
HTML;

$content = str_replace('<!-- ── TOAST ── -->', $modal_html, $content);
file_put_contents($file, $content);
echo "SUCCESS: Added confirm modal HTML.\n";
