<div class="ecv-container">
    <div class="ecv-card">
        <div class="ecv-header">
            <span class="ecv-status-icon-valid">&#10004;</span>
            <h2>Certificate Verified</h2>
        </div>
        <div class="ecv-body">
            <p class="ecv-intro">This certifies that the following business has been successfully verified by eMarketing.cy.</p>
            
            <div class="ecv-company-name">
                <?php echo esc_html($certificate->company_name); ?>
            </div>

            <div class="ecv-details">
                <div><span>Trust Level</span><strong>Level <?php echo esc_html($certificate->level); ?></strong></div>
                <div><span>Issuer</span><strong>eMarketing.cy</strong></div>
                <div><span>Verified On</span><time datetime="<?php echo esc_attr($certificate->verification_date); ?>"><?php echo date("M j, Y", strtotime($certificate->verification_date)); ?></time></div>
                <div><span>Certificate ID</span><code><?php echo esc_html($certificate->certificate_id); ?></code></div>
            </div>

            <?php 
                // You can add custom logic here based on the level
                $level_features = [
                    1 => ['Verified business identity'],
                    2 => ['Verified business identity', 'Address & contact confirmed'],
                    3 => ['Verified business identity', 'Address & contact confirmed', 'Social media audit'],
                    4 => ['Verified business identity', 'Address & contact confirmed', 'Social media audit', 'Reputation check'],
                    5 => ['Verified business identity', 'Address & contact confirmed', '5★ reviews audited', 'Annual re-verification', 'Social media audit', 'Reputation check', '10+ years operating'],
                ];
            ?>
            <ul class="ecv-chips">
                <?php foreach ($level_features[$certificate->level] as $feature): ?>
                    <li><?php echo esc_html($feature); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="ecv-footer">
            <p>This badge indicates independent verification of business existence and public reputation at the date shown.</p>
        </div>
    </div>
</div>