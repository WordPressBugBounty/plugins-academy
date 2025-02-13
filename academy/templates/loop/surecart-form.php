<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( $is_enabled_academy_login && ! is_user_logged_in() ) : ?>
    <button type="button" class="academy-btn academy-btn--bg-purple academy-btn-popup-login">
        <span class="academy-icon academy-icon--cart" aria-hidden="true"></span>
        <?php 
        if ( 'layout_two' !== $card_style ) {
            esc_html_e( 'Add to cart', 'academy' );
        }
        ?>
    </button>
<?php else : 
    $number_of_price = count( $prices );
    $cart_icon = '<span class="academy-icon academy-icon--cart" aria-hidden="true"></span>';
    $status = $number_of_price > 1 ? __( 'Enroll Now', 'academy' ) : __( 'Add to cart', 'academy' );
    $link = esc_url( $course_permalink );

    if ( 1 === $number_of_price ) {
        $price = reset( $prices );
        $checkout_url = \SureCart::pages()->url( 'checkout' );
        $link = esc_url( add_query_arg( 
            [ 'line_items' => [ [ 'price_id' => $price->id, 'quantity' => 1 ] ] ], 
            $checkout_url 
        ));
    }
    ?>
    <div class="academy-widget-enroll__add-to-cart academy-widget-enroll__add-to-cart--surecart">
        <a class="academy-btn academy-btn--bg-purple" href="<?php echo $link; ?>">
            <?php 
            if ( 1 === $number_of_price ) {
                echo $cart_icon;
            }
        
            if ( 'layout_two' !== $card_style || $number_of_price > 1 ) {
                echo esc_html( $status );
            }
            ?>
        </a>
    </div>
<?php endif; ?>
