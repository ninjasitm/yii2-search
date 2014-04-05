<?php
	use yii\helpers\Html;
	$defaultOptions = [
		'dialog' => [
			'class' => 'modal-dialog'
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
	foreach($options as $type=>$option)
	{
		switch(isset($defaultOptions[$type]))
		{
			case true:
			$options[$type]['class'] = implode(' ', array_unique(explode(' ', $defaultOptions[$type]['class']." ".$option['class'])));
			break;
		}
	}
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
	$footer = Html::tag('div', Html::button('Close', [
		'class' => 'btn btn-default',
		'data-dismiss' => 'modal'
	]), $options['footer']);
	
	//Now create the content
	$content = $header.$body;
	
	//And determine how we're rending it
	switch(isset($modalOptions['contentOnly']) && ($modalOptions['contentOnly'] === true))
	{
		case true:
		$dialog = $content;
		break;
		
		default:
		$dialog = Html::tag('div', Html::tag('div', $content, $options['content']), $options['dialog']);
		break;
	}
	//Now render the modal
	echo $dialog;
?>
<?= $this->endPage(); ?>