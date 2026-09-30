<?php
/**
 * Dashboard widget gear-menu popover (framework). Renders a widget's settings controls
 * from the schema it declares, plus the shared Save Changes button.
 *
 * @since 4.10.0
 *
 * @var array  $fields    Settings field schema. Supported types:
 *                        `select`    : { name, label, options: [ value => label ], value, locked_by? },
 *                                      where `locked_by` names a checklist field that disables this
 *                                      one while it has any option checked;
 *                        `checklist` : { name, label, panel?, options: [ value => label ],
 *                                      value: [ checked ], min_unchecked?, max_checked? }, one array
 *                                      setting whose caps disable the unchecked options once reached;
 *                        `checkboxes`: { label?, panel?, options: [ key => label ],
 *                                      value: [ key => bool ] }, independent booleans.
 * @var string $widget_id Owning widget identifier.
 * @var bool   $can_reset Whether the widget is off its defaults, i.e. there is something to reset.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Build an `id`, `class`, `data-*` and generic-attribute string, escaped piece by piece.
 */
$build_attrs = static function ( string $id, array $classes, array $data = [], array $attrs = [] ): string {

	$html  = $id !== '' ? 'id="' . esc_attr( $id ) . '" ' : '';
	$html .= 'class="' . esc_attr( implode( ' ', array_filter( array_map( 'sanitize_html_class', $classes ) ) ) ) . '"';

	foreach ( $data as $name => $value ) {
		$html .= ' data-' . esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
	}

	foreach ( $attrs as $name => $value ) {
		$html .= ' ' . esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
	}

	return $html;
};
?>
<div class="wpms-dashboard-widget-settings" hidden>
	<?php
	foreach ( $fields as $field ) :
		$field_type = $field['type'] ?? '';
		$field_name = $field['name'] ?? '';
		$field_id   = 'wpms-dashboard-' . $widget_id . '-' . $field_name;
		$options    = (array) ( $field['options'] ?? [] );

		if ( $field_type === 'select' ) :
			?>
			<p class="wpms-dashboard-widget-settings-label">
				<label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $field['label'] ?? '' ); ?></label>
			</p>
			<select <?php echo $build_attrs( $field_id, [ 'wpms-dashboard-widget-settings-field' ], array_filter( [ 'locked-by' => (string) ( $field['locked_by'] ?? '' ) ] ), [ 'name' => $field_name ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $build_attrs() escapes every piece. ?>>
				<?php foreach ( $options as $option_value => $option_label ) : ?>
					<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( (string) ( $field['value'] ?? '' ), (string) $option_value ); ?>><?php echo esc_html( $option_label ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php
		elseif ( $field_type === 'checklist' ) :
			$selected = array_map( 'strval', (array) ( $field['value'] ?? [] ) );
			$panel    = empty( $field['panel'] ) ? '' : 'wpms-dashboard-widget-settings-panel';
			// A list can require some options to stay unchecked, e.g. so a table keeps a row,
			// and can cap how many may be checked at once.
			$list_data = array_filter(
				[
					'min-unchecked' => absint( $field['min_unchecked'] ?? 0 ),
					'max-checked'   => absint( $field['max_checked'] ?? 0 ),
				]
			);
			?>
			<?php if ( ! empty( $field['label'] ) ) : ?>
				<p class="wpms-dashboard-widget-settings-label"><?php echo esc_html( $field['label'] ); ?></p>
			<?php endif; ?>
			<?php // Sentinel so a fully-unchecked list still submits an (empty) value; without it the field is omitted and the server restores all options. ?>
			<input type="hidden" name="<?php echo esc_attr( $field_name ); ?>[]" value="">
			<ul <?php echo $build_attrs( '', [ 'wpms-dashboard-widget-settings-list', 'wpms-dashboard-widget-settings-scrollbar', $panel ], $list_data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $build_attrs() escapes every piece. ?>>
				<?php foreach ( $options as $option_value => $option_label ) : ?>
					<li>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $field_name ); ?>[]" value="<?php echo esc_attr( $option_value ); ?>" <?php checked( in_array( (string) $option_value, $selected, true ) ); ?>>
							<?php echo esc_html( $option_label ); ?>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php
		elseif ( $field_type === 'checkboxes' ) :
			$values = (array) ( $field['value'] ?? [] );
			$panel  = empty( $field['panel'] ) ? '' : 'wpms-dashboard-widget-settings-panel';
			?>
			<?php if ( ! empty( $field['label'] ) ) : ?>
				<p class="wpms-dashboard-widget-settings-label"><?php echo esc_html( $field['label'] ); ?></p>
			<?php endif; ?>
			<div <?php echo $build_attrs( '', [ 'wpms-dashboard-widget-settings-group', 'wpms-dashboard-widget-settings-scrollbar', $panel ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $build_attrs() escapes every piece. ?>>
				<?php foreach ( $options as $option_key => $option_label ) : ?>
					<label class="wpms-dashboard-widget-settings-checkbox">
						<input type="hidden" name="<?php echo esc_attr( $option_key ); ?>" value="0">
						<input type="checkbox" name="<?php echo esc_attr( $option_key ); ?>" value="1" <?php checked( ! empty( $values[ $option_key ] ) ); ?>>
						<?php echo esc_html( $option_label ); ?>
					</label>
				<?php endforeach; ?>
			</div>
			<?php
		endif;
	endforeach;
	?>

	<div class="wpms-dashboard-widget-settings-footer">
		<button type="button" class="wp-mail-smtp-btn wp-mail-smtp-btn-sm wpms-dashboard-widget-settings-save">
			<?php esc_html_e( 'Save Changes', 'wp-mail-smtp' ); ?>
		</button>

		<?php
		// Always rendered, only hidden: a save can move the widget off its defaults, and the
		// widgets that re-render client-side never rebuild this markup to reveal it.
		$reset_label   = __( 'Reset to Default Settings', 'wp-mail-smtp' );
		$reset_classes = [ 'wpms-icon-btn', 'wpms-dashboard-widget-settings-reset', empty( $can_reset ) ? 'wp-mail-smtp-hide' : '' ];
		?>
		<button type="button" <?php echo $build_attrs( '', $reset_classes, [], [ 'title' => $reset_label ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $build_attrs() escapes every piece. ?>>
			<i class="wpms:icon-[fa6-solid--rotate-left] wpms:w-[12px] wpms:h-[12px]" aria-hidden="true"></i>
			<span class="screen-reader-text"><?php echo esc_html( $reset_label ); ?></span>
		</button>
	</div>
</div>
