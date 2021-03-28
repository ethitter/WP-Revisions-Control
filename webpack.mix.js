const mix = require( 'laravel-mix' );

mix
	.autoload( {} )
	.options( {
		terser: {
			extractComments: false,
		}
	} )
	.js( 'src/js/gutenberg.js', 'dist/js/gutenberg.js' )
	.react()
	.babelConfig( {
		'plugins': [
			[
				'@wordpress/babel-plugin-makepot',
				{
					'output': 'languages/wp-revisions-control-gutenberg.pot',
				},
			],
		],
	} )
	.webpackConfig( {
		externals: {
			'@wordpress/api-fetch': 'wp.apiFetch',
			'@wordpress/components': 'wp.components',
			'@wordpress/compose': 'wp.compose',
			'@wordpress/data': 'wp.data',
			'@wordpress/edit-post': 'wp.editPost',
			'@wordpress/element': 'wp.element',
			'@wordpress/i18n': 'wp.i18n',
			'@wordpress/plugins': 'wp.plugins',
			'react': 'React',
			'react-dom': 'ReactDOM',
		},
	} );
