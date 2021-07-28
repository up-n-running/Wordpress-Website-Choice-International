const unrctIntervalDescriptors = [ 'week', 'day', 'hour', 'minute', 'second' ];
const unrctIntervalDescriptorsPlural = [ 'weeks', 'days', 'hours', 'minutes', 'seconds' ];
const unrctIntervalDelimiter = ', ';
const unrctIntervalMillis = [ 1000 * 60 * 60 * 24 * 7 , 1000 * 60 * 60 * 24, 1000 * 60 * 60, 1000 * 60, 1000 ];
var unrctTimerInterval = null;

function getTimeRemainingIntervals(startTime, endTime) {
    //var millis = Date.parse(endtime) - Date.parse(startTime);
    var millisLeft =  Math.max( endTime - startTime, 0 );
    var intervals = new Array( unrctIntervalMillis.length );
    for (i = 0; i < intervals.length; i++) {
        intervals[i] = Math.floor( millisLeft / unrctIntervalMillis[i] );
        millisLeft -= intervals[i] * unrctIntervalMillis[i];
    }
    return intervals;
}

function initializeCountdown( elementId, startTime, endTime, sigFigsToWrite) {
	var timeInitialised = new Date();
    var countdownElement = document.getElementById(elementId);
    
	updateClock(timeInitialised, countdownElement, startTime, endTime, sigFigsToWrite);
	unrctTimerInterval = setInterval( function() { updateClock(timeInitialised, countdownElement, startTime, endTime, sigFigsToWrite); }, 1000);
}

function updateClock( timeInitialised, countdownElement, startTime, endTime, sigFigsToWrite) {
	//alert( startTime + ' |||| ' + new Date() + "||||" + timeInitialised + "||||" + endTime );
	var startTimePlusTimePageBeenLoaded = new Date( Date.parse(startTime) + Date.parse(new Date()) - Date.parse(timeInitialised) )
    var intervals = getTimeRemainingIntervals(startTimePlusTimePageBeenLoaded, endTime);

    var sigFigsFound = 0;
    var firstSigFigIntervalIndex = null;
    var intervalDescription = "";
    var intervalDelimiter = "";
    for (i = 0; i < intervals.length; i++) {
        if( sigFigsFound >= sigFigsToWrite ) {
            break;
        }
        if( intervals[i] > 0 ) {
            intervalDescription += intervalDelimiter + intervals[i] + ' ' + ( intervals[i]===1 ? unrctIntervalDescriptors[i] : unrctIntervalDescriptorsPlural[i] );
            intervalDelimiter = unrctIntervalDelimiter;
            firstSigFigIntervalIndex = firstSigFigIntervalIndex === null ? i : firstSigFigIntervalIndex;
            sigFigsFound++;
        }
    }  

    //maybe it was all zeros and we're past the date
    if( firstSigFigIntervalIndex === null )
    {
        intervalDescription = '0 ' + unrctIntervalDescriptorsPlural[ unrctIntervalDescriptorsPlural.length - 1 ];
        countdownElement.innerText = intervalDescription;
        clearInterval(unrctTimerInterval);
        //reload page if it's been open for more than 3 seconds
        //this avoids us getting into an infinite refresh situation
        if( ( Date.parse(new Date()) - Date.parse(timeInitialised) ) > 3000 ) {
            location.reload();
        }
    }

    countdownElement.innerText = intervalDescription;
}
