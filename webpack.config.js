/**
 * Admin React screen only (block is plain JS in assets/blocks).
 */
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config.js' );

module.exports = {
	...defaultConfig,
	entry: {
		'admin/index': path.resolve( process.cwd(), 'src/admin/index.js' ),
	},
};
