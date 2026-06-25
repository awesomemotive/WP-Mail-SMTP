<?php

namespace WPMailSMTP\Abilities\DebugEvents;

use WPMailSMTP\Abilities\AbstractAbility;
use WPMailSMTP\Admin\DebugEvents\Event;
use WPMailSMTP\Admin\DebugEvents\EventsCollection;

/**
 * Ability: list recorded WP Mail SMTP debug events.
 *
 * Edition-neutral: registered on every install (Lite and Pro). Gated on the
 * plugin's manage-options capability, the same gate that controls the Debug
 * Events screen.
 *
 * @since 4.9.0
 */
class GetDebugEventsAbility extends AbstractAbility {

	/**
	 * Ability slug, without the namespace prefix.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_name() {

		return 'get-debug-events';
	}

	/**
	 * Human-readable label.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_label() {

		return esc_html__( 'Get Debug Events', 'wp-mail-smtp' );
	}

	/**
	 * Human-readable description.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_description() {

		return esc_html__( 'List recorded WP Mail SMTP debug events (errors and debug entries).', 'wp-mail-smtp' );
	}

	/**
	 * Input schema.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_input_schema() {

		return [
			'type'       => 'object',
			'properties' => [
				'limit'    => [
					'description' => esc_html__( 'Maximum number of events to return.', 'wp-mail-smtp' ),
					'type'        => 'integer',
					'minimum'     => 1,
					'maximum'     => 100,
					'default'     => 20,
				],
				'offset'   => [
					'description' => esc_html__( 'Number of events to skip.', 'wp-mail-smtp' ),
					'type'        => 'integer',
					'minimum'     => 0,
					'default'     => 0,
				],
				'severity' => [
					'description' => esc_html__( 'Filter events by severity.', 'wp-mail-smtp' ),
					'type'        => 'string',
					'enum'        => [ 'error', 'debug' ],
				],
			],
		];
	}

	/**
	 * Output schema.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_output_schema() {

		return [
			'type'       => 'object',
			'properties' => [
				'events' => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'properties' => [
							'id'           => [ 'type' => 'integer' ],
							'created_date' => [ 'type' => 'string' ],
							'severity'     => [ 'type' => 'string' ],
							'content'      => [ 'type' => 'string' ],
						],
					],
				],
				'total'  => [ 'type' => 'integer' ],
				'limit'  => [ 'type' => 'integer' ],
				'offset' => [ 'type' => 'integer' ],
			],
		];
	}

	/**
	 * Execute: list debug events.
	 *
	 * @since 4.9.0
	 *
	 * @param mixed $input Input data.
	 *
	 * @return array
	 */
	public function execute( $input = null ) {

		$args = $this->normalize_input( $input );

		$pagination = $this->get_pagination( $args );
		$limit      = $pagination['limit'];
		$offset     = $pagination['offset'];

		$params = [
			'per_page' => $limit,
			'offset'   => $offset,
		];

		if ( isset( $args['severity'] ) ) {
			$severity = sanitize_text_field( $args['severity'] );

			if ( $severity === 'error' ) {
				$params['type'] = Event::TYPE_ERROR;
			} elseif ( $severity === 'debug' ) {
				$params['type'] = Event::TYPE_DEBUG;
			}
		}

		$collection = new EventsCollection( $params );
		$total      = $collection->get_count();

		$events = [];

		foreach ( $collection->get() as $event ) {
			$events[] = $this->format_event( $event );
		}

		return [
			'events' => $events,
			'total'  => $total,
			'limit'  => $limit,
			'offset' => $offset,
		];
	}

	/**
	 * Format a single debug event for output.
	 *
	 * @since 4.9.0
	 *
	 * @param Event $event Debug event model.
	 *
	 * @return array
	 */
	private function format_event( Event $event ) {

		return [
			'id'           => $event->get_id(),
			'created_date' => $this->to_iso8601( $event->get_created_at() ),
			'severity'     => $event->get_type() === Event::TYPE_ERROR ? 'error' : 'debug',
			'content'      => $event->get_content(),
		];
	}
}
