var unr_onloadCallback = function() { 
    console.group("unr_onloadCallback() called");
    console.log("Searching for hidden inputs named 'unr_recaptcha_settings' (there will be 1 per recaptcha on the page)" );
    let settingsHiddenArray = document.getElementsByName('unr_recaptcha_settings');
    console.log("Found %d, starting to loop through them", settingsHiddenArray.length );
    settingsHiddenArray.forEach(function(hiddenInput) {
        console.log("STARTING an interation of LOOP, converting elem to settings obj");
        let s = elem2SettObj( hiddenInput );
        console.log("converted, settings object s = %o", s);
        if( s !== null ) {
            //gather settings required to manually render
            console.log("Generating a Google friendly settingsObject to pass to google, and working out which element to render it to");
            let renderTo = ( s.boundElemId ) ? s.boundElemId : s.recElemNm;
            let settingsObject = { 
                'sitekey' : ( ( s.vers === 'v3' ) ? unr_recaptcha_site_key_v3 : unr_recaptcha_site_key_v2 ),
                'callback' : ( ( s.vers === 'v2' ) ? verifyCallbackV2 : function(response) { storeTokenAndSubmitForm(response, s.formNm, s.instNm); } ),
                'error-callback': function(err) { recaptchaErrorHandling( err, s.instNm ); }
            };
            if( s.theme ) settingsObject.theme = s.theme;
            if( s.size && !s.manualExec ) settingsObject.size = s.size;
            if( s.manualExec ) settingsObject.size = 'invisible';
            if( s.tabindex ) settingsObject.tabindex = s.tabindex;
            if( s.badgePos && s.vers !== 'v2' ) settingsObject.badge = s.badgePos;
            console.log("Complete, renderTo = %o, settingsObject = %o", renderTo, settingsObject);


            console.log("Starting manual render: grecaptcha.render( renderTo, settingsObject );");
            unr_recaptchaWidgets[ s.instNm ] = grecaptcha.render( renderTo, settingsObject );
            console.log("Finished manual render: renerated recaptcha id was: %o", unr_recaptchaWidgets[ s.instNm ]);
            
            //now autobind to form if necessary
            console.log("Checking whether to autobind this to it's form: s.autoBind = %o", s.autoBind);
            if( s.autoBind ) {
                console.log("Starting autobind");
                unrBindRecaptchaToForm( s.formNm, s.actNm, s.instNm );
                console.log("Completed autobind");
            }
        }
        console.log("COMPLETED an interation of LOOP, converting elem to settings obj");
    });
    console.groupEnd();
};