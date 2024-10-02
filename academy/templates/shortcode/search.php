<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<div class="academy-search-form-wrap">
	<form id="academy_search_form" class="academy-search-form" action="#" method="post">	
		<div class="academy-search-form__field">
			<input class="academy-search-form__field-input" name="keyword" type="text" value="" placeholder="<?php echo esc_attr( $placeholder ); ?>">
			<div class="academy-search-form__field-icon"><span class="academy-icon academy-icon--search" aria-hidden="true"></span></div>
		</div>
		<div class="academy-search-form__results"></div>
	</form>
</div>
