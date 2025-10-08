(function() {
    // Find all placeholder divs for our badges
    const badgePlaceholders = document.querySelectorAll('.trusted-vision-badge-embed');
    if (badgePlaceholders.length === 0) return;

    // Get the base URL for API calls and assets from the script tag itself
    const scriptTag = document.currentScript;
    const siteUrl = scriptTag.src.split('/wp-content/')[0];
    const cssUrl = siteUrl + '/wp-content/plugins/emarketing-certificate-verifier/assets/css/badge-style.css';

    // Load the CSS file dynamically
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.type = 'text/css';
    link.href = cssUrl;
    document.head.appendChild(link);

    // Process each badge placeholder on the page
    badgePlaceholders.forEach(placeholder => {
        const certId = placeholder.dataset.certId;
        const theme = placeholder.dataset.theme || 'is-dark';
        if (!certId) return;

        // Fetch the certificate data from our REST API endpoint
        fetch(`${siteUrl}/wp-json/ecv/v1/badge/${certId}`)
            .then(response => {
                if (!response.ok) throw new Error('Certificate not found');
                return response.json();
            })
            .then(data => {
                let featuresHtml = data.features.map(f => `<li>${f}</li>`).join('');
                const badgeHtml = `
                    <div class="footer-trust-badge ${theme}" tabindex="0">
                        <span class="ftb-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="9.5" fill="#01abe4"/><path d="M8.4 12.6 L11 15.2 L15.8 10.4" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span class="ftb-text"><strong>Trusted&nbsp;Vision</strong></span>
                        <span class="ftb-level" aria-label="Level ${data.level}">LV${data.level}</span>
                        <div class="ftb-tooltip" role="tooltip" aria-hidden="true">
                            <div class="ftb-tip-header">Trusted Vision — Level ${data.level}</div>
                            <div class="ftb-tip-meta">
                                <div><span>Issuer</span><strong>${data.issuer}</strong></div>
                                <div><span>Verified</span><time datetime="${data.date_iso}">${data.date_formatted}</time></div>
                                <div><span>Cert&nbsp;ID</span><code>${data.cert_id}</code></div>
                            </div>
                            <ul class="ftb-tip-chips">${featuresHtml}</ul>
                            <div class="tv-note">Badge indicates independent verification at date shown.</div>
                            <a class="ftb-tip-verify" href="${data.verify_link}" target="_blank" rel="noopener">Verify this badge</a>
                        </div>
                    </div>
                `;
                placeholder.innerHTML = badgeHtml;
                
                // --- NEW INTERACTION LOGIC ---
                const badge = placeholder.querySelector('.footer-trust-badge');
                if(!badge) return;

                // For desktop: show on hover, hide when mouse leaves the entire component
                placeholder.addEventListener('mouseenter', () => badge.classList.add('tooltip-show'));
                placeholder.addEventListener('mouseleave', () => badge.classList.remove('tooltip-show'));

                // For mobile & accessibility: toggle on click/tap
                placeholder.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isShown = badge.classList.toggle('tooltip-show');
                    
                    // Close all other badges when one is opened
                    if (isShown) {
                        document.querySelectorAll('.footer-trust-badge').forEach(otherBadge => {
                            if (otherBadge !== badge) {
                                otherBadge.classList.remove('tooltip-show');
                            }
                        });
                    }
                });

            })
            .catch(error => {
                placeholder.innerHTML = ``;
                console.error('Trusted Vision Badge Error:', error.message);
            });
    });
    
    // Add a global click listener to close any open tooltips when clicking away
    document.addEventListener('click', () => {
        document.querySelectorAll('.footer-trust-badge').forEach(badge => {
            badge.classList.remove('tooltip-show');
        });
    });

})();