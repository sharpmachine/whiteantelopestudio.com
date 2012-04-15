/**********
Global Variables
**********/

var current_page = 'home';
var atom_interval = 0;
var atom_pos = [0, 0];
var load_interval = 0;
var load_pos = 0;
var hash = '';

/**********
DOM Events
**********/

window.addEvent('domready', function() {
	$('l_portfolio').addEvent('click', function (e) {
		e.stop();
		openPage('portfolio');
	});
	$('l_skillset').addEvent('click', function (e) {
		e.stop();
		openPage('skillset');
	});
	$('l_blog').addEvent('click', function (e) {
	});
	$('l_about').addEvent('click', function (e) {
		e.stop();
		openPage('about');
	});
	$('l_contact').addEvent('click', function (e) {
		e.stop();
		openPage('contact');
	});
	$('logo_box').addEvent('click', function (e) {
		openPage('home');
	});
	$('spin_map').addEvent('click', function (e) {
		watchThis(true);
	});
	$('floating_video').addEvent('click', function (e) {
		watchThis(false);
	});
});

/**********
Global Methods
**********/

function meval(e) {
	return eval(e);
}

setInterval(function () {
	if (window.location.hash != hash) {
		if (window.location.hash.length > 1) {
			openPage(window.location.hash.substr(1));
		}
		hash = window.location.hash;
	}
}, 500);

function aniAttr(func, ovalue, dvalue, dur, done, frames) {
	if (!$defined(done)) var done = function () {};
	if (!$defined(frames)) var frames = (dur/1000) * 60;
	if (frames < 1) frames = Math.abs(Math.round(dvalue) - Math.round(ovalue))
	var fps = frames / (dur / 1000);
	var step = (dvalue - ovalue) / frames;
	var i = 0;
	var t = function () {
		if (i < frames) {
			if (i == frames-1) {
				ovalue = dvalue;
			} else {
				ovalue += (dvalue - ovalue) / (frames - i);
			}
			func(ovalue);
			i++;
		} else {
			$clear(t);
			done();
		}
	}.periodical(1000/fps);
}

function openPage(page) {
	watchThis(false);
	if (current_page == 'home' && page != 'home') {
		loadAni(true, $('load_sprite'));
		loadContents(page);
		$('floating_body').setStyle('display', 'block');
		aniAttr(function(x){$('nav').setStyle('top', x+'px');}, 0, 140, 300);
		aniAttr(function(x){$('nav').setStyle('left', x+'px');}, 0, -160, 300);
		aniAttr(function(x){$('nav').setStyle('font-size', x+'px');}, 30, 20, 300, null, 0);
		aniAttr(function(x){$('logo_box').setStyle('top', x+'px');}, 0, 400, 300);
		aniAttr(function(x){$('logo_box').setStyle('left', x+'px');}, 0, -270, 300);
		aniAttr(function(x){$('logo_box').setStyle('width', x+'px');}, 398, 200, 300);
		aniAttr(function(x){$('logo_box').setStyle('height', x+'px');}, 398, 200, 300);
		aniAttr(function(x){$('body').setStyle('height', x+'px');}, 0, 724, 300);
		aniAttr(function(x){$('floating_body').setStyle('opacity', x);}, 0, 1, 400, null, 10);
		$('logo_box').setStyle('cursor', 'pointer');
		current_page = page;
	}else if (current_page != 'home' && page == 'home') {
		loadAni(false, $('load_sprite'));
		aniAttr(function(x){$('nav').setStyle('top', x+'px');}, 140, 0, 300);
		aniAttr(function(x){$('nav').setStyle('left', x+'px');}, -160, 0, 300);
		aniAttr(function(x){$('nav').setStyle('font-size', x+'px');}, 20, 30, 300, null, 0);
		aniAttr(function(x){$('logo_box').setStyle('top', x+'px');}, 400, 0, 300);
		aniAttr(function(x){$('logo_box').setStyle('left', x+'px');}, -270, 0, 300);
		aniAttr(function(x){$('logo_box').setStyle('width', x+'px');}, 200, 398, 300);
		aniAttr(function(x){$('logo_box').setStyle('height', x+'px');}, 200, 398, 300);
		aniAttr(function(x){$('body').setStyle('height', x+'px');}, 724, 0, 300, function () {
			$('floating_body').setStyle('display', 'none');
			});
		aniAttr(function(x){$('floating_body').setStyle('opacity', x);}, 1, 0, 400, null, 10);
		$('logo_box').setStyle('cursor', 'default');
		current_page = 'home';
		window.location.hash = '';
	} else if (current_page == 'home' && page == 'home') {
	} else {
		loadAni(true, $('load_sprite'));
		aniAttr(function(x){$('body').setStyle('opacity', x);}, 1, 0, 150, function () {
			$('body').scrollTo(0,0);
			loadContents(page, function () {
				aniAttr(function(x){$('body').setStyle('opacity', x);}, 0, 1, 150, null, 10);
			});
		}, 10);
	}
}

function loadContents(page, done) {
	if (!$defined(done)) done = $empty;
	var ajax = new Request({
		url: 'pages/'+page+'.php?ajax',
		method: 'post',
		onComplete: function (response) {
			window.location.hash = page;
			hash = window.location.hash;
			loadAni(false, $('load_sprite'));
			$('body').set('html', response.replace(/[ ]{2}/g, "&nbsp; "));
			$each($('body').getElements('a'), function (el, i) {
				if (el.href.match(/http\:\/\//i)) {
					el.target = 'blank';
				}
			});
			$('title_spot').set('text', page.charAt(0).toUpperCase() + page.substr(1));
			if ($('init')) meval($('init').get('onload'));
			done();
		}
	}).send();
}

function watchThis(start) {
	$('floating_video').setStyle('display', start ? 'block' : 'none');
	$('logo_box').setStyle('display', start ? 'none' : 'block');
	if (start) {
		atom_interval = function () {
			if (atom_pos[1] < 6) {
				if (atom_pos[0] < 8) {
					atom_pos[0]++;
				} else {
					atom_pos[0] = 0;
					atom_pos[1]++;
				}
			} else {
				if (atom_pos[0] < 5) {
					atom_pos[0]++;
				} else {
					atom_pos = [0, 0];
				}
			}
			$('anim_sprite').setStyle('background-position', atom_pos[0]*-200 + 'px ' + atom_pos[1]*-180 + 'px');
		}.periodical(15);
	} else {
		$clear(atom_interval);
	}
}

function loadAni(start, el) {
	el.setStyle('display', start ? 'block' : 'none');
	if (start && load_interval == 0) {
		load_interval = function () {
			if (load_pos < 23) {
				load_pos++;
			} else {
				load_pos = 0;
			}
			el.setStyle('background-position', '0 ' + load_pos*-20 + 'px');
		}.periodical(25);
	} else if (!start) {
		$clear(load_interval);
		load_interval = 0;
	}
}