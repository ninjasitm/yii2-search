// JavaScript Document

class Search extends NitmEntity
{
	constructor() {
		super('search');
		this.thisInit = true;
		this.modal = null;
		this.isActive = false;
		this.modalOptions = {
			'show': false
		};
		this.events = [
			'keypress'
		];
		this.modalId = '#search-modal';
		this.searchField = '#search-field';
		this.resultContainer = '#search-results';
		this.resultWrapper = '#search-results-container';
		this.forms = {
			roles: {
				ajaxSearch: "filter",
			}
		};
		this.defaultInit = [
			'initSearchFilter',
			'initMetaActions',
			'initForms',
		];
	}

	initSearchFilter (containerId) {
		var $container = $nitm.getObj(this.getContainer(containerId));
		$container.find("form[role~='"+this.forms.roles.ajaxSearch+"']").map(function() {
			$(this).off('submit');
			var submitFunction = function (e) {
				e.preventDefault();
				$(event.currentTarget).data('yiiActiveForm').validated = true;
				var request = this.operation(event.currentTarget, function(result, form, xmlHttp) {
					var replaceId = $(form).data('id');
					$nitm.trigger('nitm:notify', [result.message, 'info', form]);
					$nitm.getObj(replaceId).replaceWith(responseText);
					//$nitm.module('tools').initDefaults('#'+replaceId);
					history.pushState({}, result.message, (!result.url ? xmlHttp.url : result.url));
				});
			};
			$(this).find(':input').on('change', function (e) {submitFunction(e);});
			$(this).on('submit', function (e) {submitFunction(e);});
		});
	};

	initSearch (id, type) {
		var $container = $(id);
		var $form = $container.find('form');
		$form.on('submit', function (event) {
			event.preventDefault();
			this.operation(this, function (result, form) {
				var $resultWrapper = $container.find(this.resultWrapper);
				$resultWrapper.html(result.data);
				$form.find(this.searchField).val(result.query);
				$resultWrapper.slideDown();
			});
		});

		switch(type)
		{
			case 'modal':
			this.initSearchModal($container, $form);
			break;

			case 'bar':
			this.initSearchBar($container, $form);
			break;
		}
	};

	initSearchBar($container, $form) {
		$container.find(this.resultWrapper).map((index, wrapper) => {
			var $wrapper = $(wrapper);
			$(document).not($wrapper).on('focus, click', (event) => {
				if(!$(this.searchField).is(':focus') && $wrapper.has(event.target).length === 0)
					$wrapper.slideUp();
			});
			$form.find(this.searchField).on('focus', function (event) {
				$wrapper.slideDown();
			});
		});
	};

	initSearchModal ($container, $form) {
		$.map(this.events, function (event) {
			$(document).on(event, function (e) {
				if(this.isActive)
					return;
				//If any special keys were hit then ignore this
				var char = String.fromCharCode(e.which);
				switch(true)
				{
					case $(e.target).is('input, textarea, .redactor-editor'):
					case e.ctrlKey || e.shiftkey || e.altKey || e.metaKey:
					case Array(
						'Esc', 'Escape', 'Backspace', 'Delete',
						'F1', 'F2', 'F3', 'F4',
						'F5', 'F6', 'F7', 'F8',
						'F7', 'F10', 'F11', 'F12'
					).indexOf(e.key) != -1:
					case !/\w/.test(char):
					return;
					break;
				}

				if($container.modal() === undefined)
				{
					$form.find(this.searchField).focus().val(e.key);
					$container.on('hidden.bs.modal', function (e) {
						this.isActive = false;
						this.modal.modal('hide');
						e.stopPropagation();
					});
					$container.on('shown.bs.modal', function () {
						this.isActive = true;
						var $modal = $(this);
						var $form = $(this).find('form');
						var $input = $form.find(this.searchField);
						$input.focus().val(e.key).get(0).setSelectionRange($input.val().length*2, $input.val().length*2);
					});
					$container.modal(this.modalOptions);
				}
				if(!this.isActive)
				{
					$container.modal('show');
				}
			});
		});
	};
}

$nitm.onModuleLoad('entity', function (module) {
	module.initModule(new Search);
});
