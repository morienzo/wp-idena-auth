jQuery(document).ready(function($) {
    // Handle login button click
    $('.idena-auth-button').on('click', function(e) {
        e.preventDefault(); // Prevent default link behavior
        
        var token = $(this).data('token');
        var siteUrl = $(this).data('site-url') || idena_auth_ajax.site_url;
        var useIndex = $(this).data('use-index') === 'true';
        var authUrl = $(this).attr('href');
        
        if (token) {
            // Save authentication info
            localStorage.setItem('idena_auth_token', token);
            localStorage.setItem('idena_auth_site_url', siteUrl);
            localStorage.setItem('idena_auth_use_index', useIndex);
            
            // Open popup - must be done immediately in click handler
            var width = 500;
            var height = 850;
            var left = (screen.width - width) / 2;
            var top = (screen.height - height) / 2;
            
            var authWindow = window.open(
                authUrl, 
                'idenaAuth',
                'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',toolbar=0,menubar=0,location=0,status=0,scrollbars=1,resizable=1'
            );
            
            // Fallback if popup is blocked
            if (!authWindow || authWindow.closed || typeof authWindow.closed == 'undefined') {
                // Popup blocked, redirect in same window
                window.location.href = authUrl;
            } else {
                // Popup opened successfully, start polling
                startAuthPolling(token, siteUrl, useIndex);
                
                // Focus on popup
                authWindow.focus();
            }
        }
    });
    
    // Polling function to check authentication status
    function startAuthPolling(token, siteUrl, useIndex) {
        var pollInterval = setInterval(function() {
            // Build check URL dynamically
            var checkUrl;
            if (useIndex) {
                checkUrl = siteUrl + '/index.php/wp-json/idena-auth/v1/check-status?token=' + token;
            } else {
                checkUrl = siteUrl + '/wp-json/idena-auth/v1/check-status?token=' + token;
            }
            
            $.ajax({
                url: checkUrl,
                type: 'GET',
                success: function(response) {
                    console.log('Checking auth status...', response);
                    
                    if (response.success && response.data.authenticated) {
                        console.log('Authentication successful!');
                        
                        // Stop polling
                        clearInterval(pollInterval);
                        localStorage.removeItem('idena_auth_token');
                        localStorage.removeItem('idena_auth_site_url');
                        localStorage.removeItem('idena_auth_use_index');
                        
                        // Redirect to callback
                        window.location.href = siteUrl + '/?idena_callback=1&token=' + token;
                    }
                },
                error: function(error) {
                    console.error('Error checking status:', error);
                }
            });
        }, 500); // Check every 0.5 seconds
        
        // Stop after 5 minutes
        setTimeout(function() {
            clearInterval(pollInterval);
            localStorage.removeItem('idena_auth_token');
            localStorage.removeItem('idena_auth_site_url');
            localStorage.removeItem('idena_auth_use_index');
            alert(idena_auth_ajax.timeout_message || 'Authentication timeout. Please try again.');
        }, 300000);
    }
    
    // Check if we're returning from Idena authentication (for mobile/same window flow)
    var savedToken = localStorage.getItem('idena_auth_token');
    var savedSiteUrl = localStorage.getItem('idena_auth_site_url');
    var savedUseIndex = localStorage.getItem('idena_auth_use_index') === 'true';
    
    if (savedToken && savedSiteUrl) {
        // We're back from Idena, start polling
        startAuthPolling(savedToken, savedSiteUrl, savedUseIndex);
    }
});