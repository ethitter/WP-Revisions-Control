const mix = require( 'laravel-mix' );

mix.autoload( {} )
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
			'@wordpress/i18n': 'wp.i18n',
			'react': 'React',
			'react-dom': 'ReactDOM',
		}
	} );
