<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$submit_disabled_attr  = ( 'all' === $layout && $is_required ) ? 'disabled' : '';
$submit_disabled_class = ( 'all' === $layout && $is_required ) ? ' academy-btn--disabled' : '';
?>
<div class="academy-quiz-buttons">
    <!-- For 'all' layout, only a submit button is needed -->
    <div></div>
    <button
        type="submit"
        class="academy-btn academy-btn--next<?php echo esc_attr( $submit_disabled_class ); ?>"
        id="academy_quiz_form_submit"
        <?php echo esc_attr( $submit_disabled_attr ); ?>
        style="display:block;"
    >
    <?php echo esc_html__( 'Submit Quiz', 'academy' ); ?>
    </button>
</div>