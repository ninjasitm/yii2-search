<?= isset($greeting) ? $greeting: ''; ?>
<?= $content; ?>
<?php if(isset($footer)): ?>
<small>
<?= $footer; ?>
</small>
<?php endif; ?>