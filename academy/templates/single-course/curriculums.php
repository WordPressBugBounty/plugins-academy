<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( empty( $curriculums ) ) {
	return;
} ?>
<div class="academy-single-course__content-item academy-single-course__content-item--curriculum">
	<div class="academy-course-curriculum-header">
		<h4 class="academy-curriculum-title"><?php esc_html_e( 'Course Content', 'academy' ); ?></h4>
	</div>

	<ul class="academy-accordion">
		<?php
		if ( is_array( $curriculums ) && count( $curriculums ) ) :
			foreach ( $curriculums as $curriculum_item_index => $curriculum_item ) :
				?>
		<li <?php echo 0 === $curriculum_item_index && true === $topics_first_item_open_status ? 'class="active"' : ''; ?>>
			<a class="academy-accordion__title"><?php echo esc_html( $curriculum_item['title'] ); ?></a>
			<div class="academy-accordion__body">
				<?php
				if ( is_array( $curriculum_item['topics'] ) && count( $curriculum_item['topics'] ) > 0 ) {
					?>
						<ul class="academy-lesson-list">
					<?php
					foreach ( $curriculum_item['topics'] as $index => $topic ) {
						if ( 'sub-curriculum' === $topic['type'] ) : ?>
								<ul class="academy-accordion academy-sub-curriculum-accordion">
								<li <?php echo 0 === $curriculum_item_index && 'on' === $topics_first_item_open_status ? 'class="active"' : ''; ?>>
								<a class="academy-accordion__title"><?php echo esc_html( $topic['name'] ); ?></a>
								<div class="academy-accordion__body">
									<ul class="academy-lesson-list">
									<?php
									// Sub Curriculums loop
									if ( is_array( $topic['topics'] ) && count( $topic['topics'] ) > 0 ) :
										foreach ( $topic['topics'] as $sub_index => $sub_topic ) : ?>
											<li class="academy-lesson-list__item academy-sub-lesson-list__item">
											<div class="academy-entry-content">
											<i class="<?php echo esc_attr( \Academy\Helper::get_topic_icon_class_name( $sub_topic['type'] ) ); ?>" aria-hidden="true"></i>
											<h4 class="academy-entry-title"><?php echo esc_html( $sub_topic['name'] ); ?></h4>
											</div>
												<div class="academy-entry-control">
												<?php
												if ( $sub_topic['is_accessible'] ) :
													?>
													<a href="<?php echo esc_url( \Academy\Helper::get_topic_play_link( $sub_topic ) ); ?>" class="academy-btn-play academy-btn-lesson-preview">
														<i class="academy-icon academy-icon--eye"></i>
													</a>
												<?php else : ?>
													<a href="javascript:void(0);" class="academy-btn-play academy-btn-play-lock" disabled="disabled">
														<i class="academy-icon academy-icon--lock"></i>
													</a>
												<?php endif; ?>
												</div>
											</li>
											<?php
									endforeach;
									endif;
									// End Sub Curriculums loop
									?>
									</ul>
										</div>
											</li>
												</ul>
									<?php else :
										?>
								<li class="academy-lesson-list__item">
									<div class="academy-entry-content">
										<i class="<?php echo esc_attr( \Academy\Helper::get_topic_icon_class_name( $topic['type'] ) ); ?>" aria-hidden="true"></i>
										<h4 class="academy-entry-title"><?php echo esc_html( $topic['name'] ); ?></h4>
										<?php
										if ( isset( $topic['duration'] ) ) : ?>
										<span class="academy-entry-time"><?php echo esc_html( $topic['duration'] ); ?></span>
											<?php
											endif;
										?>
									</div>
									<div class="academy-entry-control">
										<?php
										if ( $topic['is_accessible'] ) :
											?>
											<a href="<?php echo esc_url( \Academy\Helper::get_topic_play_link( $topic ) ); ?>" class="academy-btn-play academy-btn-lesson-preview">
												<i class="academy-icon academy-icon--eye"></i>
											</a>
										<?php else : ?>
											<a href="javascript:void(0);" class="academy-btn-play academy-btn-play-lock" disabled="disabled">
												<i class="academy-icon academy-icon--lock"></i>
											</a>
										<?php endif; ?>
									</div>
								</li>
										<?php
							endif;
					}//end foreach
					?>
						</ul>
					<?php
				}//end if
				?>
			</div>
		</li>
				<?php
				endforeach;
			endif;
		?>
	</ul>
</div>
