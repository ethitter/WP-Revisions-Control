const mix = require( 'laravel-mix' );

mix.autoload( {} )
	.js( 'src/js/gutenberg.js', 'dist/js/gutenberg.js' )
	.react()
	.webpackConfig( {
		externals: {
			'react': 'React',
			'react-dom': 'ReactDOM'
		}
	} );
