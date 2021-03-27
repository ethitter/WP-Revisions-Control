const mix = require( 'laravel-mix' );

mix.autoload( {} )
	.react( 'src/js/gutenberg.js', 'dist/js/gutenberg.js' )
	.webpackConfig({
		externals: {
			'react': 'React',
			'react-dom': 'ReactDOM'
		}
	} );
