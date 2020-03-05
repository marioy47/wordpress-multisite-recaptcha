const path = require("path")

module.exports = {
	entry: {
		v2: "./src/js/v2.js",
		v3: "./src/js/v2.js"
	},
	output: {
		filename: "[name].js",
		path: path.resolve(__dirname, "js"),
	},
	devtool: process.env.NODE_ENV == 'development' ? 'eval-source-map' : false,
	module: {
		rules: [
			{
				test: /\.js$/,
				exclude: /node_modules/,
				loader: "babel-loader",
				options: {
					presets: ['@babel/preset-env']
				}
			},
		],
	},
	mode: process.env.NODE_ENV == 'production' ? 'production' : 'development'
}
