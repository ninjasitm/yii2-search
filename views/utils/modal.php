<?php
 use yii\helpers\Html;
?>
<?= $this->beginPage(); ?>
<div class="modal-dialog-auto">
	<div class="modal-content">
	  <div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h4 class="modal-title" id="myModalLabel"><?= $title; ?></h4>
	  </div>
	  <div class="modal-body" id="modal-data">
	  <?= $content; ?>
	  </div>
	  <div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
	  </div>
	</div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<?= $this->endPage(); ?>