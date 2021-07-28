var unr_recaptchaWidgets = new Array();
var unr_recaptchaExecuting = new Array();
var unr_recaptchaOrigOnSubmits = new Array();

var elem2SettObj = function(hiddenInput) {
    console.groupCollapsed("elem2SettObj(hiddenInput) called");
    console.log("ARG1: hiddenInput = %o", hiddenInput);
    let settings = null;
    if( hiddenInput !== null && hiddenInput.value !== '' )
    {
        let settingsArray = hiddenInput.value.split("\"");
        console.log("settingsArray = %o", settingsArray);
        settings = {};
        settings.vers = settingsArray[0];
        settings.instNm = settingsArray[1];
        settings.recElemNm = settingsArray[2];
        settings.boundElemId = settingsArray[3];
        settings.autoBind = parseInt( settingsArray[4] ) === 1;
        settings.actNm = settingsArray[5];
        settings.formNm = settingsArray[6];
        settings.theme = settingsArray[7];
        settings.size = settingsArray[8];
        settings.tabindex = parseInt( settingsArray[9] );
        settings.badgePos = settingsArray[10];
        settings.manualExec = parseInt( settingsArray[11] ) === 1;
        settings.elem = hiddenInput;
    }
    console.log("Generated settings object: %o", settings);
    console.groupEnd();
    return settings;
};

var instNm2SettObj = function(instanceName, ignoreInstanceNotFoundError = false) {
    console.group("instNm2SettObj(instanceName, ignoreInstanceNotFoundError) called");
    console.log("ARG1: instanceName = %o", instanceName);
    console.log("ARG2: ignoreInstanceNotFoundError = %o", ignoreInstanceNotFoundError);
        
    console.log("Searching for elements named: 'unr_recaptcha_settings'");
    let settingsHiddenArray = document.getElementsByName('unr_recaptcha_settings');
    console.log("Found %d", settingsHiddenArray.length);
    let s = null;
    settingsHiddenArray.forEach(function(hiddenInput) {
        if( s === null )
        {
            console.log("Checking elem: %o for instance name of '%s'", hiddenInput, instanceName);
            let currentElem = elem2SettObj( hiddenInput );
            if( currentElem.instNm === instanceName ) {
                console.log("Matched: using this one");
                s = currentElem;
            }
        }
    });
    console.log("Found Settings object: %o", s);
    if( s === null && !ignoreInstanceNotFoundError ) {
        throw "Recpatcha Settings Hidden Input 'unr_recaptcha_settings' storing settings for instance '" + instanceName + "' not found";
    }
    console.groupEnd();
    return s;
};

var verifyCallbackV2 = function(response) {
    console.group("verifyCallbackV2(response) called");
    console.log("ARG1: response = %o", response);
    console.groupEnd();
};
var recaptchaErrorHandling = function(err, instanceName) {
    console.group("recaptchaErrorHandling(err, instanceName) called");
    console.log("ARG1: err = %o", err);
    console.log("ARG2: instanceName = %s", instanceName);
    console.log("CLEARING FLAG: setting unr_recaptchaExecuting['%s'] = false ", instanceName );
    unr_recaptchaExecuting[instanceName] = false;
    console.error('reCAPTCHA Error:', err);
    console.groupEnd();
};

var findForm = function( nameIdOrClass, instanceName )
{
    console.group("findForm(nameIdOrClass, instanceName) called");
    console.log("ARG1: nameIdOrClass = %o", nameIdOrClass);
    console.log("ARG2: instanceName = %o", instanceName);
    let foundFormElement = null;
    if(nameIdOrClass)
    {
        console.log("nameIdOrClass is set so searching by name, id or class regardless of instanceName");
        console.log("Searching by name");
        foundFormElement = document.forms[nameIdOrClass];
        if( typeof( foundFormElement ) === 'undefined' ) {
            foundFormElement = null;

            console.log("Searching by id");
            foundFormElement = document.getElementById( nameIdOrClass );
            if( foundFormElement === null ) {

                console.log("Searching by class name");
                let elementsByClass = document.getElementsByClassName( nameIdOrClass );
                console.log("Found %d html elements, now checking if one of them is a form element", elementsByClass.length);
                for(let i=0; i<elementsByClass.length; i++){
                    let foundElem = elementsByClass[i];
                    console.log("Elem %d is of type: %s", i+1, foundElem.tagName);
                    if( foundElem.tagName === 'FORM' )
                    {
                        console.warn("WARNING Using this one, but make sure there are no other form elements with this class as this could cause the wrong form to be submitted");
                        foundFormElement = foundElem;
                        break;
                    }
                }
            }
        }
    }
    else {
        console.log("nameIdOrClass is not set so using the parent form in DOM of the settings hidden ");
        console.log("Getting Settings Object for instance '%s'", instanceName);
        let s = instNm2SettObj(instanceName);
        console.log("Got Settings Object %o", s);
        console.log("Getting parent form from hidden elem object on settings object");
        foundFormElement = s.elem.form;
    }
    console.log("Finished searching, foundFormElement = %o", foundFormElement);
    if( foundFormElement === null ) {
        throw "Could not find form " (nameIdOrClass) ? "with name, id or class of: " + nameIdOrClass : "belonging to hidden input for instance " + instanceName;
    }
    console.groupEnd();
    return foundFormElement;
};

var submitAnyForm = function( formElement ) {
    console.group("submitAnyForm(formElement) called");
    console.log("ARG1: formElement = %o", formElement);
    console.log("DEBUG: formElement.submit = %o", formElement.submit );
    console.log("DEBUG: formElement.onsubmit = %o", formElement.onsubmit );
    if (typeof( jQuery ) === 'undefined') {
        console.warn("Cannot find jQuery, if your site does not use jQuery then no need to import it, if it does then make sure script include for jQuery is ABOVE this script in the <head> tag");
        console.log("Simulating submit with formElement.submit();");
        formElement.submit(); //here onsubmit is not run
        console.log("formElement.submit(); has returned, continuing");
        //submits form without triggering events or validation
    } else {
      console.log("jQuery found, so using it to trigger any form on submit events that may have been programaticallly bound to the form using jquery");
      console.log("Calling jQuery(formElement).trigger('submit');");
      jQuery(formElement).trigger('submit'); //here onsubmit and all jquery form submit triggers are run, but they are run synchronously before this command returns a value
      //MAYBE WE ONLY DO THIS OF jquery doesnt do it for us - does .trigger do this anyway if there's no form input named submit, TBC
      console.log("The above function call should submit the form properly, however sometimes a quirk of javascript gets in the way causing it to run onsubmit() but not then go on to submit()");
      console.log("Checking if we additionally need to manually force core form submit (without bound events) - we have to do this if there is a form input with name 'submit' as this overrides the form.submit() function and stops programattic submission of the form - d'oh");
      console.log("DEBUG: typeof( formElement.submit ) = %o", typeof( formElement.submit ) );

      if( typeof(formElement.submit) !== 'function' )
      {
         console.log("Yes we do have to additionally simulate core submit with HTMLFormElement.prototype.submit.call(formElement);");
         HTMLFormElement.prototype.submit.call(formElement);
         console.log("HTMLFormElement.prototype.submit.call(formElement); completed, continuing...");
      }
      //This is the same as formElement.submit();, only .submit() doesnt work if there is an input on the form
      //with name of submit; the submit method gets overwridden in the dom which is very annoying. So this owrks on any form
      //and because unr_recaptchaExecuting[instanceName] is set to true then if onsubmit roes run it will just exit straight away (see below)
      //simulates full form submission form including events and validation but relies of jquery
    }
    console.groupEnd();
};

var storeTokenAndSubmitForm = function(token, formName, instanceName) {
    console.group("storeTokenAndSubmitForm(token, formName, instanceName) called");
    console.log("*** RECAPTCHA '%s' EXECUTED SUCCESSFULLY ***", instanceName);
    console.log("ARG1: token = %o", token);
    console.log("ARG2: formName = %s", formName);
    console.log("ARG3: instanceName = %s", instanceName);
    
    //if there's a hidden element (or many) waiting to store the token then populate it/them
    var f=document.getElementsByName("unr_recaptcha_token_" + instanceName);
    console.log("Found %d hidden inputs with name '%s', setting their values to the token",f.length, "unr_recaptcha_token_" + instanceName);
    for(var i=0;i<f.length;i++){
        f[i].value = token;
    }
    
    console.log("Calling submitAnyForm( findForm( '%s', '%s' ) )", formName, instanceName);
    submitAnyForm( findForm( formName, instanceName ) );
    
    console.log("CLEARING FLAG: setting unr_recaptchaExecuting['%s'] = false ", instanceName );
    unr_recaptchaExecuting[instanceName] = false;
    
    console.groupEnd();
};

var unrExecuteRecaptchaIfManual = function( e, instanceName, ignoreRecaptchaNotFoundOnPageError = false ) {
    
    console.group("unrExecuteRecaptchaIfManual( e, instanceName, ignoreInstanceNotFoundError ) called");
    console.log("ARG1: e = %o", e);
    console.log("ARG2: instanceName = %s", instanceName);
    console.log("ARG3: ignoreRecaptchaNotFoundOnPageError = %o", ignoreRecaptchaNotFoundOnPageError);

    console.log("Often unrExecuteRecaptchaIfManual is called on form submit. but onsubmit is also called in a recaptcha's callback function after its been successfully executed. So for programmatically executed recaptures this function will run both before AND after a recapture execution!");
    console.log("CHECKING FLAG to see if the recaptcha is already executing. unr_recaptchaExecuting['%s']: %o", instanceName, unr_recaptchaExecuting[instanceName]);
    if( typeof( unr_recaptchaExecuting[instanceName] ) === "undefined" || unr_recaptchaExecuting[instanceName] !== true ) {
        console.log("It's not executing so continuing to check if it's a manual exec type recaptcha (ignoreRecaptchaNotFoundOnPageError=%o)", ignoreRecaptchaNotFoundOnPageError);
        let instanceSettingsObject = instNm2SettObj(instanceName, ignoreRecaptchaNotFoundOnPageError);
        if( instanceSettingsObject!== null && instanceSettingsObject.manualExec ) { //sometimes this function is attached to a form's onsubmit but the recaptcha in the form isnt actually programatically executed (eg v2) so in which case we must not do anything here and just allow the form submission to continue.
            //stop the form submitting this time
            console.log("The recaptcha was found and it's a manualExec one, so let's execute it");
            console.log("stopping poppagation of onsubmit event as we dont want form to submit this time round, e = %o", e);
            e.preventDefault();
            e.stopPropagation(); //stops any other submit events hoooked to the form using jquery
            
            console.log("SET FLAG: setting unr_recaptchaExecuting[%s] = true;", instanceName);           
            unr_recaptchaExecuting[instanceName] = true;
            
            console.log("*** TRIGGERING EXECUTE OF RECAPTCHA '%s' ASYNCHRONOUSLY *** grecaptcha.execute(%s, { action: '%s' });", instanceName, unr_recaptchaWidgets[ instanceName ], instanceName); 
            grecaptcha.execute(unr_recaptchaWidgets[ instanceName ], { action: instanceSettingsObject.actNm }).then(function (token) {
                //this runs after successful execution - but then so does the callback function storeTokenAndSubmitForm
                //so there's nothing we need to do here!
                //except maybe set unr_recaptchaExecuting[instanceName] = false; but need to reseatch how to do that on error or success
            });

            console.log("return false; - do not allow the browser to continue to submit the form");
            console.groupEnd();
            return false; //dont submit form automatically
        }
        console.log("RECAPTCHA WAS NOT EXECUTED, either the instance does not exist on the page or it does exist and it's not a manuallyExecuted recaptcha. Is this what you expected?");
    }
    
    console.log("return true; - do allow the browser to continue to submit the form");
    console.groupEnd();
    return true; //do submit form next
};

var unrBindRecaptchaToForm = function( formName, actionName, instanceName ) {
    console.group("unrBindRecaptchaToForm( formName, actionName, instanceName ) called"); 
    console.log('autobinding instance ' + instanceName + ' to form ' + formName + ', actionName = ' + actionName );
    
    let formElement = findForm( formName, instanceName );
    if( typeof unr_recaptchaOrigOnSubmits[instanceName] !== 'undefined' ) {
        throw new Error("unr_recaptchaOrigOnSubmits['"+instanceName+"'] already defined, have you called unrBindRecaptchaToForm() twice or got two recaptachas with same instance_name?"); 
    }
    console.log("Taking a copy of the original form.onsubmit function so we can store in on the form itself and also in our global array: unr_recaptchaOrigOnSubmits['%s']", instanceName);
    let origOnSubmitFunction = formElement.onsubmit;
    if( origOnSubmitFunction === null ) {
        console.log("The form didnt have an onsubmit function so we're using a default blank function that simply returns true");
        origOnSubmitFunction = function ( event ) { return true; };
    }
    console.log("Trying to save a copy of the function to the form element itself using DOM extension: formElement._unr_orig_onsubmit" );
    console.log("The reason i do this is if the orig function makes reference to 'this' then the context is preserved doing it this way" );
    try{
        formElement._unr_orig_onsubmit = origOnSubmitFunction;
    }
    catch( err ) { console.error(err); };
    console.log("Finished: formElement._unr_orig_onsubmit = %o", formElement._unr_orig_onsubmit ); 
    console.log("In case browser doesnt support that, saving a copy in global array: unr_recaptchaOrigOnSubmits", instanceName );
    unr_recaptchaOrigOnSubmits[instanceName] = origOnSubmitFunction;
    console.log("Finished, unr_recaptchaOrigOnSubmits['%s'] = %o", instanceName, unr_recaptchaOrigOnSubmits[instanceName] );
        
    console.log("Now generating the code for the new wrapper onsubmit function inside a string variable!" );
    //escape any " characters (and \ characters) on instanceName that we are passing into our function string
    //there shouldn't be any as instanceName is sanitized inside upnrunning-recaptcha.php, but just in case.
    let instanceNameSanitized = instanceName.replace(/\\([\s\S])|(")/, "\\$1$2");
    
    //minified version
    let newOnSubmitFunctionCode = 
        "var r=\""+ instanceNameSanitized +"\",t=event;"+
        "if(void 0===unr_recaptchaExecuting[r]||!0!==unr_recaptchaExecuting[r]){"+
            "var u=null;try{u=this._unr_orig_onsubmit(t);}catch(n){u=unr_recaptchaOrigOnSubmits[r](t);}"+
            "if(void 0===u||!1!==u)return unrExecuteRecaptchaIfManual(t,r);"+
        "}"+
        "return!0;";

/* DebugOnlyCode - START */
//this statement is stripped out of the file when it's being minified ready for production
//along with all the console.log debug statements!
    //non minified version
    newOnSubmitFunctionCode = `
        console.group("form.onsubmit called");
        let instanceName = "` + instanceNameSanitized + `";
        let e=event; // helps when minifying !
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
            if( typeof( returnValue ) === 'undefined' || returnValue !== false ) {
                console.log("form validated, so manually executing recaptcha: unrExecuteRecaptchaIfManual( e, instanceName );");
                let execReturnVal = unrExecuteRecaptchaIfManual( e, instanceName );
                console.log("unrExecuteRecaptchaIfManual returned %o, returning: %o from form.onsubmit", execReturnVal, execReturnVal);
                console.groupEnd();
                return execReturnVal;
            }
        }
        console.log('FLAG SET: recaptcha just finished executing so returning true to allow form submission');
        //The submit has already run once and validation passed and the recaptha is executing and this has triggered another form onsubmit so allow the form to submit by returning true this time
        console.log("returning true from form.onsubmit");
        console.groupEnd();
        return true; `;
/* DebugOnlyCode - END */ 

   
    console.log("Finished generating new onsubmit function javascript code..." );
    console.log(newOnSubmitFunctionCode);    
    console.log("Converting the code string to javascript function..." );
    formElement.onsubmit = new Function( "event", newOnSubmitFunctionCode );
    console.log("formElement.onsubmit now = %o", formElement.onsubmit );  
    console.groupEnd();
};