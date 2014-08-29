// JavaScript Document

function Requests () {
	//Extend Nitm
	//Nitm.apply(this, arguments);
	
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
		var $nitm = $nitm.module('nitm', true);
		$nitm.initMetaActions(null, 'nitm:requests');
	}
}

$nitm.onModuleLoad('nitm', function () {
	$nitm.initModule('nitm:requests', new Requests());
});