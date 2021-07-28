var event = null;
fish = function(dummy) {


// ********* COPY AND PASTE FUNCTION STRING FROM CONSOLE LOG HERE *****************
        console.group("form.onsubmit called");
    
        let instanceName = "login";
        let e=event; /* helps when minifying ! */
        
        console.log("DEBUG: e = event = %o", e );
        console.log("DEBUG: this = %o", this );
        console.log("DEBUG: instanceName = %s", instanceName );
        console.log("DEBUG: unr_recaptchaExecuting['%s'] = %s", instanceName, unr_recaptchaExecuting[instanceName] );
    
        if( typeof( unr_recaptchaExecuting[instanceName] ) === 'undefined' || unr_recaptchaExecuting[instanceName] !== true ) {
            console.log("FLAG NOT SET: we've not started recaptcha, so running form validation (orig onsubmit function)");
            
            console.log("NOTE: we will call orig onsubmit function once and once only, there will be a second pass round this wrapper onsubmit but it wont run the orig onsubmit on second pass" );
            let returnValue = null;
            try {
                console.log("ATTEMPT CALLING ORIG ONSUBMIT FUNCTION USING DOM EXTENSION to preserve function context: this._unr_orig_onsubmit(e) = %o", this._unr_orig_onsubmit );
                returnValue = this._unr_orig_onsubmit(e);
            }
            catch (err) {
                console.error("WARNING: Attempt to access this._unr_orig_onsubmit(e) failed with error %o, Instead trying: unr_recaptchaOrigOnSubmits['%s'](e); <--may have unexpected results if forms orig onsubmit function references 'this' keyword as the global array is a different function context", err, instanceName );
                returnValue = unr_recaptchaOrigOnSubmits[instanceName](e);
            }
            console.log("ORIG ONSUBMIT FUNCTION RAN: returnValue = %o (note both undefined and true means passed validation (undefined just means no return value), false means failed validation so dont continue to execute recaptcha and submit the form. And null probably means function failed to run", returnValue );
            if( typeof( returnValue ) === 'undefined' || returnValue !== false ) { /* if it doesnt return a value that means it passed! only false means fail */
                console.log("form validated, so manually executing recaptcha: unrExecuteRecaptchaIfManual( e, instanceName );");
                let execReturnVal = unrExecuteRecaptchaIfManual( e, instanceName );
                console.log("unrExecuteRecaptchaIfManual returned %o, returning: %o from form.onsubmit", execReturnVal, execReturnVal);
                console.groupEnd();
                return execReturnVal;
            }
        }
    
        console.log('FLAG SET: recaptcha just finished executing so returning true to allow form submission');
        /* The submit has already run once and validation passed and the recaptha is executing and this has triggered another form onsubmit so allow the form to submit by returning true this time */
        
        console.log("returning true from form.onsubmit");
        console.groupEnd();
        return true; 
// ********* ***************************** *****************

        
}



//used after minification in order to stringfy and copy and paste into unr-recaptcha-4debugging.js!
/*
var dummyFunc = function (dummyArg) {



    var r="login",t=event;
        if(void 0===unr_recaptchaExecuting[r]||!0!==unr_recaptchaExecuting[r]){
            var u=null;try{u=this._unr_orig_onsubmit(t);}catch(n){u=unr_recaptchaOrigOnSubmits[r](t);}
            if(void 0===u||!1!==u)return unrExecuteRecaptchaIfManual(t,r);
        }
        return!0;



};

var dummyFunc = function (dummyArg) {
        instanceNameSanitized = 'login';
        
        
        
    "var r=\""+ instanceNameSanitized +"\",t=event;"+
        "if(void 0===unr_recaptchaExecuting[r]||!0!==unr_recaptchaExecuting[r]){"+
            "var u=null;try{u=this._unr_orig_onsubmit(t);}catch(n){u=unr_recaptchaOrigOnSubmits[r](t);}"+
            "if(void 0===u||!1!==u)return unrExecuteRecaptchaIfManual(t,r);"+
        "}"+
        "return!0;";



};
*/