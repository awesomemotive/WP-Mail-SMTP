<?php

namespace WPMailSMTP\Helpers;

/**
 * Reusable interface components.
 *
 * @since 3.10.0
 */
class UI {

	/**
	 * Toggle component.
	 *
	 * @since 3.10.0
	 *
	 * @param array $args {
	 *     Toggle parameters.
	 *
	 *     @type string          $name     Name attribute of the toggle's input element. Default ''.
	 *     @type string          $value    Value attribute of the toggle's input element. Default 'yes'.
	 *     @type string|string[] $label    Single label, or a 2-elements array of on/off labels. Default ''.
	 *     @type string          $id       ID attribute of the toggle's container element. Default ''.
	 *     @type string          $class    Class attribute of the toggle's container element. Default ''.
	 *     @type bool            $checked  Checked attribute of the toggle's input element. Default false.
	 *     @type bool            $disabled Disabled attribute of the toggle's input element. Default false.
	 * }
	 */
	public static function toggle( $args = [] ) {

		$args = wp_parse_args(
			$args,
			[
				'name'     => '',
				'value'    => 'yes',
				'label'    => [
					esc_html__( 'On', 'wp-mail-smtp' ),
					esc_html__( 'Off', 'wp-mail-smtp' ),
				],
				'id'       => '',
				'class'    => '',
				'checked'  => false,
				'disabled' => false,
			]
		);
		?>
		<label class="wp-mail-smtp-toggle">
			<input type="checkbox"
				   name="<?php echo esc_attr( $args['name'] ); ?>"
				   <?php echo empty( $args['class'] ) ? '' : ' class="' . esc_attr( $args['class'] ) . '"'; ?>
				   <?php echo empty( $args['id'] ) ? '' : ' id="' . esc_attr( $args['id'] ) . '"'; ?>
				   value="<?php echo esc_attr( $args['value'] ); ?>"
				   <?php checked( (bool) $args['checked'] ); ?>
				   <?php disabled( (bool) $args['disabled'] ); ?> />
			<span class="wp-mail-smtp-toggle__switch"></span>
			<?php if ( is_array( $args['label'] ) ) : ?>
				<?php if ( count( $args['label'] ) > 0 ) : ?>
					<span class="wp-mail-smtp-toggle__label wp-mail-smtp-toggle__label--checked"><?php echo esc_html( $args['label'][0] ); ?></span>
				<?php endif; ?>
				<?php if ( count( $args['label'] ) > 1 ) : ?>
					<span class="wp-mail-smtp-toggle__label wp-mail-smtp-toggle__label--unchecked"><?php echo esc_html( $args['label'][1] ); ?></span>
				<?php endif; ?>
			<?php else : ?>
				<span class="wp-mail-smtp-toggle__label wp-mail-smtp-toggle__label--static"><?php echo esc_html( $args['label'] ); ?></span>
			<?php endif; ?>
		</label>
		<?php
	}
}
