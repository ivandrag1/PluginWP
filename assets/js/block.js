(function(blocks, element){
	const el = element.createElement;
	blocks.registerBlockType('platen-vaprosnik/button', {
		title: 'Платен въпросник',
		icon: 'money-alt',
		category: 'widgets',
		edit: function(){
			return el('p', {}, 'Блокът „Платен въпросник“ ще покаже бутона „Стартирай“ на сайта.');
		},
		save: function(){
			return null;
		}
	});
})(window.wp.blocks, window.wp.element);
