<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<?php if ( ! $curriculums ) : ?>
<p class="academy-empty-message"> <?php echo esc_html__( 'No topics found!', 'academy' ); ?></p>
	<?php
	return;
endif;
?>
<div class="academy-lesson-sidebar-content--wrapper">
	<?php
	foreach ( $curriculums as $curriculum ) : ?>
		<div class="academy-learn-page-topics">
			<button class="academy-learn-page-topics-title" type="button">
				<span class="academy-icon academy-icon--simple-add"></span>
				<span class="academy-learn-page-topics-title__text"><?php echo esc_html( $curriculum['title'] ); ?></span>
			</button>
			<?php foreach ( $curriculum['topics'] as $topic ) : ?>
			<div class="academy-learn-page-topics-lesson-items">
				<?php if ( 'sub-curriculum' === $topic['type'] ) : ?>
				<div class="academy-learn-page-topics-sub-topics academy-learn-page-topics-sub-topics--is-open">
					<button type="button" class="academy-sub-topics-title">
						<span class="academy-icon academy-icon--simple-add"></span>
						<span class="academy-sub-topics-title__text"><?php echo esc_html( $topic['name'] ); ?></span>
					</button>
					<?php
					foreach ( $topic['topics'] as $sub_topic ) : ?>
						<div class="academy-sub-topics-lesson-items">
							<div class="academy-sub-topics-lesson-item <?php echo \Academy\Helper::is_active_curriculum_content( $sub_topic ) ? 'academy-sub-topics-lesson-item--playing' : ''; ?>">
								<div class="academy-sub-topics-lesson-item__input <?php echo \Academy\Helper::is_active_curriculum_content( $sub_topic ) ? 'academy-sub-topics-lesson-item--playing' : ''; ?>">
									<form id="save_mark_topic" class="save_mark_topic" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<?php wp_nonce_field( 'academy_nonce', 'security' ); ?>
										<input type="hidden" name="action" value="academy/save_topic_mark_as_complete">
										<input type="hidden" name="course_id" value="<?php echo esc_attr( \Academy\Helper::get_the_current_course_id() ); ?>">
										<input type="hidden" name="topic_type" value="<?php echo esc_attr( $sub_topic['type'] ); ?>">
										<input type="hidden" name="topic_id" value="<?php echo esc_attr( $sub_topic['id'] ); ?>">
										<input type="checkbox" class="topic_check" value="<?php echo esc_attr( $sub_topic['id'] ); ?>" <?php echo esc_attr( $sub_topic['is_completed'] ? 'checked' : '' ); ?>>
									</form>
								</div>
								<a href="<?php echo esc_url( \Academy\Helper::get_topic_play_link( $sub_topic ) ); ?>" class="academy-sub-topics-lesson-item__btn">
									<span class="academy-entry-left">
										<i class="<?php echo esc_attr( \Academy\Helper::get_topic_icon_class_name( $sub_topic['type'] ) ); ?>" aria-hidden="true"></i>
										<span class="academy-sub-topics-lesson-item__text">
											<?php echo esc_html( $sub_topic['name'] ); ?>
										</span>
									</span>
								</a>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<?php else : ?>
					<div class="academy-learn-page-topics-lesson-item <?php echo \Academy\Helper::is_active_curriculum_content( $topic ) ? 'academy-learn-page-topics-lesson-item--playing' : ''; ?>">
						<div class="academy-learn-page-topics-lesson-item__input <?php echo \Academy\Helper::is_active_curriculum_content( $topic ) ? 'academy-learn-page-topics-lesson-item--playing' : ''; ?>">
						<form id="save_mark_topic" class="save_mark_topic" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'academy_nonce', 'security' ); ?>
							<input type="hidden" name="action" value="academy/save_topic_mark_as_complete">
							<input type="hidden" name="course_id" value="<?php echo esc_attr( \Academy\Helper::get_the_current_course_id() ); ?>">
							<input type="hidden" name="topic_type" value="<?php echo esc_attr( $topic['type'] ); ?>">
							<input type="hidden" name="topic_id" value="<?php echo esc_attr( $topic['id'] ); ?>">
							<input type="checkbox" class="topic_check" value="<?php echo esc_attr( $topic['id'] ); ?>" <?php echo esc_attr( $topic['is_completed'] ? 'checked' : '' ); ?>>
						</form>
						</div>
						<a href="<?php echo esc_url( \Academy\Helper::get_topic_play_link( $topic ) ); ?>" class="academy-topics-lesson-item__btn">
							<span class="academy-entry-left">
								<i class="<?php echo esc_attr( \Academy\Helper::get_topic_icon_class_name( $topic['type'] ) ); ?>" aria-hidden="true"></i>
								<span class="academy-learn-page-topics-lesson-item__text"> <?php echo esc_html( $topic['name'] ); ?> </span>
							</span>
						</a>
					</div>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div>
	<?php endforeach; ?>
</div>
