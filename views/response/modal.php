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
<?php if(!\Yii::$app->request->isAjax) echo $this->beginPage(); ?>
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
	$footer = isset($footer) ? $footer : '';
	//Footer content
	$footer = \Yii::$app->request->isAjax ? Html::tag('div',  $footer.Html::button('Close', [
		'class' => 'btn btn-default',
		'data-dismiss' => 'modal'
	]), $options['footer']) : '';
	
	//Now create the content
	$content = $header.$body.$footer;
	
	//And determine how we're rending it
	switch(\Yii::$app->request->get('__full') == 1)
	{
		case true:
		$dialog = Html::tag('div', Html::tag('div', $content, $options['content']), $options['dialog']);
		break;
		
		default:
		$dialog = $header.$body.$footer;
		break;
	}
	//Now render the modal
	echo $dialog;
?>
<?php if(!\Yii::$app->request->isAjax) echo $this->endPage(); ?>