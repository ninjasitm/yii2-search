<?php

namespace nitm\traits;

use Yii;
use yii\base\Model;
use yii\base\Event;

/**
 * Trait ActivityIndicator
 * @package nitm\models
 */

trait Widgets
{	
	public function issueTrackerWidget($options)
	{
		return \nitm\widgets\issueTracker\IssueTracker::widget($options);
	}
	
	public function activityWidget($options)
	{
		return \nitm\widgets\activityIndicator\ActivityIndicator::widget($options);
	}
	
	public function replyWidget(array $constrain)
	{
		return \nitm\widgets\replies\Replies::widget($constrain);
	}
	
	public function replyFormWidget(array $constrain)
	{
		return \nitm\widgets\replies\RepliesForm::widget($constrain);
	}
	
	public function replyCountWidget(array $constrain)
	{
		return \nitm\widgets\replies\RepliesCount::widget($constrain);
	}
	
	public function voteWidget(array $constrain)
	{
		return \nitm\widgets\vote\Vote::widget($constrain);
	}
	
	public function ratingWidget(array $constrain)
	{
		return \nitm\widgets\rating\Rating::widget($constrain);
	}
	
	public function legendWidget()
	{
		return \nitm\widgets\legend\Legend::widget([
			"legend" => $this->legend
		]);
	}
	
	public function revisionsWidget(array $options)
	{
		return \nitm\widgets\revisions\Revisions::widget($options);
	}
	
	public function revisionsCountWidget(array $constrain)
	{
		return \nitm\widgets\revisions\RevisionsCount::widget($constrain);
	}
	
	public function revisionsInputWidget(array $constrain)
	{
		return \nitm\widgets\revisions\RevisionsInput::widget($constrain);
	}
	
	public function revisionsModalWidget()
	{
		return \nitm\widgets\revisions\RevisionsModal::widget();
	}
	
	public function alertWidget(array $options)
	{
		return \nitm\widgets\alert\Alert::widget($options);
	}
	
	public function tokensWidget(array $options)
	{
		return \nitm\widgets\tokens\Tokens::widget($options);
	}
	
	public function modalWidget(array $options)
	{
		return \nitm\widgets\modal\Modal::widget($options);
	}
}
?>