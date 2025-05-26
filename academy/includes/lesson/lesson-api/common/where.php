<?php
namespace Academy\Lesson\LessonApi\Common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class Where {
	protected array $conditions;
	protected array $values = [];
	protected string $relation;
	protected array $clauses = [];
	public ?string $alias = null;
	public function __construct( array $conditions, string $relation = 'AND', ?string $alias = null ) {
		$this->conditions = $conditions;
		$this->relation = $relation;
		$this->alias = empty( $alias ) ? '' : ( strpos( $alias, '.' ) === false ? "{$alias}." : $alias );
		$this->iterate();
	}

	public function query(): string {
		return implode( " {$this->relation} ", $this->clauses );
	}

	public function values(): array {
		return $this->values;
	}

	protected function clause_in( string $key, array $value ): void {
		$placeholders = implode( ',', array_fill( 0, count( $value ), '%s' ) );
		$this->clauses[] = "{$key} IN ($placeholders)";
		$this->values = array_merge( $this->values, $value );
	}

	protected function clause_not_in( string $key, array $value ): void {
		$placeholders = implode( ',', array_fill( 0, count( $value ), '%s' ) );
		$this->clauses[] = "{$key} NOT IN ($placeholders)";
		$this->values = array_merge( $this->values, $value );
	}

	protected function clause_like( string $key, string $value ): void {
		$this->clauses[] = "{$key} LIKE %s";
		$this->values[] = "%{$value}%";
	}

	protected function clause_not_like( string $key, string $value ): void {
		$this->clauses[] = "{$key} NOT LIKE %s";
		$this->values[] = "%{$value}%";
	}

	protected function clause_default( string $key, string $value, string $compare ): void {
		$this->clauses[] = "{$key} {$compare} %s";
		$this->values[] = $value;
	}
	protected function iterate(): void {
		foreach ( $this->conditions as $condition ) {
			if (
				isset( $condition['relation'] ) &&
				in_array( $relation = strtoupper( $condition['relation'] ), [ 'AND', 'OR' ] )
			) {
				unset( $condition['relation'] );
				$ins = new static( $condition, $relation, $this->alias );
				$this->clauses[] = '(' . $ins->query() . ')';
				$this->values = array_merge( $this->values, $ins->values() );
			} elseif ( isset( $condition['key'] ) ) {
				$key = $condition['key'];
				$value = isset( $condition['value'] ) ? $condition['value'] : '';
				$compare = isset( $condition['compare'] ) ? $condition['compare'] : '=';
				$method = str_replace( '-', '_', sanitize_title( $compare ) );

				if ( method_exists( $this, $method = "clause_{$method}" ) ) {
					$this->{$method}( $key, $value );
				} else {
					$this->clause_default( $key, $value, $compare );
				}
			}
		}//end foreach
	}
}
