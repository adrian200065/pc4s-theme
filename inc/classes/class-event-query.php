<?php
/**
 * Event Query Helper
 *
 * Centralizes all event-related WP_Query logic. Templates must only
 * call this class and render markup — no business logic in templates.
 *
 * Recurrence model
 * ────────────────
 * `event_date`            Base / first occurrence date (Y-m-d, ACF date field).
 * `is_recurring`          Boolean (ACF true_false).
 * `recurrence_rule`       "weekly" | "biweekly" | "monthly" (ACF select).
 * `recurrence_end_date`   Optional upper bound (Y-m-d, ACF date field).
 *
 * On each ACF save (`acf/save_post`) the next future occurrence is computed
 * and stored as the `_event_next_occurrence` meta key (Y-m-d), which is used
 * for all queries so the DB never runs heavy date arithmetic at request time.
 *
 * @package PC4S
 */

namespace PC4S\Classes;

use PC4S\Classes\PostTypes\Event;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EventQuery {

	// ──────────────────────────────────────────────────────────────────────
	// Constants
	// ──────────────────────────────────────────────────────────────────────

	/**
	 * Hidden meta key that stores the pre-computed next occurrence date.
	 */
	const NEXT_OCC_META = '_event_next_occurrence';

	// ──────────────────────────────────────────────────────────────────────
	// Public query API
	// ──────────────────────────────────────────────────────────────────────

	/**
	 * Return a WP_Query of upcoming events.
	 *
	 * @param int      $count       Maximum number of events to return.
	 * @param string[] $type_slugs  Optional event_type taxonomy slugs to filter by
	 *                              (e.g. ['pc4s'] or ['true-blue']). Empty = all types.
	 * @param int[]    $post_ids    Optional explicit post IDs (manual selection).
	 *                              When provided, $type_slugs is ignored.
	 * @return \WP_Query
	 */
	public static function get_upcoming( int $count = 4, array $type_slugs = [], array $post_ids = [] ): \WP_Query {
		$today = gmdate( 'Y-m-d' );

		$args = [
			'post_type'      => Event::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => $count,
			'no_found_rows'  => true,
			'meta_key'       => self::NEXT_OCC_META,
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_query'     => [
				[
					'key'     => self::NEXT_OCC_META,
					'value'   => $today,
					'compare' => '>=',
					'type'    => 'DATE',
				],
			],
		];

		// Manual selection overrides taxonomy filter.
		if ( ! empty( $post_ids ) ) {
			$args['post__in'] = array_map( 'absint', $post_ids );
			unset( $args['meta_key'], $args['orderby'] );
			$args['orderby'] = 'post__in';
		} elseif ( ! empty( $type_slugs ) ) {
			$args['tax_query'] = [
				[
					'taxonomy' => Event::TAXONOMY,
					'field'    => 'slug',
					'terms'    => $type_slugs,
				],
			];
		}

		return new \WP_Query( $args );
	}

	/**
	 * Return a WP_Query built by merging per-type buckets.
	 *
	 * Useful when you want N events from type A and M events from type B.
	 * Each row in $rows must have:
	 *   'type_slug'  (string) 'all' | 'pc4s' | 'true-blue' | any event_type slug
	 *   'type_count' (int)    how many events to pull from that type
	 *
	 * Duplicates are removed (a post matching multiple rows appears once).
	 * The final ordering is by `_event_next_occurrence` ASC.
	 *
	 * @param array<array{type_slug:string,type_count:int}> $rows
	 * @return \WP_Query
	 */
	public static function get_upcoming_mixed( array $rows ): \WP_Query {
		$today      = gmdate( 'Y-m-d' );
		$merged_ids = [];

		foreach ( $rows as $row ) {
			$type_slug = sanitize_key( $row['type_slug'] ?? 'all' );
			$count     = absint( $row['type_count'] ?? 2 );

			if ( $count < 1 ) {
				continue;
			}

			$args = [
				'post_type'      => Event::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => $count,
				'no_found_rows'  => true,
				'fields'         => 'ids',
				'meta_key'       => self::NEXT_OCC_META,
				'orderby'        => 'meta_value',
				'order'          => 'ASC',
				'meta_query'     => [
					[
						'key'     => self::NEXT_OCC_META,
						'value'   => $today,
						'compare' => '>=',
						'type'    => 'DATE',
					],
				],
			];

			if ( $type_slug && 'all' !== $type_slug ) {
				$args['tax_query'] = [
					[
						'taxonomy' => Event::TAXONOMY,
						'field'    => 'slug',
						'terms'    => [ $type_slug ],
					],
				];
			}

			$sub = new \WP_Query( $args );
			foreach ( (array) $sub->posts as $id ) {
				if ( ! in_array( (int) $id, $merged_ids, true ) ) {
					$merged_ids[] = (int) $id;
				}
			}
			wp_reset_postdata();
		}

		if ( empty( $merged_ids ) ) {
			// Return an empty query.
			return new \WP_Query( [ 'post_type' => Event::POST_TYPE, 'post__in' => [ 0 ], 'no_found_rows' => true ] );
		}

		// Re-fetch full post objects, ordered by next occurrence date.
		return new \WP_Query( [
			'post_type'      => Event::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => count( $merged_ids ),
			'no_found_rows'  => true,
			'post__in'       => $merged_ids,
			'meta_key'       => self::NEXT_OCC_META,
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
		] );
	}

	// ──────────────────────────────────────────────────────────────────────
	// Archive helpers
	// ──────────────────────────────────────────────────────────────────────

	/**
	 * Expand a list of event WP_Post objects into individual occurrence entries.
	 *
	 * For single (non-recurring) events one entry is produced per post.
	 * For recurring events every occurrence within the look-ahead window is
	 * generated by walking the recurrence rule forward from the base date,
	 * mirroring the logic in compute_next_occurrence().
	 *
	 * Each entry in the returned array is:
	 *   [ 'post' => WP_Post, 'date' => 'Y-m-d' ]
	 *
	 * The array is sorted by date ASC before returning so callers receive a
	 * ready-to-render timeline.
	 *
	 * NOTE: ACF's `get_field('event_date')` returns Y-m-d (display_format is
	 * irrelevant; return_format is 'Y-m-d' in the field group JSON).
	 *
	 * @param \WP_Post[] $posts        WP_Post objects from the main archive query.
	 * @param int        $months_ahead How many months ahead to look (default 6).
	 * @return array<int, array{post: \WP_Post, date: string}>
	 */
	public static function expand_occurrences( array $posts, int $months_ahead = 6 ): array {
		$today      = gmdate( 'Y-m-d' );
		$window_end = gmdate( 'Y-m-d', strtotime( "+{$months_ahead} months" ) );
		$occurrences = [];

		foreach ( $posts as $post ) {
			if ( ! function_exists( 'get_field' ) ) {
				// ACF unavailable — fall back to the pre-computed meta key.
				$fallback = (string) get_post_meta( $post->ID, self::NEXT_OCC_META, true );
				if ( $fallback ) {
					$occurrences[] = [ 'post' => $post, 'date' => $fallback ];
				}
				continue;
			}

			$base_date    = (string) get_field( 'event_date', $post->ID );           // Y-m-d
			$is_recurring = (bool)   get_field( 'is_recurring', $post->ID );
			$rule         = (string) get_field( 'recurrence_rule', $post->ID );      // weekly|biweekly|monthly|monthly_nth_weekday
			$occ_end      = (string) get_field( 'recurrence_end_date', $post->ID ); // Y-m-d or ''

			// Extra fields used only by the monthly_nth_weekday rule.
			$rule_weekday    = 'monthly_nth_weekday' === $rule ? (int) get_field( 'recurrence_weekday', $post->ID )    : 0;
			$rule_occurrence = 'monthly_nth_weekday' === $rule ? (int) get_field( 'recurrence_occurrence', $post->ID ) : 0;

			if ( ! $base_date ) {
				continue;
			}

			if ( ! $is_recurring ) {
				// Single occurrence: include only if it falls in [today, window_end].
				if ( $base_date >= $today && $base_date <= $window_end ) {
					$occurrences[] = [ 'post' => $post, 'date' => $base_date ];
				}
				continue;
			}

			// Recurring: walk forward from base_date collecting every occurrence
			// that falls within [today, window_end].
			$candidate = $base_date;
			$limit     = 200; // Safety cap — prevents infinite loops on bad data.
			$i         = 0;

			while ( $candidate <= $window_end && $i < $limit ) {
				if ( $candidate >= $today ) {
					// Respect the optional recurrence end date set in ACF.
					if ( $occ_end && $candidate > $occ_end ) {
						break;
					}
					$occurrences[] = [ 'post' => $post, 'date' => $candidate ];
				}
				$candidate = self::advance_date( $candidate, $rule, $rule_weekday, $rule_occurrence );
				$i++;
			}
		}

		// Sort all occurrences chronologically.
		usort( $occurrences, static fn( $a, $b ) => strcmp( $a['date'], $b['date'] ) );

		return $occurrences;
	}

	/**
	 * Group occurrence entries (from expand_occurrences) by calendar month.
	 *
	 * Each entry in $occurrences must be: [ 'post' => WP_Post, 'date' => 'Y-m-d' ]
	 *
	 * Returns an associative array keyed by 'Y-m' with each entry containing:
	 *   'label'       (string)  Human-readable month  — e.g. "March 2026"
	 *   'datetime'    (string)  Machine-readable month — e.g. "2026-03"
	 *   'occurrences' (array[]) The occurrence entries that belong to this month
	 *
	 * @param array<int, array{post: \WP_Post, date: string}> $occurrences
	 * @return array<string, array{label: string, datetime: string, occurrences: array}>
	 */
	public static function group_by_month( array $occurrences ): array {
		$groups = [];

		foreach ( $occurrences as $occ ) {
			$date = $occ['date'];

			if ( ! $date ) {
				continue;
			}

			$ts  = strtotime( $date );
			$key = gmdate( 'Y-m', $ts );

			if ( ! isset( $groups[ $key ] ) ) {
				$groups[ $key ] = [
					'label'       => gmdate( 'F Y', $ts ),
					'datetime'    => gmdate( 'Y-m', $ts ),
					'occurrences' => [],
				];
			}

			$groups[ $key ]['occurrences'][] = $occ;
		}

		return $groups;
	}

	// ──────────────────────────────────────────────────────────────────────
	// Recurrence helpers
	// ──────────────────────────────────────────────────────────────────────

	/**
	 * Compute the next future occurrence date for a given event post.
	 *
	 * Returns a Y-m-d string, or an empty string when the event has
	 * no future occurrences (expired one-time event, or past end date).
	 *
	 * @param int $post_id
	 * @return string Y-m-d or ''
	 */
	public static function compute_next_occurrence( int $post_id ): string {
		if ( ! function_exists( 'get_field' ) ) {
			return '';
		}

		$base_date  = (string) get_field( 'event_date', $post_id );      // Y-m-d
		$is_recurr  = (bool)   get_field( 'is_recurring', $post_id );
		$rule       = (string) get_field( 'recurrence_rule', $post_id ); // weekly|biweekly|monthly|monthly_nth_weekday
		$end_date   = (string) get_field( 'recurrence_end_date', $post_id ); // Y-m-d or ''

		// Extra fields used only by the monthly_nth_weekday rule.
		$rule_weekday   = 'monthly_nth_weekday' === $rule ? (int) get_field( 'recurrence_weekday', $post_id )    : 0;
		$rule_occurrence = 'monthly_nth_weekday' === $rule ? (int) get_field( 'recurrence_occurrence', $post_id ) : 0;

		if ( ! $base_date ) {
			return '';
		}

		$today = gmdate( 'Y-m-d' );

		// ── One-time event ───────────────────────────────────────────────
		if ( ! $is_recurr ) {
			return $base_date >= $today ? $base_date : '';
		}

		// ── Recurring event ──────────────────────────────────────────────
		$candidate = $base_date;
		$limit     = 500; // guard against infinite loops
		$i         = 0;

		while ( $candidate < $today && $i < $limit ) {
			$candidate = self::advance_date( $candidate, $rule, $rule_weekday, $rule_occurrence );
			$i++;
		}

		// Respect optional end date.
		if ( $end_date && $candidate > $end_date ) {
			return '';
		}

		return $candidate >= $today ? $candidate : '';
	}

	/**
	 * Find the Nth (or last) occurrence of a weekday within a given month.
	 *
	 * This is the core algorithm that underpins monthly Nth-weekday recurrence.
	 * It calculates dates independently for each month rather than reusing a
	 * day-of-month offset, which would drift to the wrong weekday.
	 *
	 * Algorithm for positive occurrence:
	 *   1. Find the first day of the target month.
	 *   2. Calculate the offset (days) to the first occurrence of $weekday.
	 *   3. Add (occurrence - 1) * 7 to reach the Nth occurrence.
	 *   4. Validate the result still falls within the target month.
	 *
	 * Example — 3rd Wednesday of April 2025:
	 *   April 1 = Tuesday (w=2).  offset = (3 − 2 + 7) % 7 = 1.
	 *   day = 1 + 1 + (2 × 7) = 16.  Result: 2025-04-16.
	 *
	 * @param int $year
	 * @param int $month      1–12
	 * @param int $weekday    0 = Sunday … 6 = Saturday
	 * @param int $occurrence 1–4 for 1st–4th | -1 for last
	 * @return string|null    Y-m-d, or null when the occurrence does not exist
	 *                        in that month (e.g. a 5th Monday in a short month).
	 */
	public static function get_nth_weekday_of_month( int $year, int $month, int $weekday, int $occurrence ): ?string {
		if ( -1 === $occurrence ) {
			// Last occurrence: start from the last day and walk backwards.
			$last_day     = new \DateTime( "last day of {$year}-{$month}" );
			$last_weekday = (int) $last_day->format( 'w' );
			$offset       = ( $last_weekday - $weekday + 7 ) % 7;
			if ( $offset > 0 ) {
				$last_day->modify( "-{$offset} days" );
			}
			return $last_day->format( 'Y-m-d' );
		}

		// Positive occurrence: first weekday + (occurrence - 1) weeks.
		$first_day     = new \DateTime( sprintf( '%04d-%02d-01', $year, $month ) );
		$first_weekday = (int) $first_day->format( 'w' );
		$offset        = ( $weekday - $first_weekday + 7 ) % 7;
		$day           = 1 + $offset + ( ( $occurrence - 1 ) * 7 );

		// Guard: if the computed day overflows into the next month the occurrence
		// does not exist (e.g. 5th Wednesday in a month with only 4 Wednesdays).
		$candidate = \DateTime::createFromFormat( 'Y-n-j', "{$year}-{$month}-{$day}" );
		if ( ! $candidate || (int) $candidate->format( 'n' ) !== $month ) {
			return null;
		}

		return $candidate->format( 'Y-m-d' );
	}

	/**
	 * Advance a Y-m-d date string by one recurrence step.
	 *
	 * For the 'monthly_nth_weekday' rule the next date is computed fresh for
	 * each month via get_nth_weekday_of_month() rather than reusing the same
	 * day-of-month.  Up to 12 months are tried so that sparse occurrences
	 * (e.g. "5th Monday") are skipped gracefully when they don't exist.
	 *
	 * @param string $date       Y-m-d
	 * @param string $rule       weekly|biweekly|monthly|monthly_nth_weekday
	 * @param int    $weekday    0–6 (Sun–Sat). Used only for monthly_nth_weekday.
	 * @param int    $occurrence 1–4 or -1.   Used only for monthly_nth_weekday.
	 * @return string Y-m-d
	 */
	private static function advance_date( string $date, string $rule, int $weekday = 0, int $occurrence = 0 ): string {
		$ts = strtotime( $date );

		switch ( $rule ) {
			case 'weekly':
				return gmdate( 'Y-m-d', strtotime( '+7 days', $ts ) );

			case 'biweekly':
				return gmdate( 'Y-m-d', strtotime( '+14 days', $ts ) );

			case 'monthly':
				// Plain monthly: preserve the same day-of-month each cycle.
				return gmdate( 'Y-m-d', strtotime( '+1 month', $ts ) );

			case 'monthly_nth_weekday':
				// Calculate the Nth weekday fresh for each target month.
				// Try up to 12 consecutive months so rare occurrences (e.g.
				// "5th Monday") are skipped when they don't exist.
				$year  = (int) gmdate( 'Y', $ts );
				$month = (int) gmdate( 'n', $ts );
				for ( $try = 0; $try < 12; $try++ ) {
					$month++;
					if ( $month > 12 ) {
						$month = 1;
						$year++;
					}
					$next = self::get_nth_weekday_of_month( $year, $month, $weekday, $occurrence );
					if ( null !== $next ) {
						return $next;
					}
				}
				// Fallback: simple +1 month to prevent an infinite loop.
				return gmdate( 'Y-m-d', strtotime( '+1 month', $ts ) );

			default:
				// Unknown rule — advance by a day to avoid an infinite loop.
				return gmdate( 'Y-m-d', strtotime( '+1 day', $ts ) );
		}
	}

	// ──────────────────────────────────────────────────────────────────────
	// ACF save hook + WP-Cron refresh — kept in this class to avoid
	// scattering logic
	// ──────────────────────────────────────────────────────────────────────

	/**
	 * Registers the ACF save hook and the daily WP-Cron refresh job.
	 * Called once from functions.php.
	 */
	public static function register_hooks(): void {
		add_action( 'acf/save_post', [ self::class, 'update_next_occurrence_on_save' ], 20 );

		// Daily cron: keep next-occurrence meta fresh for recurring events so
		// values don't go stale when a month rolls over between admin saves.
		add_action( 'pc4s_refresh_recurring_events', [ self::class, 'refresh_all_recurring_events' ] );

		if ( ! wp_next_scheduled( 'pc4s_refresh_recurring_events' ) ) {
			wp_schedule_event( time(), 'daily', 'pc4s_refresh_recurring_events' );
		}
	}

	/**
	 * Recompute and persist `_event_next_occurrence` whenever an event is saved.
	 *
	 * @param int|string $post_id
	 */
	public static function update_next_occurrence_on_save( $post_id ): void {
		if ( get_post_type( $post_id ) !== Event::POST_TYPE ) {
			return;
		}

		$next = self::compute_next_occurrence( (int) $post_id );

		if ( $next ) {
			update_post_meta( (int) $post_id, self::NEXT_OCC_META, $next );
		} else {
			delete_post_meta( (int) $post_id, self::NEXT_OCC_META );
		}
	}

	/**
	 * Re-compute and persist `_event_next_occurrence` for every published event.
	 *
	 * Invoked daily by WP-Cron (pc4s_refresh_recurring_events) so that
	 * recurring events whose stored date has passed always advance to their
	 * next future occurrence, preventing them from disappearing from the
	 * Events page, front-page section, and True Blue page when a new month
	 * begins without anyone re-saving the post in the admin.
	 */
	public static function refresh_all_recurring_events(): void {
		$post_ids = get_posts( [
			'post_type'      => Event::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'fields'         => 'ids',
		] );

		foreach ( $post_ids as $post_id ) {
			$next = self::compute_next_occurrence( (int) $post_id );

			if ( $next ) {
				update_post_meta( (int) $post_id, self::NEXT_OCC_META, $next );
			} else {
				delete_post_meta( (int) $post_id, self::NEXT_OCC_META );
			}
		}
	}
}
