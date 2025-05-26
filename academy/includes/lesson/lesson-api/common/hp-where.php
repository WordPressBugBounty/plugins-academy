<?php
namespace Academy\Lesson\LessonApi\Common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class HpWhere extends Where {
	protected function clause_in( string $key, array $value ): void {
		$placeholders = implode( ',', array_fill( 0, count( $value ), '%s' ) );
		$this->clauses[] = "({$this->alias}meta_key = %s AND {$this->alias}meta_value IN ($placeholders))";
		$this->values = array_merge( $this->values, [ $key ], $value );
	}

	protected function clause_not_in( string $key, array $value ): void {
		$placeholders = implode( ',', array_fill( 0, count( $value ), '%s' ) );
		$this->clauses[] = "({$this->alias}meta_key = %s AND {$this->alias}meta_value NOT IN ($placeholders))";
		$this->values = array_merge( $this->values, [ $key ], $value );
	}

	protected function clause_like( string $key, string $value ): void {
		$this->clauses[] = "({$this->alias}meta_key = %s AND {$this->alias}meta_value  LIKE %s)";
		$this->values = array_merge( $this->values, [ $key, "%{$value}%" ] );
	}

	protected function clause_not_like( string $key, string $value ): void {
		$this->clauses[] = "({$this->alias}meta_key = %s AND {$this->alias}meta_value NOT LIKE %s)";
		$this->values = array_merge( $this->values, [ $key, "%{$value}%" ] );
	}

	protected function clause_default( string $key, string $value, string $compare ): void {
		$this->clauses[] = "({$this->alias}meta_key = %s AND {$this->alias}meta_value  {$compare} %s)";
		$this->values = array_merge( $this->values, [ $key, $value ] );
	}
	protected function clause_exists( string $key ): void {
		global $wpdb;
		$this->clauses[] = "EXISTS (SELECT 1 FROM {$wpdb->prefix}academy_lessonmeta lmq WHERE lmq.lesson_id = l.ID AND lmq.meta_key = %s)";
		$this->values[] = $key;
	}

	protected function clause_not_exists( string $key ): void {
		global $wpdb;
		$this->clauses[] = "NOT EXISTS (SELECT 1 FROM {$wpdb->prefix}academy_lessonmeta lmq WHERE lmq.lesson_id = l.ID AND lmq.meta_key = %s)";
		$this->values[] = $key;
	}
}
