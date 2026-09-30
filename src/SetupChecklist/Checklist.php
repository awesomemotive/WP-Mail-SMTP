<?php

namespace WPMailSMTP\SetupChecklist;

/**
 * Setup Checklist model.
 *
 * @since 4.10.0
 */
class Checklist {

	/**
	 * Data-model source.
	 *
	 * @since 4.10.0
	 *
	 * @var Config
	 */
	private $config;

	/**
	 * Completion detector.
	 *
	 * @since 4.10.0
	 *
	 * @var CompletionDetector
	 */
	private $detector;

	/**
	 * Memoised enriched sections for the current request.
	 *
	 * @since 4.10.0
	 *
	 * @var array
	 */
	private $sections;

	/**
	 * Constructor.
	 *
	 * @since 4.10.0
	 *
	 * @param Config             $config   Data-model source.
	 * @param CompletionDetector $detector Completion detector.
	 */
	public function __construct( Config $config, CompletionDetector $detector ) {

		$this->config   = $config;
		$this->detector = $detector;
	}

	/**
	 * Sections enriched with completion state, with inapplicable items removed.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	public function get_sections() {

		if ( $this->sections !== null ) {
			return $this->sections;
		}

		$sections = $this->config->get_sections();

		foreach ( $sections as $section_id => $section ) {
			$completed = 0;
			$items     = $section['items'] ?? [];

			foreach ( $items as $item_id => $item ) {
				if ( ! $this->detector->is_applicable( $item['id'] ) ) {
					unset( $items[ $item_id ] );

					continue;
				}

				$is_complete = $this->detector->is_complete( $item['id'] );

				$items[ $item_id ]['complete'] = $is_complete;

				if ( $is_complete ) {
					++$completed;
				}
			}

			$total = count( $items );

			$sections[ $section_id ]['items']           = $items;
			$sections[ $section_id ]['completed_items'] = $completed;
			$sections[ $section_id ]['total_items']     = $total;
			$sections[ $section_id ]['complete']        = $total > 0 && $completed === $total;
		}

		$this->sections = $sections;

		return $this->sections;
	}

	/**
	 * Overall progress summary.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	public function get_progress() {

		$sections = $this->get_sections();

		$total_sections     = count( $sections );
		$completed_sections = 0;
		$total_items        = 0;
		$completed_items    = 0;

		foreach ( $sections as $section ) {
			$completed_sections += ! empty( $section['complete'] ) ? 1 : 0;
			$total_items        += $section['total_items'] ?? 0;
			$completed_items    += $section['completed_items'] ?? 0;
		}

		$percent = $total_items > 0 ? (int) round( $completed_items / $total_items * 100 ) : 0;

		return [
			'percent'            => $percent,
			'completed_items'    => $completed_items,
			'total_items'        => $total_items,
			'completed_sections' => $completed_sections,
			'total_sections'     => $total_sections,
		];
	}
}
