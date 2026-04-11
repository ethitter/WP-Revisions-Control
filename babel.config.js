module.exports = ( api ) => {
	api.cache( true );

	return {
		presets: [ '@wordpress/babel-preset-default' ],
		plugins: [
			[
				'@babel/plugin-transform-react-jsx',
				{
					runtime: 'classic',
					pragma: 'wp.element.createElement',
					pragmaFrag: 'wp.element.Fragment',
				},
			],
		],
	};
};
