<?php
namespace Academy\Admin\Playlist;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Academy\Admin\Playlist\Info;
use Academy\Admin\Playlist\Interfaces\Platform;
use Academy\Admin\Playlist\import\{ CourseImport, LessonImport };
class Import {
    public string $status;
    public string $course_type;
    public array $topics = [];
    public Platform $platform_data;
    public array $allowed_status = [
        'publish',
        'private',
        'draft',
        'pending',
    ];
    public array $allowed_course_type = [
        'free',
        'paid',
        'public',
    ];

    public function __construct( Info $info, string $status = 'publish', string $course_type = 'free' ) {
        $this->platform_data = $info->get();
        $this->status        = $status;
        $this->course_type   = $course_type;
    }

    public function run() : ?int {
        foreach ( $this->platform_data->videos() as $video ) {
            $this->topics[] = [
                'id' => 
                ( new LessonImport( [
                    'lesson_title'   => sanitize_text_field( $video['title'] ?? '' ),
                    'lesson_content' => wp_kses_post( $video['description'] ?? '' ),
                    'lesson_status'  => 'publish'
                ], [
                    'featured_media' => 0,
                    'attachment'     => 0,
                    'video_duration' => [ 'hours' => 0, 'minutes' => 0, 'seconds' => 0 ],
                    'video_source'   => [
                        'type' => 'youtube',
                        'url' => 'https://www.youtube.com/watch?v='.sanitize_text_field( $video['video_id'] ?? '' )
                    ],
                ] ) )->save()->get_id(),
                'name' => sanitize_text_field( $video['title'] ?? '' ),
                'type' => 'lesson',
            ];
        }
    
        $course_id = null;
        foreach ( $this->platform_data->detail() as $playlist ) {
            $course_id = ( new CourseImport( [
                'post_title'   => sanitize_text_field( $playlist['snippet']['title'] ?? '' ),
                'post_content' => wp_kses_post( $playlist['snippet']['description'] ?? '' ),'post_status'  => in_array( $this->status, $this->allowed_status ) ? $this->status : 'publish',
            ], [
                'academy_course_type' => in_array( $this->course_type, $this->allowed_course_type ) ? $this->course_type : 'free',
                'academy_course_product_id' => 0,
                'academy_course_download_id	' => 0,
                'academy_course_max_students' => 0,
                'academy_course_language' => '',
                'academy_course_difficulty_level' => 'beginner',
                'academy_course_benefits' => '',
                'academy_course_requirements' => '',
                'academy_course_audience' => '',
                'academy_course_materials' => '',
                'academy_is_enabled_course_qa' => true,
                'academy_is_enabled_course_announcements' => true,
                'academy_course_duration' => [ 0, 0, 0 ],
                'academy_course_intro_video' => [],
                'academy_course_certificate_id' => 0,
                'academy_rcp_membership_levels' => [],
                'academy_course_enable_certificate' => false,
                'academy_is_disabled_course_review' => false,
                'academy_course_curriculum'   => [
                    [
                        'title'   => 'Videos',
                        'content' => 'Videos',
                        'topics'  => $this->topics,
                    ]
                ],
            ] ) )->save()->get_id();
        }//end foreach
        return $course_id ?? 0;
    }
}