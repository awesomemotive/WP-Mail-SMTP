/**
 * WPForms Coding Standards for JavaScript.
 *
 * @version 1.4
 */
module.exports = {

	/**
	 * Environments.
	 *
	 * Each environment brings with it a certain set of predefined global variables..
	 *
	 * @since 1.0
	 */
	env: {

		/**
		 * Mostly fix require() function recognition in gulpfile.js.
		 *
		 * @since 1.5
		 */
		node: true,

		/**
		 * Browser global variables.
		 *
		 * @since 1.0
		 */
		browser: true,

		/**
		 * jQuery global variables.
		 *
		 * @since 1.0
		 */
		jquery: true,

		/**
		 * ECMAScript 6 features.
		 *
		 * Enable all ECMAScript 6 features except for modules (this automatically sets the ecmaVersion parser option to 6).
		 *
		 * @since 1.0
		 */
		es6: true,
	},

	/**
	 * Plugins.
	 *
	 * @since 1.0
	 */
	plugins: [

		/**
		 * JSDoc specific linting rules for ESLint.
		 *
		 * Added because ESLint dropped support for JSDoc.
		 * https://eslint.org/blog/2018/11/jsdoc-end-of-life
		 *
		 * @since 1.2
		 */
		'jsdoc',
	],


	/**
	 * Preset configurations.
	 *
	 * @since 1.0
	 */
	extends: [

		/**
		 * Subset of core rules that report common problems.
		 *
		 * @since 1.0
		 */
		'eslint:recommended',

		/**
		 * WPCS rules.
		 *
		 * eslint-plugin-wordpress is not used at the moment as recommended here:
		 * https://github.com/WordPress-Coding-Standards/eslint-plugin-wordpress/issues/106
		 *
		 * @since 1.0
		 */
		'wordpress',
	],

	/**
	 * Parser.
	 *
	 * @since 1.0
	 */
	parser: 'babel-eslint',

	/**
	 * Parser Options.
	 *
	 * ecmaVersion is automatically set to 6 by env.es6
	 *
	 * @since 1.0
	 */
	parserOptions: {

		/**
		 * Source code type.
		 *
		 * Set to "script" (default) or "module" if your code is in ECMAScript modules.
		 *
		 * @since 1.0
		 */
		sourceType: 'script',

		/**
		 * Additional language features.
		 *
		 * @since 1.0
		 */
		ecmaFeatures: {

			/**
			 * Enable JSX.
			 *
			 * @since 1.0
			 */
			jsx: true,
		},
	},

	/**
	 * Settings.
	 *
	 * @since 1.0
	 */
	settings: {

		/**
		 * The names of any function used to wrap propTypes, e.g. 'forbidExtraProps'.
		 * If this isn't set, any propTypes wrapped in a function will be skipped.
		 *
		 * @since 1.0
		 */
		propWrapperFunctions: [
			'forbidExtraProps',
			{ property: 'freeze', 'object': 'Object' },
			{ property: 'myFavoriteWrapper' }
		]
	},

	/**
	 * Default globals.
	 *
	 * These will get ignored automatically.
	 * 'true' to allow the variable to be overwritten or 'false' to disallow overwriting.
	 *
	 * @since 1.0
	 */
	globals: {
		_       : false,
		Backbone: false,
		jQuery  : false,
		JSON    : false,
		wp      : false,
	},

	/**
	 * WPForms preset standard overrides and additions.
	 *
	 * @since 1.0
	 */
	rules: {

		'strict': [ 'error', 'global' ],

		/**
		 * Disallow variable or function use before it's declared.
		 *
		 * @since 1.0
		 */
		'no-use-before-define': 'error',

		/**
		 * Require constructor names to begin with a capital letter.
		 *
		 * @since 1.0
		 */
		'new-cap': 'error',

		/**
		 * Enforce spaces inside of parentheses.
		 *
		 * @since 1.0
		 */
		'space-in-parens': [ 'error', 'always' ],

		/**
		 * Require camel case names.
		 *
		 * @since 1.0
		 */
		'camelcase'      : [
			'warn',
			{
				properties: 'always',
				allow     : [ '^wpforms_', '^wp_mail_smtp' ],
			},
		],

		/**
		 * Disallow or enforce trailing commas.
		 *
		 * @since 1.0
		 */
		'comma-dangle': 'off',

		/**
		 * Require or disallow spacing between function identifiers and their invocations.
		 *
		 * @since 1.0
		 */
		'func-call-spacing': 'error',

		/**
		 * Enforces spacing between keys and values in object literal properties.
		 *
		 * @since 1.0
		 */
		'key-spacing': 'off',

		/**
		 * Don't force vars to be on top.
		 *
		 * @since 1.0
		 */
		'vars-on-top': 'off',

		/**
		 * Require or disallow Yoda conditions.
		 *
		 * @since 1.0
		 */
		'yoda': 'off',

		/**
		 * Require valid jsdoc blocks.
		 *
		 * Switched off in favor of 'eslint-plugin-jsdoc'.
		 *
		 * @since 1.0
		 */
		'valid-jsdoc': 'off',

		/**
		 * Require docblocks.
		 *
		 * Switched off in favor of 'eslint-plugin-jsdoc'.
		 *
		 * @since 1.0
		 */
		'require-jsdoc': 'off',

		/**
		 * Do not enforce function style.
		 *
		 * @since 1.0
		 */
		'func-style': 'off',

		/**
		 * Require == and !== where necessary.
		 *
		 * @since 1.0
		 */
		'eqeqeq': 'error',

		/**
		 * Disallow null comparisons without type-checking operators.
		 *
		 * @since 1.0
		 */
		'no-eq-null': 'error',

		/**
		 * Must use radix in parseInt.
		 *
		 * e.g.
		 *
		 *     var a = 1.22;
		 *     var b = parseInt( a, 10 ); // Radix used here
		 *
		 * @since 1.0
		 */
		'radix': 'error',

		/**
		 * Warn about unused vars.
		 *
		 * @since 1.1
		 */
		'no-unused-vars': [
			'error',
			{
				args: 'none'
			}
		],

		/**
		 * Warn about useless escapes.
		 *
		 * @since 1.1
		 */
		'no-useless-escape': 'warn',

		/**
		 * Cyclomatic complexity measures the number of linearly independent paths through a program’s source code.
		 *
		 * @since 1.3
		 */
		"complexity": [
			'warn',
			{
				"max": 6,
			}
		],

		/**
		 * Limit the number of lines that a function can comprise of.
		 *
		 * @since 1.3
		 */
		"max-lines-per-function": [
			'warn',
			{
				"max": 50,
				"skipBlankLines": true,
				"skipComments": true,
			}
		],

		/**
		 * Maximum depth that blocks can be nested to reduce code complexity.
		 *
		 * @since 1.3
		 */
		"max-depth": [ 'error', 3 ],

		/**
		 * Enforce tabbed indentation.
		 *
		 * @since 1.4
		 */
		"indent": [
			'error',
			'tab',
			{
				"SwitchCase": 1,
			}
		],

		/**
		 * Reports invalid alignment of JSDoc block asterisks.
		 *
		 * @since 1.2
		 */
		"jsdoc/check-alignment": 'error',

		/**
		 * Reports invalid padding inside JSDoc block.
		 *
		 * @since 1.2
		 */
		"jsdoc/check-indentation": 'error',

		/**
		 * Ensures that parameter names in JSDoc match those in the function declaration.
		 *
		 * @since 1.2
		 */
		"jsdoc/check-param-names": 'error',

		/**
		 * Reports against Google Closure Compiler syntax.
		 *
		 * @since 1.2
		 */
		"jsdoc/check-syntax": 'error',

		/**
		 * Reports invalid block tag names.
		 *
		 * @since 1.2
		 */
		"jsdoc/check-tag-names": 'error',

		/**
		 * Reports invalid types
		 *
		 * @since 1.2
		 */
		"jsdoc/check-types": 'error',

		/**
		 * Reports an issue with any non-constructor function using @implements.
		 *
		 * @since 1.2
		 */
		"jsdoc/implements-on-classes": 'error',

		/**
		 * Enforces a regular expression pattern on descriptions.
		 *
		 * Default (basic expression to match English sentences): ^([A-Z]|[`\\d_])([\\s\\S]*[.?!`])?$
		 *
		 * @since 1.2
		 */
		"jsdoc/match-description": 'error',

		/**
		 * Enforces a consistent padding of the block description.
		 *
		 * @since 1.2
		 */
		"jsdoc/newline-after-description": 'error',

		/**
		 * Requires a hyphen before the @param description.
		 *
		 * @since 1.2
		 */
		"jsdoc/require-hyphen-before-param-description": [ 'error', 'never' ],

		/**
		 * Checks for presence of jsdoc comments, on class declarations as well as functions.
		 *
		 * @since 1.2
		 */
		'jsdoc/require-jsdoc': [
			'error',
			{
				'require': {
					ArrowFunctionExpression: false,
					ClassDeclaration: true,
					FunctionDeclaration: true,
					FunctionExpression: false,
				}
			}
		],

		/**
		 * Requires that all function parameters are documented.
		 *
		 * @since 1.2
		 */
		"jsdoc/require-param": 'error',

		/**
		 * Requires that @param tag has description value.
		 *
		 * @since 1.2
		 */
		"jsdoc/require-param-description": 'error',

		/**
		 * Requires that all function parameters have name.
		 *
		 * @since 1.2
		 */
		"jsdoc/require-param-name": 'error',

		/**
		 * Requires that @param tag has type value.
		 *
		 * @since 1.2
		 */
		"jsdoc/require-param-type": 'error',

		/**
		 * Requires returns are documented.
		 *
		 * @since 1.2
		 */
		"jsdoc/require-returns": 'error',

		/**
		 * Checks if the return expression exists in function body and in the comment.
		 *
		 * @since 1.2
		 */
		"jsdoc/require-returns-check": 'error',

		/**
		 * Requires that @returns tag has description value.
		 *
		 * @since 1.2
		 */
		"jsdoc/require-returns-description": 'error',

		/**
		 * Requires that @returns tag has type value.
		 *
		 * @since 1.2
		 */
		"jsdoc/require-returns-type": 'error',

		/**
		 * Requires all types to be valid JSDoc or Closure compiler types without syntax errors.
		 *
		 * @since 1.2
		 */
		"jsdoc/valid-types": 'error',
	},
};
