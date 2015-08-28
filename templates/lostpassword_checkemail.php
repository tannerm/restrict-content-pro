<?php if( ! is_user_logged_in() ) : ?>

    <?php do_action( 'rcp_before_lostpassword_checkemail_message' ); ?>

    <p><?php _e('Check your e-mail for the confirmation link.', 'rcp'); ?></p>

    <?php do_action( 'rcp_after_lostpassword_checkemail_message' ); ?>

<?php else : ?>
    <div class="rcp_logged_in"><a href="<?php echo wp_logout_url( home_url() ); ?>"><?php _e( 'Logout', 'rcp' ); ?></a></div>
<?php endif; ?>