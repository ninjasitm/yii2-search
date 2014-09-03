// JavaScript Document

function Requests () {
	var self = this;
	
	this.forms = {
		roles: ['createRequest', 'updateRequest']
	};
	
	this.buttons = {
		roles: []
	};
	this.views = {
		itemId : 'request',
		containerId: 'requests',
	}
	this.defaultInit = [
	];

	this.init = function (container) {
		this.defaultInit.map(function (method, key) {
			if(typeof self[method] == 'function')
			{
				self[method](container);
			}
		});
		var $entity = $nitm.module('entity', true);
		$entity.initMetaActions(null, 'entity:requests');
	}
}

$nitm.onModuleLoad('entity', function () {
	$nitm.initModule('entity:requests', new Requests());
});