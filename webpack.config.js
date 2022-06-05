const config = require( './node_modules/@wordpress/scripts/config/webpack.config' );
const { resolve } = require( 'path' );

config.entry = {
	'classic-editor': './assets/src/classic-editor.js',
	gutenberg: './assets/src/gutenberg.js',
};

config.output.path = resolve( process.cwd(), 'assets/build' );

module.exports = config;
