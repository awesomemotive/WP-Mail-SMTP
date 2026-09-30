<?php

namespace WPMailSMTP\Admin\Dashboard;

/**
 * Dashboard widget state value object.
 *
 * @since 4.10.0
 */
class WidgetState {

	/**
	 * Whether the widget should render.
	 *
	 * @since 4.10.0
	 *
	 * @var bool
	 */
	private $visible;

	/**
	 * Template variant: 'data', 'connect', 'education', 'install'.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private $variant;

	/**
	 * Dynamic sort position. Null means use the widget class ORDER constant.
	 *
	 * @since 4.10.0
	 *
	 * @var int|null
	 */
	private $order_override;

	/**
	 * Constructor.
	 *
	 * @since 4.10.0
	 *
	 * @param bool     $visible        Whether the widget should render.
	 * @param string   $variant        Template variant.
	 * @param int|null $order_override Dynamic sort position.
	 */
	public function __construct( bool $visible, string $variant = '', ?int $order_override = null ) {

		$this->visible        = $visible;
		$this->variant        = $variant;
		$this->order_override = $order_override;
	}

	/**
	 * Whether the widget should render.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_visible(): bool {

		return $this->visible;
	}

	/**
	 * Get the template variant.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_variant(): string {

		return $this->variant;
	}

	/**
	 * Get the dynamic sort position, or null to use the widget ORDER constant.
	 *
	 * @since 4.10.0
	 *
	 * @return int|null
	 */
	public function get_order_override(): ?int {

		return $this->order_override;
	}
}
