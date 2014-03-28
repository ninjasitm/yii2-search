<?php
	use yii\helpers\Html;
	$defaultOptions = [
		'dialog' => [
			'class' => 'modal-dialog-auto'
		],
		'content' => [
			'class' => 'modal-content'
		],
		'header' => [
			'class' => 'modal-header'
		],
		'title' => [
			'class' => 'modal-title',
			'id' => 'modalTitle'
		],
		'body' => [
			'class' => 'modal-body'
		],
		'footer' => [
			'class' => 'modal-footer'
		]
	];
	$options = (isset($modalOptions) && is_array($modalOptions)) ? array_merge($defaultOptions, $modalOptions) : $defaultOptions;
?>
<?= $this->beginPage(); ?>
<?php
	//Header content
	$headerClose = Html::button('&times;', [
		'class' => 'close',
		'data-dismiss' => 'modal',
		'aria-hidden' => 'true',
	]);
	$title = Html::tag('h2', $title, $options['title']);
	$header = Html::tag('div', $headerClose.$title, $options['header']);
	
	//Body content
	$body = Html::tag('div', $content, $options['body']);
	$footer = '';
	//Footer content
	/*$footer = Html::tag('div', Html::button('Close', [
		'class' => 'btn btn-default',
		'data-dismiss' => 'modal'
	]), $options['footer']);*/
	
	//Now render the modal
	$dialog = Html::tag('div', $header.$body.$footer, $options['dialog']);
	echo $dialog;
?>
<?= $this->endPage(); ?>