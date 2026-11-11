<?php
/*
Template Name: Registration Success
*/

get_header(); ?>

<div id="welcome">
    <section class="breadcrumb">
        <div class="container">
            <h1 class="pagetitle">Application Received</h1>
        </div>
    </section>

    <div class="container pagesection">
        <div style="max-width: 800px; margin: 0 auto; text-align: center; padding: 40px 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-top: 5px solid #28a745;">
            
            <div style="margin-bottom: 20px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>

            <h2 style="color: #333; margin-bottom: 15px; font-size: 28px;">Application Submitted Successfully!</h2>
            <h3 style="color: #666; font-weight: normal; font-size: 18px; margin-top: 0;">We have received your membership request.</h3>

            <hr style="margin: 30px 0; border: 0; border-top: 1px solid #eee;">

            <div style="text-align: left; line-height: 1.8; color: #444; font-size: 16px;">
                <p>Dear Batchmate,</p>
                <p>Thank you for applying for membership in the <?php echo batchmate_org_label( 'full' ); ?>. We have successfully received your application and payment details.</p>
                
                <div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #007cba; margin: 20px 0;">
                    <strong>Next Steps:</strong>
                    <ul style="margin-bottom: 0; margin-top: 5px; padding-left: 20px;">
                        <li>Your application is currently <strong>Pending Review</strong>.</li>
                        <li>Our admin team will verify your payment and information (this usually takes 24-48 hours).</li>
                        <li>Once approved, you will receive a confirmation email and will be able to log in to the website.</li>
                    </ul>
                </div>

                <?php
                $success_settings = get_option('batchmate_settings', []);
                $contact_email    = ! empty( $success_settings['org_email'] ) ? $success_settings['org_email'] : '';
                if ( $contact_email ) :
                ?>
                <p>If you have any questions, please contact us at: <a href="mailto:<?php echo esc_attr( $contact_email ); ?>"><?php echo esc_html( $contact_email ); ?></a></p>
                <?php endif; ?>
            </div>

            <div style="margin-top: 40px;">
                <a href="<?php echo home_url('/'); ?>" class="button" style="padding: 12px 25px; background: #007cba; color: #fff; text-decoration: none; border-radius: 4px; font-weight: bold;">Back to Home</a>
                <a href="<?php echo home_url('/members-list/'); ?>" class="button" style="padding: 12px 25px; background: #f0f0f0; color: #333; text-decoration: none; border-radius: 4px; margin-left: 10px;">View Members List</a>
            </div>

        </div>
    </div>
</div>

<?php get_footer(); ?>