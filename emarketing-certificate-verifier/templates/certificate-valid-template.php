<div class="ecv-container ecv-valid-container">
    <div class="ecv-card">
        <div class="ecv-card-header">
            <div class="ecv-icon-wrapper">
                <svg class="ecv-icon-success" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 17.5228 17.5228 22 12 22ZM11.0026 16L18.0737 8.92893L16.6595 7.51472L11.0026 13.1716L8.17421 10.3431L6.75999 11.7574L11.0026 16Z"></path></svg>
            </div>
            <h1>Certificate Verified</h1>
            <p class="ecv-subtitle">This certifies that the following business has been successfully verified as of <?php echo date("F j, Y"); ?>.</p>
        </div>

        <div class="ecv-card-body">
            <div class="ecv-company-name">
                <?php echo esc_html($certificate->company_name); ?>
            </div>

            <div class="ecv-details-grid">
                <div class="ecv-detail-item">
                    <span class="ecv-label">Certificate ID</span>
                    <strong class="ecv-value ecv-code"><?php echo esc_html($certificate->certificate_id); ?></strong>
                </div>
                <div class="ecv-detail-item">
                    <span class="ecv-label">Trust Level</span>
                    <strong class="ecv-value">Level <?php echo esc_html($certificate->level); ?></strong>
                </div>
                <div class="ecv-detail-item">
                    <span class="ecv-label">Verified Website</span>
                    <strong class="ecv-value"><a href="<?php echo esc_url('https://' . $certificate->website_url); ?>" target="_blank" rel="noopener nofollow"><?php echo esc_html($certificate->website_url); ?></a></strong>
                </div>
                <div class="ecv-detail-item">
                    <span class="ecv-label">Date of Last Verification</span>
                    <strong class="ecv-value"><?php echo date("F j, Y", strtotime($certificate->verification_date)); ?></strong>
                </div>
            </div>

            <?php if (!empty($features_for_page)): ?>
            <div class="ecv-features">
                <h2>Verified Credentials</h2>
                <ul class="ecv-features-list">
                    <?php foreach ($features_for_page as $feature): ?>
                        <li>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M10 15.172L19.192 5.97901L20.607 7.39301L10 18L3.636 11.636L5.05 10.222L10 15.172Z"></path></svg>
                            <span><?php echo esc_html($feature); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="ecv-card-footer">
            <p class="cerd">This certificate indicates independent verification of business existence and public reputation at the date shown</p>
            <p>Verification performed by <strong>eMarketing.cy</strong>, Cyprus.</p>
            <a href="<?php echo esc_url(home_url('/')); ?>" class="ecv-home-link">Return to Homepage</a>
        </div>
    </div>
</div>