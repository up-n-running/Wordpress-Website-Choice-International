<?php
/*
Plugin Name: up-n-running Events Manager Extras
Version: 1.0
Plugin URI: http://www.upnrunning.co.uk
Description: Add booking form extras, extra conditional placeholders, and zoom functionality to WP Events Manager plugin http://wp-events-plugin.com
Author: John Milner
Author URI: http://www.upnrunning.co.uk
Text Domain: events-manager
*/

/*
Copyright (c) 2020, John Milner

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

/* CONSTANTS DEFINITIONS */
//Version Number
define('UEMA_PLUGIN_VERSION', 1.100); //self expanatory
define('UEMA_USE_UEMA_ZOOM_URLS', FALSE); //OPTIONAL FUNCTIONALITY NOW OVERRWIDDEN BY OFFICIAL ZOOM PLUGIN
define('UEMA_LINK_ACTIVATION_MINUTES_OVERRIDE', -1); //CAN SET TO -1 OR 60 (hour) or 1440 (day) OR COMMENT OUT (DO NOT DEFINE) TO NOT USE THIS
define('UEMA_CHOOSE_PASSWORD_WHEN_REGISTERING', TRUE); //

//Zoom Settings Table Name Constant
global $wpdb;
if( EM_MS_GLOBAL ){
	$prefix = $wpdb->base_prefix;
}else{
	$prefix = $wpdb->prefix;
}
define('UEME_ZOOM_SETTINGS_TABLE',$prefix.'em_ueme_zoom_settings'); //TABLE NAME


/**
 * runs sql install/upgrade script whenever plugin just installed/activated/upgraded
 */
require_once( 'ueme-install.php');
register_activation_hook( __FILE__, 'ueme_install' );

/* Check plugin dependencies on load, shows warning in admin console if main Events Manager plugin is not installed */
check_dependencies();

/**
 * Code to manage the Zoom Meta Box on the Event and Recurring Event Admin pages
 * upnrunning_em_zoom_settings_meta_boxes() Adds the metabox to the page
 * upnrunning_em_zoom_settings_metabox() generates the HTML for the metabox
 */

//Hook to add the 'upnrunning Zoom Integration' meta box into both edit event and edit recurring event admin pages
//NOTE: Uses EM_POST_TYPE_EVENT which is defined in the main Events Manager plugin code.
function upnrunning_em_zoom_settings_meta_boxes()
{
    add_meta_box('em-event-zoom-settings', 'upnrunning Zoom Integration', 'upnrunning_em_zoom_settings_metabox', EM_POST_TYPE_EVENT, 'side','high');
    add_meta_box('em-event-zoom-settings', 'upnrunning Zoom Integration', 'upnrunning_em_zoom_settings_metabox', 'event-recurring', 'side','high');
}
if( UEMA_USE_UEMA_ZOOM_URLS )
{
    add_action('add_meta_boxes', 'upnrunning_em_zoom_settings_meta_boxes');
}
 

//render the controls that go inside the meta box (drop down of zoom settings options) and zoom url
//NOTE: Uses EM_EVENTS_TABLE which is defined in the main Events Manager plugin code.
////TO DO: Add a 3 option select only on the recurrence form and only when in edit mode that prompts the user for
// 1) do not update any zoom settings on children (default when no onchange):
// 1) Update zoom settings only unvhanged children (default for Detached links onchange)
// 2) update zoome settings on children all even changed (other fields only udated on unchanged children) (default for attached links onchange)
//and have a little helper hyperlink under the setting for when it appears that says 'what's this?'
function upnrunning_em_zoom_settings_metabox()
{
    global $wpdb, $EM_Event;

    //work out if we are on Recurring Event screen or the Individual Event screen 
    //and if we're on the individual event screen - is it a child event with a parent recurrence or a lone event?
    $is_recurrence_parent = isset( $EM_Event->recurrence ) && $EM_Event->recurrence === 1;
    $is_recurrence_child  = isset( $EM_Event->recurrence_id ) && (int)$EM_Event->recurrence_id > 0;
    $is_singular  = !$is_recurrence_parent && !$is_recurrence_child;    
    $parent_recurrence_zoom_settings = null; //used to store the parent's details if and only if it's a child event
    
    //If it is the child of a recurrence then we need to know the zoom setting status of the parent recurrence
    if($is_recurrence_child) {
        //find parent event and if linked find the zoom settings for it (otherwise zoom settings return null)
        $sql = $wpdb->prepare( "SELECT e.event_id, e.ueme_zoom_url, z.zoomset_id, z.zoomset_detach_recurrences
                FROM " . EM_EVENTS_TABLE . " e
                LEFT OUTER JOIN " . UEME_ZOOM_SETTINGS_TABLE . " z ON z.zoomset_id = e.ueme_zoom_settings_id
                WHERE e.recurrence AND e.event_id = %d", $EM_Event->recurrence_id);
        $parent_recurrence_zoom_settings = $wpdb->get_row($sql, OBJECT);
    }
    
    //dropdown to change zoom settings should not contain any attached' options if we are in 
    //detached mode as detached mode means the parent doesnt have a zoom link and attached mode 
    //requires a zoom link on parent
    $deny_attached_settings_in_drop_down = $is_singular || ( $is_recurrence_child && ( !isset( $parent_recurrence_zoom_settings->zoomset_detach_recurrences ) || (int)$parent_recurrence_zoom_settings->zoomset_detach_recurrences === 1 ) );
    $some_settings_were_denied = false; //keep track of this so we can show a little helper on screen if some settings are removed
    
    //this one is an edge case - if a recurrence child event has an 'attached'-style zoome setting set,
    //then if the user detached the child from it's parent then the next time the user opens the event record in admin console
    //the setting_id on the actual record will be removed from the zoom setting drop down as it is not 
    //really allowed on lone event records, so we need to keep track of when this happens so that we can
    //probpt the user to set a new setting id more suitable for a lone event. drop down will default to 
    //no zoom event in that case so if user hits save without choosing a new more suitable value the event record will be 
    //saved with soom setting of 'not a zoom event' and events functionality will be deactivated on this event
    //which would cause anyone booking themselved on the event to NOT receive the zoom link they need.
    $the_events_actual_zoom_setting_id_was_denied = false; 
    
    //get all zoom_settings_options from database and loop through to generate the drop down box HTML and a javascript 
    //array of available zoom settings to display. The js array is used by the onchange js function of the drop down
    //when user selectd a setting from the drop down the js onchange function uses the array to look up
    //everything it needs to know about that newly selected setting.
    //view sorce of the event admin page if you want to see what the javascript array actually looks like
    $db_zoom_settings = $wpdb->get_results( "SELECT zoomset_id, zoomset_name, zoomset_detach_recurrences FROM " . UEME_ZOOM_SETTINGS_TABLE . " ORDER BY zoomset_id ASC" );
    $drop_down_options = '';
    $js_array_elements = '';
    $js_delimiter = '';

    foreach ($db_zoom_settings as $db_setting) {
        $drop_down_selected_insert = ((int)$db_setting->zoomset_id === $EM_Event->ueme_zoom_settings_id) ? ' selected="selected"' : '';
        if( !($deny_attached_settings_in_drop_down && (int)$db_setting->zoomset_detach_recurrences == 0 ) ) {
            $drop_down_options .= "        <option value=\"{$db_setting->zoomset_id}\"{$drop_down_selected_insert}>{$db_setting->zoomset_name}</option>\n";
        }
        else
        {
            $some_settings_were_denied = true;
            $the_events_actual_zoom_setting_id_was_denied = $drop_down_selected_insert != '';
        }
        $js_array_elements .= "{$js_delimiter} '{$db_setting->zoomset_id}':[{$db_setting->zoomset_detach_recurrences}, '" . esc_js($db_setting->zoomset_name) . "']";
        $js_delimiter = ', ';
    }
    
    $drop_down_insert

    //In this next section we create a massive javscript function on the fly to manage all of the different things
    //that happen when use user selects a new setting in the zoom settings drop down. Ive designed these fields
    //to have interactive context sensitive labels that let you hover-over them to offer extra assistance to the user 
    //relevant to whatever settings they have selected. It gets a bit complex here but its important that the
    //user unserstands when their zoom link may or may not get overridden by a parent recurring event being updated.
    //A zoom link chaging after people have booked on the event means a lot of very disappointed people trying to
    //access an event and not being able to :(
            
    //TIP FOR DEVS - PUT WORD WRAP ON IN YOUR TEXT EDITOR WHEN WORKING ON THIS OR IT GETS UNMANAGEABLE!
?>
<script type="text/javascript">
    //<![CDATA[
    const zoomSettingArray = {'-1':[null, 'Not a Zoom event'], <?php echo $js_array_elements ?>};
    const isRecurrenceParent = <?php echo $is_recurrence_parent ? 'true' : 'false' ?>;
    const isRecurrenceChild  = <?php echo $is_recurrence_child ? 'true' : 'false' ?>;
    const isSingular  = <?php echo $is_singular ? 'true' : 'false' ?>;
    
    //this will be null if its not the child of a recurrence, -1 if parent is 'no zoom', 0 if attach mode, 1 if detach (safe) mode
    const parentsZoomSettingsIfChild = <?php echo $parent_recurrence_zoom_settings == null || !isset( $parent_recurrence_zoom_settings->zoomset_detach_recurrences) ? 'null' : $parent_recurrence_zoom_settings->zoomset_detach_recurrences ?>;
    const parentsZoomLinkIfChild = <?php echo $parent_recurrence_zoom_settings == null || !isset( $parent_recurrence_zoom_settings->zoomset_detach_recurrences) ? 'null' : "'" . esc_js($parent_recurrence_zoom_settings->ueme_zoom_url ) . "'" ?>;

    function event_zoom_settings_onselect( ddElement ) { 
        url_field = document.getElementById("event_zoom_url");
        
        //clear down notification areas
        setFieldNotification( "event-zoom-settings-help", "" );
        setFieldNotification( "event-zoom-url-help", "" );
        
        //this event-zoom-settings-help text is displayed only when necessary - and may be overridden further down
        //this function if a more pressing warning message comes up
        <?php echo $some_settings_were_denied ? 'setFieldNotification( "event-zoom-settings-help",  "Why are some settings missing?...", "Some of your Zoom Settings are designed to force events that are part of a recurrence to use the Zoom Link on the Recurring Event record rather than using individual links for each event. ' . ( $is_singular ? 'This event is not part of a recurrence so these settings are unavailable' : 'If you want to use these settings please edit the Zoom Settings on the Recurring Event record first' ) . '." );' : '' ?>
        
        //IF ITS A LONE EVENT WITH NO PARENT RECURRENCE
        //set zoom link to readonly ONLY if user selects 'no zoom' (-1);
        if(isSingular) {
            url_field.readOnly = ( ddElement.value == "-1" );
            if( !url_field.readOnly ) {
                setFieldNotification( "event-zoom-url-help", "<b>Link Protected</b>...", "This event is not part of a recurrance so this value cannot be inadventantly overridden");
            }
        }
        else if( isRecurrenceChild && parentsZoomSettingsIfChild == null) 
        {
            //if it has a parent recurrence record and parent set to 'no zoom' then no restrictions and zoom url safe
            //treat it same as a singular record like above
            url_field.readOnly = ( ddElement.value == "-1" );
            if( !url_field.readOnly ) {
                setFieldWarning( "event-zoom-settings-help", "<b>Different from Recurring Event</b>...", "The parent Recurring Event record is set to 'No Zoom' so any updates to it may revert this event's setting back (your link below would be preserved, but this event wouldn't use it as it would be set to a non-zoom event). It might be wise to detach this event from the recurrence.");
                setFieldNotification( "event-zoom-url-help", "<b>Link Protected</b>...", "The Parent Recurring Event record is set to 'No Zoom' so updating the Recurring EVent will NOT override this link.");
            }
        }
        else if( isRecurrenceChild && parentsZoomSettingsIfChild == 1) 
        {
            //if it has a parent recurrence record and parent set to 'Detach' then url is safe
            //only restriction is 'attach' settings have already been stripped from drop down
            //so you can only set to no zoom or a detched setting.
            url_field.readOnly = ( ddElement.value == "-1" );
            if( !url_field.readOnly ) {
                setFieldNotification( "event-zoom-url-help", "<b>Link Protected</b>...", "The parent Recurring Event record is using 'Individual Event Links' Zoom Settings so updating it will NOT override link.");
            }
            if( zoomSettingArray[ ddElement.value.toString() ][0] != parentsZoomSettingsIfChild )
            {
                setFieldWarning( "event-zoom-settings-help", "<b>Different from Recurring Event</b>...", "The parent Recurring Event record is using an 'Individual Event Links' Zoom Setting so any updates to it may revert this event's Zoom Setting back to that. It might be wise to detach this event from the recurrence.");
            }
        }
        else if( isRecurrenceChild && parentsZoomSettingsIfChild == 0) 
        {
            //if it has a parent recurrence record and parent set to 'Attach' then...
            //link field should be read-only unless user manually changes the zoom settings on this child to a
            //different 'detached' type setting
            if( ddElement.value == "-1" || zoomSettingArray[ ddElement.value.toString() ][0] == 1)
            {
                //you have changed drop down!
                setFieldWarning( "event-zoom-settings-help", "<b>Different from Recurring Event</b>...", "The parent Recurring Event record is using a 'Use Zoom Link from Recurring Event' Zoom Setting so any updates to it may revert this event's Zoom Setting back to that. It might be wise to detach this event from the recurrence.");
                url_field.readOnly = ddElement.value == "-1";
                if( !url_field.readOnly ) {
                    setFieldWarning( "event-zoom-url-help", "<b>Link could be lost</b>...", "The Parent Recurring Event record is using a 'Use Zoom Link from Recurring Event' Zoom Setting so any updates to it may revert this Zoom Link back to that and your link would be lost. If you want to use these settings then MAKE A NOTE OF THIS LINK BEFORE UPDATING THE RECURRING EVENT IN CASE THE LINK REVERTS. Alternatively it might be wise to detach this event from the recurrence.");
                }
            }
            else
            {
                //you havent changed drop down so make the zoom link field read-only - no warning's necessary
                url_field.readOnly = true;
                if( url_field.value == parentsZoomLinkIfChild )
                {   //assuming they havent changed the readonly field - which isnt easy
                    setFieldNotification( "event-zoom-url-help", "<b>Link Read-Only</b>...", "This Zoom Link WILL be used for this event. The only reason it is greyed-out is becuase it is read only; the Parent Recurring Event record is using a 'Use Zoom Link from Recurring Event' Zoom Setting." );
                }
                else
                {
                    //they are still on attach mode but they have sneakily edited the Zoom Link field which is usually readonly
                    setFieldWarning( "event-zoom-url-help", "<b>Link could be lost</b>...", "The Parent Recurring Event record is also using a 'Use Zoom Link from Recurring Event' Zoom Setting but it's Zoom Link is set to '" + parentsZoomLinkIfChild + "' which is different to this one. Any updates to the parent Recurring Event may revert this Zoom Link back to '" + parentsZoomLinkIfChild + "' and your link would be lost. If you want to use these settings then MAKE A NOTE OF THIS LINK BEFORE UPDATING THE RECURRING EVENT IN CASE THE LINK REVERTS. Alternatively it might be wise to detach this event from the recurrence.");
                }
            }
        }
        else if(isRecurrenceParent)
        {
            //Here we are editing on the Recurring Event form not an individual event form
            if( ddElement.value == "-1" )
            {
                //no zoom
                url_field.readOnly = true;
            }
            else if(zoomSettingArray[ ddElement.value.toString() ][0] == 1)
            {
                //individual event links
                url_field.readOnly = true;
                setFieldWarning( "event-zoom-url-help", "<b>Warning</b>: Please be ready to configure the Zoom Links on all child events individually before a booking is made on one of them.");
            }
            else{
                url_field.readOnly = false;
                setFieldWarning( "event-zoom-url-help", "<b>Warning</b>: This setting is deigned for when all of the child recurrence events use the same Zoom Link.");
            }
        }
        else
        {
            //Just being a drama queen - but better safe than sorry
            alert( 'This should never happen - plese view HTML source of this page and send a copy of it to developer for support.' );
        }
        
        //after all that hassle, overrite any of the above warningz with this more important one 
        //if the user has left the link field blank when they shouldn't have
        event_zoom_link_onfocus(url_field);
    }

    //this shows a big red warning if the zoom link field is NOT rwad-only but the user has not written anything in it yet
    //returns true if no issues, false if user still required to enter a value
    function event_zoom_link_onfocus( url_field ) { 
        if( url_field.readOnly != null && url_field.readOnly == false && url_field.value.trim() == "" ) {
            setFieldWarning( "event-zoom-url-help", "<b>IMPORTANT</b> Please set the Zoom Link before a booking is made" );
            document.getElementById("event-zoom-url-help").style.color = 'darkred';
            return false;
        }
        return true;
    }
    
    function event_zoom_link_onblur( url_field ) {
        if(event_zoom_link_onfocus( url_field )) {
            //dont run if the user hasnt entered a value as we dont want the big red warning to be overwridden
            event_zoom_settings_onselect( document.getElementById("event-zoom-settings") );
        }
    }

    function setFieldNotification( labelElementName, message, titleToolTip )
    { 
        labelElement = document.getElementById(labelElementName);
        labelElement.innerHTML = '<p style="margin-block-start: 0em">' + message + '</p>';
        labelElement.style.color = 'darkgreen';
        labelElement.title = titleToolTip == null ? '' : titleToolTip;
    }
        
    function setFieldWarning( labelElementName, message, titleToolTip )
    { 
        labelElement = document.getElementById(labelElementName);
        labelElement.innerHTML = '<p style="margin-block-start: 0em">' + message + '</p>';
        labelElement.style.color = 'darkorange';
        labelElement.title = titleToolTip == null ? '' : titleToolTip;
    }
    
    function setFieldError( labelElementName, message, titleToolTip )
    { 
        labelElement = document.getElementById(labelElementName);
        labelElement.innerHTML = '<p style="margin-block-start: 0em">' + message + '</p>';
        labelElement.style.color = 'darkred';
        labelElement.title = titleToolTip == null ? '' : titleToolTip;
    }
    //]]>
</script>
    <label for="event-zoom-settings">Zoom Event Settings</label>
    <select style="width: 100%" onchange="event_zoom_settings_onselect( this );" name="event_zoom_settings" id="event-zoom-settings" class="cs-replacement-field">
        <option value="-1">Not a zoom event</option>
<?php echo $drop_down_options; ?>
    </select>
    <label title="CONTEXT SENSITIVE HELP TEXT INSERTED HERE BY JAVASCRIPT" id="event-zoom-settings-help" for="event-zoom-settings" style="width: 100%; font-weight: normal; font-size: 10px; color: darkgreen"></label>
    <label for="event_zoom_url">Event's Zoom URL:</label>
    <input style="width: 100%" type="text" name="event_zoom_url" id="event_zoom_url" onfocus="event_zoom_link_onfocus( this )" onblur="event_zoom_link_onblur( this )" value="<?php echo esc_attr( $EM_Event->ueme_zoom_url ) ?>" class="cs-replacement-field" />
    <label title="CONTEXT SENSITIVE HELP TEXT INSERTED HERE BY JAVASCRIPT" id="event-zoom-url-help" for="event_zoom_url" style="width: 100%; font-weight: normal; font-size: 10px; color: darkgreen"></label>
<script type="text/javascript">
    //<![CDATA[
    event_zoom_settings_onselect( document.getElementById("event-zoom-settings") );
    
    <?php echo $the_events_actual_zoom_setting_id_was_denied ? 'setFieldError( "event-zoom-settings-help",  "<b><u>YOU MUST RESELECT BEFORE SAVING...</b></u>", "You have recently detahced this event from a Recurrence. Previously you were using a using a \'Use Zoom Link from Recurring Event\' Zoom Setting. That setting is no longer available. IF YOU DO NOT CHOOSE A NEW SETTING IT WILL REVERT TO \'NOT A ZOOM EVENT\' WHEN YOU SAVE THIS RECORD!" ); alert("IMPORTANT: Your Zoom Setting needs to be updated before saving or your event will loose Zoom Functionality")' : '' ?>
    //]]>
</script>

<?php
}

//Hook to add same drop down to front end 'submit new event' form that non-admin
//users can use
//NOT TESTED AS DONT USE CURRENTLY - March 2020
function upnrunning_em_zoom_settings_frontend_form_input(){
    ?>
<!-- Add html here to make it look pretty -->
        <?php upnrunning_em_zoom_settings_metabox(); ?>
<!-- Add html here to make it look pretty -->
    <?php
}
if( UEMA_USE_UEMA_ZOOM_URLS )
{
    add_action('em_front_event_form_footer', 'upnrunning_em_zoom_settings_frontend_form_input');
}



/*AFTER PARENT PLUGIN HAS SAVED EVENT RECORD OR RECURRING EVENT PARENT RECORD (But not it's children) THIS WILL BE CALLED
 *We will use it to save the additional 2 fields to the event record (see install.php) 
 *for zoom as they have not yet been saved by tarent plugin
 * NOTE: Uses EM_EVENTS_TABLE which is defined in the main Events Manager plugin code.
 */
if( UEMA_USE_UEMA_ZOOM_URLS )
{
    add_filter('em_event_save','upnrunning_em_zoom_settings_event_save',1,2);
}
function upnrunning_em_zoom_settings_event_save($result,$EM_Event){
    global $wpdb;
    
    if($result!=true)
    {
        $debug = 'debug';
    }
    
    //Update the ueme values on the $EM_Event object before we save so object stays correct as well as db data
    //only update if the event has been saved in db correctly by
    //main Events Manager plugin (ie id is set) and the form fields were there on form submit
    //sometimes $result=false here which means there was an issue saving - but this generally
    //just means that the record was still saved, only it was it saved as a draft and user will 
    //be prompted to resolve ussues and republish on next screen
    //so either way we still need to save the zoom fields
    if( isset( $EM_Event->event_id ) && ( isset($_POST['event_zoom_settings']) || isset($_POST['event_zoom_url']) ) ){

        //Update the ueme values on the object before we save so object stays correct as well as db date
        $EM_Event->ueme__fields_loaded = true;
        $EM_Event->ueme_zoom_settings_id = (int)$_POST['event_zoom_settings'];
        $EM_Event->ueme_zoom_url = $_POST['event_zoom_url'];
    
        //update additional two fields for this plugin
        $result = $wpdb->query( $wpdb->prepare( 
            "UPDATE ".EM_EVENTS_TABLE." SET ueme_zoom_settings_id = %d, ueme_zoom_url = %s WHERE event_id = %d",
            $EM_Event->ueme_zoom_settings_id, $EM_Event->ueme_zoom_url, $EM_Event->event_id ) 
        )  != false;

    }
    
    return $result;
}

//this runs when a recurring Event parent record is saved (it has just updated all children 
//(or even worse deleted and recreated all children if recurrence times and dates have changed
//TO DO: we need to copy zoom-url down IF AND ONLY IF the zoom settings dictate that
//   (see my comments on function upnrunning_em_zoom_settings_metabox for more info)
//NOTE: Uses EM_EVENTS_TABLE which is defined in the main Events Manager plugin code.
if( UEMA_USE_UEMA_ZOOM_URLS )
{
    add_filter('em_event_save_events', 'ueme_zoomsettings_event_save_events', 5, 3);
}
function ueme_zoomsettings_event_save_events($result, $EM_Event, $event_ids){

    if( $result ){ //check that the children were created okay otherwise we dont bother
        global $wpdb;
        
        //ensure all ids are absint (non-negative) here as $event_ids is used in both insert and delete
        array_walk($event_ids, 'absint'); 
         
        //for some reason the ueme_zoom fields on have not been populated here for 
        //the $EM_Event object representing the parent recurrence event
        //i dont know why as the function that sets them is hooked into the end of the $EM_Event creator function.
        //this seems to be a 'quirk' of the main Event Manager plugin.
        //not to worry we can call the hook function manually and it will set them (if not already set) with minimal overhead
        upnrunning_em_ensure_zoom_settings_loaded_onto_event( $EM_Event );
       
        //now we know the zoom setting id on this parent recurrrence event record we can fetch the
        //detils of those settings from the db
        $sql = $wpdb->prepare( "SELECT zoomset_id, zoomset_name, zoomset_detach_recurrences " .
                               "FROM " . UEME_ZOOM_SETTINGS_TABLE . " " .
                               "WHERE zoomset_id = %d", 
                               $EM_Event->ueme_zoom_settings_id );
        $parent_recurrence_zoom_settings = $wpdb->get_row($sql, OBJECT);
   
        if( isset($parent_recurrence_zoom_settings) && (int)$parent_recurrence_zoom_settings->zoomset_detach_recurrences == 0)
        {
            //parent recurrence record saved with 'attached' mode so we copy BOTH zoom settings drop down and the Zoom URL fileds down to children
            $sql = $wpdb->prepare( 
            "UPDATE ".EM_EVENTS_TABLE." SET ueme_zoom_settings_id = %d, ueme_zoom_url = %s WHERE event_id IN (" . implode(',', $event_ids). ")" ,
            $EM_Event->ueme_zoom_settings_id, $EM_Event->ueme_zoom_url );
            $result = $wpdb->query( $sql ) !== false;
        }
        else {
            //parent recurrence record saved as 'not a zoom event' or 'detached' so sync down zoom setting drop down but NOT zoom URL
            //by ignoring the zoom url column, child values will be unchanged if this is an edit or they will be set to '' (DB Default Value) on recurrence creation (which is exactly what we want to happen)
            $sql = $wpdb->prepare( 
            "UPDATE ".EM_EVENTS_TABLE." SET ueme_zoom_settings_id = %d WHERE event_id IN (" . implode(',', $event_ids). ")" ,
            $EM_Event->ueme_zoom_settings_id );
            $result = $wpdb->query( $sql ) !== false;
        }
     }
  
    return $result;
}


//After an EM_Event object has been created (other from SELECT or from passingin values) this is run
//get the zoom settings and zoom-url add them on - only if it's actually made it to the database so far
// $EM_Event->ueme__fields_loaded keeps track of whether this function has already run on the event object and whether the values have been already populated
//this function is also used outside of a hook as these values arent always available on the instantiated object even thouugh they are hooked into by the contructor
//the exact reason for this hurts my brain - maybe its due to object duplication or incorrect hook priorities but either way i probably
//cant fix this without editing the parent plugin code so we just call this periodically sometimes on an already instantiated object
//whenever we want to use its zoom fields to ensure they are there and havent 'fallen off'
//NOTE: Uses EM_EVENTS_TABLE which is defined in the main Events Manager plugin code.
function upnrunning_em_ensure_zoom_settings_loaded_onto_event($EM_Event){

    if( UEMA_USE_UEMA_ZOOM_URLS && ( !isset($EM_Event->ueme__fields_loaded) || $EM_Event->ueme__fields_loaded != true ) ) {
        
        if( isset($EM_Event->event_id) && $EM_Event->event_id != null )  { //might be a newly instantiated blank object

            global $wpdb;
            $sql = $wpdb->prepare("SELECT e.ueme_zoom_settings_id, e.ueme_zoom_url, z.zoomset_link_active_mins FROM ".EM_EVENTS_TABLE." e LEFT OUTER JOIN ".UEME_ZOOM_SETTINGS_TABLE." z ON ( e.ueme_zoom_settings_id = z.zoomset_id ) WHERE event_id=%d", 
                                    $EM_Event->event_id);
            $ueme_event_zoom_fields = $wpdb->get_row($sql, OBJECT);

            if( isset($ueme_event_zoom_fields) ) {
                $EM_Event->ueme__fields_loaded = true;
                $EM_Event->ueme_zoom_settings_id = (int)$ueme_event_zoom_fields->ueme_zoom_settings_id;
                $EM_Event->ueme_zoom_url = $ueme_event_zoom_fields->ueme_zoom_url;
                $EM_Event->ueme_zoom_url_active_mins = $ueme_event_zoom_fields->zoomset_link_active_mins == null ? null : (int)$ueme_event_zoom_fields->zoomset_link_active_mins;
            }
            else {
                //this means the $EM_Event_event_id was set but it doesnt exist in the database yet - in which case maybe they are about to save it
                //just set $EM_Event->ueme__fields_loaded = true and set fields to default values
                $EM_Event->ueme__fields_loaded = true;
                $EM_Event->ueme_zoom_settings_id = -1;
                $EM_Event->ueme_zoom_url = '';
                $EM_Event->ueme_zoom_url_active_mins = null;
            }
        }
    }
    
    if( defined( 'UEMA_LINK_ACTIVATION_MINUTES_OVERRIDE' ) )
    {
        $EM_Event->ueme_zoom_url_active_mins = UEMA_LINK_ACTIVATION_MINUTES_OVERRIDE;
    }
}
add_action('em_event','upnrunning_em_ensure_zoom_settings_loaded_onto_event',15,1);


/*
 * Placeholder String Replace Definition for #_ZOOMLINK and #_BOOKINGSTATUS
 */
add_filter('em_event_output_placeholder','upnrunning_em_zoom_settings_placeholders',1,3);
function upnrunning_em_zoom_settings_placeholders($replace, $EM_Event, $result){
    if( $result == '#_ZOOMLINK' ){
        upnrunning_em_ensure_zoom_settings_loaded_onto_event( $EM_Event );
        $replace = 'Contact us for your Zoom Link or you will not be able to join.';
        if( isset( $EM_Event->event_location_type ) && strtolower( $EM_Event->event_location_type ) === 'url' )
        {
            $replace = $EM_Event->event_location_data['url'];
        }
        
        if( isset( $EM_Event->event_location_type ) && strpos( strtolower( $EM_Event->event_location_type ) , 'zoom' ) !== false )
        {
            $replace = $EM_Event->event_location_data['join_url'];
        }
    }
    elseif( $result == '#_BOOKINGSTATUS' && is_user_logged_in() && isset($EM_Event->event_id) ) {
        $replace = 'Not-Booked';
        $freindly_status = upnrunning_em_get_event_person_booking_status_friendly_description($EM_Event->event_id, get_current_user_id());
        if( $freindly_status != null )
        {
            $replace = $freindly_status;
        }
    }
    elseif( $result == '#_BOOKINGCLOSURETIME' && isset($EM_Event->event_id) ) {
        $replace = $EM_Event->rsvp_end()->format('H:i, jS M');
    }
    elseif( $result == '#_ZOOMTIMINGSTATUS' && isset($EM_Event->event_id) ) {
        upnrunning_em_ensure_zoom_settings_loaded_onto_event($EM_Event);
        $replace = ueme_get_event_zoom_link_timing_status($EM_Event);
    }
    elseif( $result == '#_JSCURRENTTIMESTAMP' && isset($EM_Event->event_id) && $EM_Event->rsvp_end() != null ) {
        $replace = 'new Date(' . time() * 1000 . ')';
    }
    elseif( $result == '#_JSBOOKINGSCLOSURETIMESTAMP' && isset($EM_Event->event_id) && $EM_Event->rsvp_end() != null ) {
        $replace = 'new Date(' . $EM_Event->rsvp_end()->getTimestamp() * 1000 . ')';
    }
    elseif( $result == '#_JSZOOMLINKACTIVATIONTIMESTAMP' && isset($EM_Event->event_id) ) {
        upnrunning_em_ensure_zoom_settings_loaded_onto_event( $EM_Event );
        $replace = 'new Date(' . ( $EM_Event->start()->getTimestamp() - $EM_Event->ueme_zoom_url_active_mins * 60 ) * 1000 . ')';
    }
    elseif( $result == '#_JSEVENTSTARTTIMESTAMP' && isset($EM_Event->event_id) ) {
        $replace = 'new Date(' . $EM_Event->start()->getTimestamp() * 1000 . ')';
    }
    
    return $replace;
}

/*
 * Conditional Placeholders Definitions for working out if the user has permissions to see the zoom url
 */
add_action('em_event_output_show_condition', 'upnrunning_em_zoom_event_output_show_condition', 1, 4);
function upnrunning_em_zoom_event_output_show_condition($show, $condition, $full_match, $EM_Event){
    
    //user is logged out
    if( strtolower( $condition ) === 'is_logged_out' && !is_user_logged_in() ){
        $show = true;
    }
    elseif( strtolower( $condition ) === 'is_logged_in' && is_user_logged_in() ){
        $show = true;
    }
    elseif( strtolower( $condition ) === 'event_bookings_not_enabled' && isset( $EM_Event->event_id) 
            && !( $EM_Event->event_rsvp && get_option('dbem_rsvp_enabled') ) ) {
        $show = true;
    }
    elseif( strtolower( $condition ) === 'user_has_any_booking' && is_user_logged_in() && isset( $EM_Event->event_id) ){
        if(upnrunning_em_get_event_person_booking_status_friendly_description( $EM_Event->event_id, get_current_user_id() ) != null ) {
            $show = true;
        }
    }
    elseif( strtolower( $condition ) === 'user_is_rejected' && is_user_logged_in() && isset( $EM_Event->event_id) ) {
        $friendly_booking_status = upnrunning_em_get_event_person_booking_status_friendly_description( $EM_Event->event_id, get_current_user_id() );
        if( in_array( strtoupper( $friendly_booking_status ), ["REJECTED"] ) )  {
            $show = $EM_Event->get_bookings()->is_open();
        }
    }
    elseif( strtolower( $condition ) === 'user_has_no_bookings_and_bookings_open' && is_user_logged_in() && isset( $EM_Event->event_id) ) {
        $friendly_booking_status = upnrunning_em_get_event_person_booking_status_friendly_description( $EM_Event->event_id, get_current_user_id() );
        if( $friendly_booking_status === null || in_array( strtoupper( $friendly_booking_status ), ["CANCELLED"] ) )  {
            $show = $EM_Event->get_bookings()->is_open();
        }
    }
    elseif( strtolower( $condition ) === 'user_has_no_bookings_and_bookings_closed' && is_user_logged_in() && isset( $EM_Event->event_id) ) {
        $friendly_booking_status = upnrunning_em_get_event_person_booking_status_friendly_description( $EM_Event->event_id, get_current_user_id() );
        if( $friendly_booking_status === null || in_array( strtoupper( $friendly_booking_status ), ["CANCELLED"] ) )  {
            $show = !$EM_Event->get_bookings()->is_open();
        }
    }
    elseif( strtolower( $condition ) === 'user_booking_status_pending_and_bookings_open' && is_user_logged_in() && isset( $EM_Event->event_id) ){
        if(strtoupper( upnrunning_em_get_event_person_booking_status_friendly_description( $EM_Event->event_id, get_current_user_id() ) ) === "PENDING" ) {
            $show = $EM_Event->get_bookings()->is_open();
        }
    }
    elseif( strtolower( $condition ) === 'user_booking_status_pending_and_bookings_closed' && is_user_logged_in() && isset( $EM_Event->event_id) ){
        if(strtoupper( upnrunning_em_get_event_person_booking_status_friendly_description( $EM_Event->event_id, get_current_user_id() ) ) === "PENDING" ) {
            $show = !$EM_Event->get_bookings()->is_open();
        }
    }
    elseif( strtolower( $condition ) === 'user_booking_status_confirmed' && is_user_logged_in() && isset( $EM_Event->event_id) ){
        if( strtoupper( upnrunning_em_get_event_person_booking_status_friendly_description( $EM_Event->event_id, get_current_user_id() ) ) == 'CONFIRMED' ) {
            $show = true;
        }
    }
    elseif ($condition == 'event_has_bookings_but_user_does_not_and_is_logged_in') {
        if($EM_Event->event_rsvp && get_option('dbem_rsvp_enabled')){ //This is used for do we show the booking form but deffo without the login part alongside it?
            $friendly_booking_status = upnrunning_em_get_event_person_booking_status_friendly_description( $EM_Event->event_id, get_current_user_id() );
            if( is_user_logged_in() && ( $friendly_booking_status === null || strtoupper( $friendly_booking_status ) === "CANCELLED" ) ) {
                $show = true;
            }
        }
    }
    elseif ($condition == 'event_has_bookings_but_user_does_not_or_is_logged_out') {
        if($EM_Event->event_rsvp && get_option('dbem_rsvp_enabled')){ //THis is used for do we show the booking form?
            $friendly_booking_status = upnrunning_em_get_event_person_booking_status_friendly_description( $EM_Event->event_id, get_current_user_id() );
            if( !is_user_logged_in() || ($friendly_booking_status === null || strtoupper( $friendly_booking_status ) === "CANCELLED" ) ) {
                $show = true;
            }
        }
    }
    elseif( preg_match('/^zoom_timing_status_in_list_(.+)$/',$condition, $matches) && isset( $EM_Event->event_id) ) {
        $valid_waiting_statuses = explode("_", $matches[1]);
        $timing_status = ueme_get_event_zoom_link_timing_status( $EM_Event );
        if( in_array($timing_status, $valid_waiting_statuses) ){
            $show = true;
        }
    }
    elseif( preg_match('/^user_booking_status_confirmed_and_zoom_timing_status_in_list_(.+)$/',$condition, $matches) && is_user_logged_in() && isset( $EM_Event->event_id) ) {
        if(strtoupper( upnrunning_em_get_event_person_booking_status_friendly_description( $EM_Event->event_id, get_current_user_id() ) ) === "CONFIRMED" ) {
            $valid_waiting_statuses = explode("_", $matches[1]);
            $timing_status = ueme_get_event_zoom_link_timing_status( $EM_Event );
            if( in_array($timing_status, $valid_waiting_statuses) ){
                $show = true;
            }
        }
    }
    elseif( strtolower( $condition ) === 'is_zoom_event' && isset( $EM_Event->event_id) ){
        $timing_status = ueme_get_event_zoom_link_timing_status( $EM_Event );
        if( isset( $timing_status ) && $timing_status !== 'nozoom' ) {
            $show = true;
        }
    }
    elseif( preg_match('/^user_has_booking_status_in_(.+)$/',$condition, $matches) && is_user_logged_in() && isset( $EM_Event->event_id) ){
        $status_id_list = explode("_", $matches[1]);
        $booking_status_array = upnrunning_em_get_event_person_booking_statuses( $EM_Event->event_id, get_current_user_id(), $status_id_list );
        if( !empty($booking_status_array) ){
            $show = true;
        }
    }
    return $show;
}

//Generate a friendly User-Facing version of event booking status for all their bookings for that event.
//they may have one cancelled booking and one pending boking and one approved (Confirmed) booking.
//The user friendly status would be 'Confirmed' as they are going to the event regardless of any 
//other failed attempts at booking that they have made!
//
//at time of writing this was the full list of booking statuses taken from em-booking.php
// 0 => __('Pending','events-manager'),
// 1 => __('Approved','events-manager'),
// 2 => __('Rejected','events-manager'),
// 3 => __('Cancelled','events-manager'),
// 4 => __('Awaiting Online Payment','events-manager'),
// 5 => __('Awaiting Payment','events-manager')
//again - should really be calling a function in em_booking(s) here for best Practice
//but this is a TO DO - either for here or for the main Events Manager plugin dev(s)
function upnrunning_em_get_event_person_booking_status_friendly_description( $event_id, $person_id ) {

    $booking_statuses_array = upnrunning_em_get_event_person_booking_statuses( $event_id, $person_id );
    
    $has_confirmed = $has_awaiting_payment = $has_pending = $has_rejected = $has_cancelled = false;
    
    foreach ($booking_statuses_array as $booking_status) {
        $has_confirmed = $has_confirmed || in_array( $booking_status->booking_status, [1] );
        $has_awaiting_payment = $has_awaiting_payment || in_array( $booking_status->booking_status, [4, 5] );
        $has_pending   = $has_pending   || in_array( $booking_status->booking_status, [0] );
        $has_rejected  = $has_rejected  || in_array( $booking_status->booking_status, [2] );
        $has_cancelled = $has_cancelled || in_array( $booking_status->booking_status, [3] );
    }
    
    if( $has_confirmed ) { return "Confirmed"; }
    if( $has_awaiting_payment ) { return "Awaiting_Payment"; }
    if( $has_pending )   { return "Pending"; }
    if( $has_rejected )  { return "Rejected"; }
    if( $has_cancelled ) { return "Cancelled"; }
    
    return null; //they've not made a booking
}

//Find all the bookings for a particular person on a particular event and filter for only the booking status values (integers) that we are interested in
//then return an array of the booking status ids that were found!
//NOTE THE status_id_array filter is optional, no argument means don't filter
//NOTE2: Uses EM_BOOKINGSS_TABLE which is defined in the main Events Manager plugin code.
//TO DO: Should be calling a function in classes/em-booking(s) really for best practice
function upnrunning_em_get_event_person_booking_statuses( $event_id, $person_id, $status_id_array = array()) {
    global $wpdb;

    $status_id_array = array_filter($status_id_array, 'is_numeric'); //force away and text statuses (only ints) to avoud sql compile errors

    $select_query_string = "SELECT DISTINCT b.booking_status, b.booking_id FROM " . EM_BOOKINGS_TABLE . " b
                           WHERE b.event_id = %d AND b.person_id = %d";
    if( !empty($status_id_array) ) {
        $status_id_list = implode(', ', $status_id_array);
        $select_query_string = $select_query_string . " AND b.booking_status IN (" . $status_id_list . ")";
    }
    $sql = $wpdb->prepare($select_query_string, $event_id, $person_id );
    
    $booking_status_array = $wpdb->get_results($sql, OBJECT);
    return $booking_status_array;
}


/* This function looks at the settings for how many minutes should it be before 
 * the event that the zoom url activates and also what time the event starts
 * and finally what time is it now. then it returns one of:
 * nozoom/waiting/active/started/finished
 */
function ueme_get_event_zoom_link_timing_status( $EM_Event ) {
 
    upnrunning_em_ensure_zoom_settings_loaded_onto_event( $EM_Event );
    if( !isset( $EM_Event->event_location_type ) || empty( $EM_Event->event_location_type ) )
    {
        return 'nozoom';
    }
    
    //if there is a location type but it is not 'url' or a 'zoom' type then it's still not a zoom event
    $locationTypeLowercase = strtolower($EM_Event->event_location_type );
    if( !( strpos( $locationTypeLowercase , 'zoom' ) !== false ) && $locationTypeLowercase !== 'url' )
    {
        return 'nozoom';
    }
    
    $time_now = new DateTime();
    if( $time_now > $EM_Event->end() ) {
        return 'finished';
    }
    elseif ( $time_now  > $EM_Event->start() ) {
        return 'started';
    }
    elseif( $EM_Event->ueme_zoom_url_active_mins === null || $EM_Event->ueme_zoom_url_active_mins < 0 ) {
            return 'alwaysactive';
    }
    else {
            $time_till_start = $EM_Event->start()->diff( $time_now );
            $minutes_till_start = $time_till_start->days * 24 * 60;
            $minutes_till_start += $time_till_start->h * 60;
            $minutes_till_start += $time_till_start->i;
            $minutes_till_start *=  $time_till_start->invert === 1 ? 1 : -1 ;
            
            return $minutes_till_start > $EM_Event->ueme_zoom_url_active_mins ? 'waiting' : 'activated';
    }
}





/*
 * The following code edits the booking form (on event details page) adds extra fields, validates
 * those fields and saves the values to the db
 * it also manages setting the user passowrd if you add password fields to it
 * rather than the default random password generation which sucks
 */

function ueme_is_frontend_event_details_page() {
    global $wp_query;
    $obj = $wp_query->get_queried_object();
    //if its events page then enque recaptcha
    return ( !empty($obj->post_type) && $obj->post_type === EM_POST_TYPE_EVENT );
}

/* 
 * Enqueue Scripts for Recaptcha if we're on an events manager page
 */
add_action('em_enqueue_scripts', 'ueme_enque_recaptcha_scripts');
function ueme_enque_recaptcha_scripts() {
    //if its events page then enque recaptcha
    if( ueme_is_frontend_event_details_page() )
    {
        //will run to enqueue scripts if plugin is installed, otherwise will do nothing
        do_action('unr_recaptcha_enqueue_scripts');
    }
}

if( UEMA_CHOOSE_PASSWORD_WHEN_REGISTERING )
{
    add_action( 'em_booking_form_after_user_details', 'unr_template_booking_form_password_fields' );
}
function unr_template_booking_form_password_fields( $EM_Event ) {
    if( !is_user_logged_in() )
    {
?>
	<p>
		<label for='user_pass'><?php _e('Choose Password *','events-manager') ?></label> 
		<input type="password"  autocomplete="off" name="user_pass" id="user_pass" class="input" value="<?php if(!empty($_REQUEST['user_pass'])) echo esc_attr($_REQUEST['user_pass']); ?>"  />
	</p>
	<p>
		<label for='user_pass_confirm'><?php _e('Confirm Password *','events-manager') ?></label> 
		<input type="password"  autocomplete="off" name="user_pass_confirm" id="user_pass_confirm" class="input" value="<?php if(!empty($_REQUEST['user_pass_confirm'])) echo esc_attr($_REQUEST['user_pass_confirm']); ?>"  />
	</p>
<?php
    }
}

/* Called in em-actions.php before any of the below stuff is called
 * we're using this to validate all the fields on the reg form
 * if they exist!
 */
add_filter('em_booking_validate','upnrunning_em_validate_registration_form_before_saving', 2, 2);
function upnrunning_em_validate_registration_form_before_saving($result, $EM_Booking){
    if(!is_user_logged_in()) {
        
        /* Managed by events manager Pro Form Editor
        if (isset( $_REQUEST['user_name'] ) && $_REQUEST['user_name'] == ''){
            $EM_Booking->add_error('Your Name is required');
            $result = false;
        }
        if (isset( $_REQUEST['dbem_phone'] ) && $_REQUEST['dbem_phone'] == ''){
            $EM_Booking->add_error('Your Phone Number is required');
            $result = false;
        }
        if ( isset( $_REQUEST['user_email'] ) ) {
            if( $_REQUEST['user_email'] == '' ) {
                $EM_Booking->add_error('Your Email Address is required');
                $result = false;
            }
            elseif(!filter_var($_REQUEST['user_email'], FILTER_VALIDATE_EMAIL)) {
                $EM_Booking->add_error('Your Email Address is invalid');
                $result = false;
            }
        }
        */
        
        //password validation
        if( UEMA_CHOOSE_PASSWORD_WHEN_REGISTERING )
        {
            if(isset( $_REQUEST['user_pass'] ) && $_REQUEST['user_pass'] == ''){
                $EM_Booking->add_error('Please choose a password');
                $result = false;
            }
            elseif(isset( $_REQUEST['user_pass_confirm'] ) && $_REQUEST['user_pass_confirm'] == ''){
                $EM_Booking->add_error('Please confirm your password');
                $result = false;
            }
            elseif( isset( $_REQUEST['user_pass'] ) && strlen( $_REQUEST['user_pass'] ) < 6 ) {
                $EM_Booking->add_error('Your password must be at least 6 characters long');
                $result = false;
            }
            elseif( isset( $_REQUEST['user_pass_confirm'] ) && $_REQUEST['user_pass_confirm'] !== $_REQUEST['user_pass'] ) {
                $EM_Booking->add_error('Your Password and Confirm Password do not match');
                $result = false;
            }
        }
        
        //check recapthca and validate if logged out, ajax and recaptcha plugin installed, and plugin says use recaptcha
        if ( $result && class_exists('UnrRecaptchaPlugin') && UnrRecaptchaPlugin::site_is_using_recaptcha() ) {
            $errorStringArray = array();
            if( !UnrRecaptchaPlugin::validate_recaptcha_response_use_settings_version( "sign-up-and-book", true, $errorStringArray ) ) {
                $EM_Booking->add_error( sprintf( __( implode( "<br /><br />", $errorStringArray ) ) ) );
                $result = false;
            }
        }
    }

    return $result;
}




add_filter('em_register_new_user_pre', 'upnrunning_em_add_password_to_user_data_before_create_user', 1, 1);
function upnrunning_em_add_password_to_user_data_before_create_user($user_data)
{
    if( empty($user_data['user_pass']) && isset( $_REQUEST['user_pass'] ) ) {
        $user_data['user_pass'] =  $_REQUEST['user_pass'];
        $user_data['user_chosen_pass_used'] = 'true';
	}
    return $user_data;
}

/*
add_action( 'set_logged_in_cookie', 'my_update_cookie' );
function my_update_cookie( $logged_in_cookie ){
    $_COOKIE[LOGGED_IN_COOKIE] = $logged_in_cookie;
}
 */

add_filter('em_register_new_user', 'upnrunning_em_after_create_user_update_password_nag_and_login', 1, 3);
//this is called once the new user is created in wordpress
function upnrunning_em_after_create_user_update_password_nag_and_login($user_id)
{
    //this global stores the user_data in a temp global var set
    //by master plugin in em_register_new_user in em-functions.php
    global $em_temp_user_data;
    
    //user_id numeric means user was created successfully and
    //the global should always be set but just in case master source code changes
    if( is_numeric($user_id) && isset($em_temp_user_data))
    {
        if( isset( $em_temp_user_data['user_chosen_pass_used'] ) && $em_temp_user_data['user_chosen_pass_used'] == true )
        {
            //switch off nag for update password if the user chose their own password
            $delme = update_user_option( $user_id, 'default_password_nag', false, true ); //Set up the Password change nag to not 
        }

        //now log the user in as the user has been created successfully
        //i think i can return an error object here is something goes wrong?!
        $signon_data = array('user_login' => $em_temp_user_data['user_email'], 'user_password' => $em_temp_user_data['user_pass'], 'remember'=> true);
        $logged_in_user = wp_signon( $signon_data );
        
        //force the set of current user or is_user_logged_in doesn't work
        //global $current_user; 
        //$current_user = wp_set_current_user($logged_in_user->ID);
        //force the auth cookie.... but it seems to do nothing
        //wp_set_auth_cookie( $logged_in_user->ID, true, false );
        do_action( 'wp_login', $logged_in_user->user_login, $logged_in_user );        
        
        if( is_wp_error( $logged_in_user ) )
        {
            //return error object with failure to login message
            return $logged_in_user;
        }
    }
    return $user_id;
}