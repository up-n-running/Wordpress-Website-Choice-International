//calendarize js acquired under GPL
function calendarize(target, offset) {
	var i=0, j=0, week, out=[], date = new Date(target || new Date);
	var year = date.getFullYear(), month = date.getMonth();

	// day index (of week) for 1st of month
	var first = new Date(year, month, 1 - (offset | 0)).getDay();

	// how many days there are in this month
	var days = new Date(year, month+1, 0).getDate();

	while (i < days) {
		for (j=0, week=Array(7); j < 7;) {
			while (j < first) week[j++] = 0;
			week[j++] = ++i > days ? 0 : i;
			first = 0;
		}
		out.push(week);
	}

	return out;
}

//sublet js acquired under GPL
function debounce(n)
{
    var e;
    return function() {
        var t=this,
        u=arguments;
        e&&clearTimeout(e),e=setTimeout(function(){n.apply(t,u),e=null})}
}
function sublet(n,e){
    var t=debounce(e),
    u=new Proxy(n,{get:function(n,e){return n[e]},set:function(n,e,u){return n[e]!==u&&(n[e]=u,t(n)),!0}});
    return e(u),u
};


//Calendar on Events Page
// for help: https://codepen.io/johniswellace/pen/BaozqJO

// -- setup
const $ = document.querySelector.bind(document);
const h = tag => document.createElement(tag);

const text_labels = {
  en: ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT']
};

const month_names = {
  en: [ "January", "February", "March", "April", "May", "June", 
           "July", "August", "September", "October", "November", "December" ]
};

// -- setup

const monthAndYear = $('#calendar .month-and-year');
const labels = $('#calendar .labels');
const dates = $('#calendar .dates');

const lspan = Array.from({ length: 7 }, () => {
  return labels.appendChild(h('span'));
});

const dspan = Array.from({ length: 42 }, () => {
  return dates.appendChild(h('span'));
});

// -- state mgmt
function update(state) {
  const offset = state.offset;
  
  //apply monthAndYear Label
  monthAndYear.textContent = month_names[state.lang][state.month-1];
  monthAndYear.textContent += ' ' + state.year
  
  // apply day labels
  const txts = text_labels[state.lang];
  lspan.forEach((el, idx) => {
    el.textContent = txts[(idx + offset) % 7];
  });
  
  // apply date labels (very naiive way, pt 1)
  let i=0, j=0, date = new Date(state.year, state.month-1);
  calendarize(date, offset).forEach(week => {
    for (j=0; j < 7; j++) {
      dspan[i].textContent = week[j] > 0 ? week[j] : '';
      if( week[j] == state.eventday ) {
        dspan[i].classList.add('hilight'); //date box
        dspan[i].innerHTML += '<p>' + state.eventtext + '</p>';
        lspan[j].classList.add('hilight'); //day of week label
      }
      else if( dspan[i].textContent == '' ) {
        dspan[i].classList.add('empty');
      }
      i++;
    }
  });
  
  // clear remaining (very naiive way, pt 2)
  while (i < dspan.length) {
   dspan[i].parentNode.removeChild(dspan[i]);
   //dspan[i].classList.add('empty')
   i++;
  }
}