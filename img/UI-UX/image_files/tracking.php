
// polyfills
// https://developer.mozilla.org/en-US/docs/Web/API/Element/matches
if (window.Element && !Element.prototype.matches) {
    Element.prototype.matches =
        Element.prototype.matchesSelector ||
        Element.prototype.mozMatchesSelector ||
        Element.prototype.msMatchesSelector ||
        Element.prototype.oMatchesSelector ||
        Element.prototype.webkitMatchesSelector ||
        function(s) {
            var matches = (this.document || this.ownerDocument).querySelectorAll(s),
                i = matches.length;
            while (--i >= 0 && matches.item(i) !== this) {}
            return i > -1;
        };
}
// https://developer.mozilla.org/en-US/docs/Web/API/Element/closest
if (window.Element && !Element.prototype.closest) {
    Element.prototype.closest =
    function(s) {
        var matches = (this.document || this.ownerDocument).querySelectorAll(s),
            i,
            el = this;
        do {
            i = matches.length;
            while (--i >= 0 && matches.item(i) !== el) {};
        } while ((i < 0) && (el = el.parentElement));
        return el;
    };
}
// https://developer.mozilla.org/en-US/docs/Web/API/Navigator/sendBeacon
if (!navigator.sendBeacon) {
	navigator.sendBeacon = function sendBeacon(url, data) {
    	var xhr = ('XMLHttpRequest' in window) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    	//make this synchronous so it must be sent before unload happens
    	xhr.open('POST', url, false);
    	xhr.setRequestHeader('Accept', '*/*');
    	if (typeof data === 'string') {
      		xhr.setRequestHeader('Content-Type', 'text/plain;charset=UTF-8');
      		xhr.responseType = 'text/plain';
    	} else if (Object.prototype.toString.call(data) === '[object Blob]') {
      		if (data.type) {
	        	xhr.setRequestHeader('Content-Type', data.type);
      		}
   		}
   		try {
		    xhr.send(data);
		}
		catch(e) {
			//stop CORS errors from being thrown
		}
    	return true;
	};
}

//Helper object
function DateBuilder(dayValue, monthValue, yearValue, monthBase) {
    var date = new Date(),
        base = 0,
        day, month, year;
    if (this.constructor !== DateBuilder) {
        return new DateBuilder(dayValue, monthValue, yearValue, monthBase)
    } else {
        this.dayValue = function(value) {
            value = parseInt(value) >> 0;
            date.setDate(value);
            day = date.getDate();
            month = date.getMonth() + 1;
            year = date.getFullYear();
            return this;
        };
        this.monthValue = function(value) {
            value = (parseInt(value) >> 0) - base;
            date.setMonth(value);
            month = date.getMonth() + 1;
            year = date.getFullYear()
            return this;
        }
        this.yearValue = function(value) {
            value = parseInt(value) >> 0;
            date.setFullYear(value);
            year = date.getFullYear();
            return this;
        }
        this.monthBase = function(value) {
            value = +(!!Number(value)); //convert any value to 1 or 0
            base = value;
            return this;
        }
        this.shiftDays = function(value) {
            value = parseInt(value) >> 0;
            date.setDate(date.getDate() + value);
            day = date.getDate();
            month = date.getMonth() + 1;
            year = date.getFullYear();
            return this;
        }
        this.toString = function() {
            return year + '-' + ('0' + month).slice(-2) + '-' + ('0' + day).slice(-2);
        };
        this.monthBase(monthBase || 0).yearValue(yearValue || date.getFullYear()).monthValue(monthValue || date.getMonth() + base).dayValue(dayValue || date.getDate());
    }
}
DateBuilder.YYYYMMDD = function(value) {
	var re = /^([0-9]{4}).?([0-9]{1,2}).?([0-9]{1,2})$/;
	var v = re.exec(value);
	return v ? [v[3],v[2],v[1]] : [];
};
DateBuilder.MMDDYYYY = function(value) {
	var re = /^([0-9]{1,2}).?([0-9]{1,2}).?([0-9]{4})$/;
	var v = re.exec(value);
	return v ? [v[2],v[1],v[3]] : [];
};
DateBuilder.DDMMYYYY =  function(value) {
	var re = /^([0-9]{1,2}).?([0-9]{1,2}).?([0-9]{4})$/;
	var v = re.exec(value);
	return v ? [v[1],v[2],v[3]] : [];
}
DateBuilder.fromString = function(value,parser) {
	if (!parser) parser = DateBuilder.YYYYMMDD;
    var vals = parser(value);
    return new DateBuilder(vals[0], vals[1], vals[2], vals.length).monthBase(0);
};
DateBuilder.fromDate = function(date) {
	try {
		return new DateBuilder(date.getDate(),date.getMonth(),date.getFullYear(),0);
	}
	catch(e) {
		return new DateBuilder();
	}
};
DateBuilder.fromTimestamp = function(ts) {
	return new DateBuilder(new Date(ts));
};
DateBuilder.dayValue = function(value) {
    return new DateBuilder().dayValue(value);
};
DateBuilder.monthValue = function(value) {
    return new DateBuilder().monthValue(value);
};
DateBuilder.yearValue = function(value) {
    return new DateBuilder().yearValue(value);
};
DateBuilder.monthBase = function(value) {
    return new DateBuilder().monthBase(value);
};


//Main Object
TC_Tracker = new (function () {
    var data = {},
    	queryData,
        beaconSent = false,
        blurEvent = 'blur',
        changeEvent = 'change',
        clickEvent = 'click',
        eventTrackers = {},
        registerDeligateListener = function(event, query, elementProperty, trackProperty) {
            //noop. will be replaced if browser complies.
        },
        reportingUri = 'https://www.tcgms.net/abandonment/tc_web_nav.php',
        self = this;
    function setCookie(cname, cvalue, exdays) {
        var d = new Date();
        d.setTime(d.getTime() + (exdays*24*60*60*1000));
        var expires = "expires="+d.toUTCString();
        document.cookie = cname + "=" + cvalue + "; " + expires + ", path=/";
    }
    function sendData() {
		if (!beaconSent) {
			delete data[self.CURRENCY]; //not needed on the server.
			var payload = JSON.stringify(data);
			self.debug && console.log('send data: ' + payload);
			navigator.sendBeacon(reportingUri,payload);
			beaconSent = true;
		}
    }
    function sendRoomData(roomData) {
    	roomData[this.PAGE_TYPE] = data[this.PAGE_TYPE];
    	roomData[internal.SIGNATURE] = data[internal.SIGNATURE];
    	roomData[internal.PUSH_LIST] = data[internal.PUSH_LIST];
    	roomData[internal.LOP] = data[internal.LOP];
	    roomData[internal.PAGE_URL] = data[internal.PAGE_URL];
	    roomData[internal.VERSION_NAME] = internal.VERSION_VALUE;
		var payload = JSON.stringify(roomData);
		self.debug && console.log('send room data: ' + payload);
		navigator.sendBeacon(reportingUri,payload);
    }
    var internal = {};
    internal.SIGNATURE = 'sig';
    internal.PUSH_LIST = 'pushListId';
    internal.LOP = 'lopMid';
    internal.PAGE_URL = 'pageUrl';
    internal.VERSION_NAME = 'tcAbandonmentVersion';
    internal.VERSION_VALUE = '2';
    
    this.debug = false;

	this.PAGE_TYPE = 'currentPageType'; //Enum("GENERAL", "PROCESSING_PAGE", "DATE_PICK", "PERSONAL_INFO", "CONFIRMATION", "COMPLETED_PAGE");
	this.PAGE_TYPE_GENERAL = "GENERAL";
	this.PAGE_TYPE_PROCESSING = "PROCESSING_PAGE";
	this.PAGE_TYPE_DATE_PICK = "DATE_PICK";
	this.PAGE_TYPE_PERSONAL_INFO = "PERSONAL_INFO";
	this.PAGE_TYPE_CONFIRMATION = "CONFIRMATION";
	this.PAGE_TYPE_COMPLETED_BOOKING = "COMPLETED_PAGE"; //auto-fixes a bug for previous installs
 	this.PAGE_TYPE_COMPLETED_PAGE = "COMPLETED_PAGE";

	this.BOOKING_ENGINE = 'bookingEngine'; //String
	this.CHECK_IN = 'checkIn'; //'(yyyy-mm-dd)
	this.CHECK_OUT = 'checkOut'; // (yyyy-mm-dd)
	this.CHILD_AGES = 'childAge'; //comma-delim Integers
	this.CLIENT_CURRENCY = 'clientCurrency'; //String
	this.CURR_ROOM = 'currentRoom'; //(Integer)
	this.CURRENCY = 'currency'; //String;
	this.DISCOUNT_CODE = 'discountCode'; //String
	this.DISCOUNT_TYPE = 'discountType'; //String
	this.EMAIL = 'email'; //email
	this.F_NAME = 'firstName'; //String
	this.L_NAME = 'lastName'; //String
	this.LANGUAGE = 'language'; //String
	this.META_PARTNER = 'metaPartners'; //String
	this.NUM_ADULTS = 'adults'; //Integer
	this.NUM_CHILDREN = 'children'; //Integer
	this.NUM_INFANTS = 'infants'; //Integer
	this.NUM_ROOMS = 'numberOfRooms'; //Integer
	this.OPT_IN = 'tcgmsOptIn'; // Boolean
	this.PORTAL_CODE = 'portal'; //String
	this.PROPERTY_CODE = 'propertyCode'; //String
	this.RATE = 'rate'; // (float)
	this.RATE_CODE = 'rateCode'; //String
	this.RES_ID = 'webReservationId'; //String
	this.REVENUE = 'revenue'; //float;
	this.ROOM_CODE = 'roomCode'; //String
    
    this.addDelegate = function(event, query, elementCheck, trackProperty) {
        switch (String(event).toLowerCase()) {
            case 'blur':
                event = blurEvent;
                break;
            case 'change':
                event = changeEvent;
                break;
            case 'click':
            	event = clickEvent;
                break;
            default:
                return;
        }
        registerDeligateListener(event, query, elementCheck, trackProperty);
    };
    this.getQueryProperty = function(queryProperty) {
    	if (!queryData) {
	    	queryData = queryStringToData();
	    }
	    return queryData[queryProperty];
    };
    this.setProperty = function(property, value) {
        switch (property) {
            case self.PAGE_TYPE:
                switch (value) {
                    case self.PAGE_TYPE_DATE_PICK:
                    case self.PAGE_TYPE_PROCESSING:
                    case self.PAGE_TYPE_PERSONAL_INFO:
                    case self.PAGE_TYPE_CONFIRMATION:
                    case self.PAGE_TYPE_COMPLETED_PAGE:
                        break;
                    default:
                        value = self.PAGE_TYPE_GENERAL;
                }
                break;
            case self.REVENUE: //float;
            case self.RATE:
                value = (parseFloat(value) || 0).toFixed(2);
                if (data[self.CURRENCY]) {
                	value = data[self.CURRENCY]+' ' +value;
                }
                break;
            case self.CHECK_IN:
            case self.CHECK_OUT:
                if (value != DateBuilder.fromString(value).toString()) {
                    //only set if valid;
                    return;
                }
                break;
            case self.NUM_ADULTS: //Integer
            case self.NUM_CHILDREN:
            case self.NUM_INFANTS:
            case self.NUM_ROOMS: 
            case self.CURR_ROOM:
                value = parseInt(value) >> 0;
                break;
            case self.OPT_IN: //boolean
                value = +(!!value); //change to 1 or 0
                break;
            case self.CURRENCY: //string
            	value = String(value);
	            if (data[self.REVENUE]) {
	                //if a currency value existed, pull it off the front
	            	var amt = data[self.REVENUE].split(' ').pop();
	            	data[self.REVENUE] = value + ' ' + amt;
	            } //float;
	            if (data[self.RATE]) {
	                //if a currency value existed, pull it off the front
	            	var amt = data[self.RATE].split(' ').pop();
	            	data[self.RATE] = value + ' ' + amt;
	            }
                break;
            case self.EMAIL: //email
	            //TODO:validate email
			case self.CHILD_AGES: //comma-delim Integers
				//TODO: validate values			
            case self.BOOKING_ENGINE: //String
            case self.CLIENT_CURRENCY:
            case self.DISCOUNT_CODE: 
            case self.DISCOUNT_TYPE: 
            case self.F_NAME: 
            case self.L_NAME:
            case self.LANGUAGE:
            case self.META_PARTNER:
            case self.PORTAL_CODE:
            case self.PROPERTY_CODE:
            case self.RES_ID:
            case self.RATE_CODE:
            case self.ROOM_CODE:
                value = String(value).trim();
                break;
            default: //unknown trackProperty
                self.debug && console.log('ignoring unknown property',property,value);
                return;
        }
        self.debug && console.log('setting property',property,value);
        data[property] = value;
    };
    this.setPropertyFrom = function(trackProperty,query,elementProperty) {
    	var property = elementProperty || 'value',
    	    el = document.querySelector(query);
	    self.debug && console.log('attempting to read property',property,'from',query,el && el.tagName,el && '#'+el.id,el && 'classes:',el && el.className);
    	if (el && property in el) {
	    	self.setProperty(trackProperty,el[property]);
    	}
    	else if (el && !elementProperty) { //no property specified
	        self.debug && console.log('attempting to read inner text from',el.tagName,'#'+el.id,'classes:',el.className);
    		if (el.innerText || el.textContent) {
	    		self.setProperty(trackProperty,el.innerText || el.textContent);
	    	}
    	}
    };
    this.clearProperty = function(property) {
        switch (property) {
            case self.PAGE_TYPE:
		        self.debug && console.log('resetting property',property);
                data[property] = self.PAGE_TYPE_GENERAL;
                break;
            case self.CURRENCY:
		        self.debug && console.log('clearing property',property);
		        delete data[property];
		        self.debug && console.log('and removing it from property',self.REVENUE,'&',self.RATE);
		        delete data[property];
            	data[self.REVENUE] = data[self.REVENUE].split(' ').pop();
            	data[self.RATE] = data[self.RATE].split(' ').pop();
            	break;
            case self.REVENUE: //float;
            case self.RATE: 
            case self.CHECK_IN: //Date
            case self.CHECK_OUT:
            case self.NUM_ADULTS: //Integer
            case self.NUM_CHILDREN:
            case self.NUM_INFANTS:
            case self.NUM_ROOMS: 
            case self.CURR_ROOM:
            case self.OPT_IN: //boolean
            case self.EMAIL: //email
			case self.CHILD_AGES: //comma-delim Integers
            case self.BOOKING_ENGINE: //String
            case self.CLIENT_CURRENCY:
            case self.DISCOUNT_CODE: 
            case self.DISCOUNT_TYPE: 
            case self.F_NAME: 
            case self.L_NAME:
            case self.LANGUAGE:
            case self.META_PARTNER:
            case self.PORTAL_CODE:
            case self.PROPERTY_CODE:
            case self.RES_ID:
            case self.RATE_CODE:
            case self.ROOM_CODE:
		        self.debug && console.log('clearing property',property);
		        delete data[property];
                break;
            default: //unknown trackProperty
                self.debug && console.log('ignoring unknown property',property);
                return;
        }
    };
	this.sendAdditionalRoomData = function sendAdditionalRoom(currentRoom,rate,rateCode,roomCode) {
		var roomData = {};
		var value;
		value = parseInt(currentRoom) >> 0;
		//currentRoom invalid or not supplied. quit.
		if (value < 1) {
		    self.debug && console.log('improper value for currentRoom',currentRoom,'skipped sending room data');
			return;
		}
		else {
			roomData[self.CURR_ROOM] = value;
	        value = (parseFloat(rate) || 0).toFixed(2);
	        if (value !== '0.00') {
		        if (data[self.CURRENCY]) {
		        	value = data[self.CURRENCY]+' ' + value;
		        }
		        roomData[self.RATE] = value;
		    }
		    else {
		        roomData[self.RATE] = '';
		    }
			roomData[self.RATE_CODE] = rateCode ? String(rateCode).trim() : '';
			roomData[self.ROOM_CODE] = rateCode ? String(roomCode).trim() : '';
			sendRoomData(roomData);
		}
	}
    this.sendTrackingData = function(stopAutoSend) {
    	beaconSent = false;
    	sendData();
    	beaconSent = !!stopAutoSend;
    };
    data[this.PAGE_TYPE] = this.PAGE_TYPE_GENERAL;
    data[internal.SIGNATURE] = "sig5e19d73384e96";
    data[internal.PUSH_LIST] = "";
    data[internal.LOP] = "t36n37j32i00";
    data[internal.PAGE_URL] = window && window.location && window.location.href;
    data[internal.VERSION_NAME] = internal.VERSION_VALUE;
    setCookie('zdt36n37j32i00','sig5e19d73384e96',365);
    if (document && document.querySelector && document.addEventListener) {
        registerDeligateListener = function(event, query, elementCheck, trackProperty) {
            if (!eventTrackers[event]) {
            	self.debug && console.log('creating listener for',event);
                eventTrackers[event] = [];
                document.addEventListener(event, function(e) {
	            	self.debug && console.log('for',event,'fired');
                    setTimeout(function asyncEventListener() {
	                    var t,el;
	                    for (var i = 0; i < eventTrackers[event].length; i++) {
                            el = e.target;
	                        t = eventTrackers[event][i];
			            	self.debug && console.log('checking query',t.query);
	                        if ((event === clickEvent && (el = el && el.closest(t.query))) ||
	                            (el && el.matches(t.query))) {
	                            self.debug && console.log('track',t);
	                            self.debug && console.log('matched',el.tagName,'#'+el.id,'classes:',el.className);
	                            if (typeof t.elementCheck === 'function') {
	                                t.trackProperty ?
	                                	self.setProperty(t.trackProperty,t.elementCheck.apply(el, [e])) :
	                                	t.elementCheck.apply(el, [e]);
	                            } else if (t.elementCheck && t.trackProperty) {
	                            	self.debug && console.log(t.trackProperty,t.elementCheck,el[t.elementCheck]);
	                                self.setProperty(t.trackProperty,el[t.elementCheck]);
	                            }
	                        }
	                    }
	               },0);
                }, true);
            }
            eventTrackers[event].push({
                'query': query,
                'elementCheck': elementCheck,
                'trackProperty': trackProperty
            });
        }
        // http://stackoverflow.com/a/20322988/6826702
        window.addEventListener('beforeunload',function(e) {
	        sendData(); //works in chrome only
        },false);
        window.addEventListener('unload',function(e) {
	        sendData(); //works in other browsers.
        },false);
    }
    else if (document && document.querySelector && document.attachEvent) { //IE 8
        blurEvent = changeEvent = 'onfocusout';
        clickEvent = 'onClick';
        registerDeligateListener = function(event, query, elementCheck, trackProperty) {
            if (!eventTrackers[event]) {
                eventTrackers[event] = [];
                document.attachEvent(event, function(e) {
                    var el = e.srcElement;
                    setTimeout(function asyncEventListener() {
	                    var t;
	                    for (var i = 0; i < eventTrackers[event].length; i++) {
	                        t = eventTrackers[event][i];
	                        if ((event === clickEvent && (el = el && el.closest(t.query))) ||
	                            (el && el.matches(t.query))) {
	                            if (typeof t.elementCheck === 'function') {
	                                t.trackerProperty ?
	                                	TC_Tracker.setProperty(t.trackerProperty,t.elementCheck.apply(el, [e])) :
	                                	t.elementCheck.apply(el, [e]);
	                            } else if (t.trackerProperty && t.elementCheck && t.elementCheck in el) {
	                                TC_Tracker.setProperty(t.trackerProperty,el[t.elementCheck]);
	                            }
	                        }
	                    }
	                },0);
                });
            }
            eventTrackers[event].push({
                'query': query,
                'elementCheck': elementCheck,
                'trackProperty': trackProperty
            });
        }
        window.attachEvent('onunload',function(e) {
	        sendData();
        });
    }
})();

(function() {
    if (location.hostname=='gc.synxis.com') {
    	TC_Tracker.setProperty(TC_Tracker.PAGE_TYPE,TC_Tracker.PAGE_TYPE_PROCESSING);
    }
    else {
    	TC_Tracker.setProperty(TC_Tracker.PAGE_TYPE,'COMPLETED_PAGE');
	TC_Tracker.sendTrackingData(true);
	return;    
    }
	if(window._GlobalTokens) {
		if (TC_Tracker.PROPERTY_CODE && _GlobalTokens.HOTEL_ID) {
			TC_Tracker.setProperty(TC_Tracker.PROPERTY_CODE,_GlobalTokens.HOTEL_ID);
		}
		if (_GlobalTokens.ArrivalDtYYYYMMDD) {
			TC_Tracker.setProperty(TC_Tracker.CHECK_IN,_GlobalTokens.ArrivalDtYYYYMMDD);
		}
		if (_GlobalTokens.DepartDtYYYYMMDD) {
			TC_Tracker.setProperty(TC_Tracker.CHECK_OUT,_GlobalTokens.DepartDtYYYYMMDD);
		}
		if (_GlobalTokens.RmQty) {
			TC_Tracker.setProperty(TC_Tracker.NUM_ROOMS,_GlobalTokens.RmQty);
		}
		if (_GlobalTokens.RateTypCd) {
			TC_Tracker.setProperty(TC_Tracker.RATE_CODE,_GlobalTokens.RateTypCd);
		}
		if (_GlobalTokens.RmTypCd) {
			TC_Tracker.setProperty(TC_Tracker.ROOM_CODE,_GlobalTokens.RmTypCd);
		}
		if (_GlobalTokens.CurrCode) {
			TC_Tracker.setProperty(TC_Tracker.CURRENCY,_GlobalTokens.CurrCode);
		}
		if (_GlobalTokens.DailyRate) {
			TC_Tracker.setProperty(TC_Tracker.RATE,_GlobalTokens.DailyRate);
		}
		if (_GlobalTokens.TotalCost) {
			TC_Tracker.setProperty(TC_Tracker.REVENUE,_GlobalTokens.TotalCost);
		}
		if (_GlobalTokens.CFirstName) {
			TC_Tracker.setProperty(TC_Tracker.F_NAME,_GlobalTokens.CFirstName);
		}
		if (_GlobalTokens.CLastName) {
			TC_Tracker.setProperty(TC_Tracker.L_NAME,_GlobalTokens.CLastName);
		}
		if (_GlobalTokens.CEmail) {
			TC_Tracker.setProperty(TC_Tracker.EMAIL,_GlobalTokens.CEmail);
		}
		if (_GlobalTokens.ViewName) {
			switch(_GlobalTokens.ViewName) {
			case "Check Availability":
				TC_Tracker.setProperty(TC_Tracker.PAGE_TYPE,TC_Tracker.PAGE_TYPE_DATE_PICK);
				TC_Tracker.addDelegate('click','.ui-datepicker-calendar td',function() {
					var el = document.querySelector('[id$="_CalendarAvailability_hiddenField"]');
					var value = el && el.value;
					var date,db;
					if (value && value.length) {
						value = value.split(','); //list of comma delimited dates from start to end
						date = value.shift();
						if (date) {
							db = DateBuilder.fromString(date,DateBuilder.MMDDYYYY);
							TC_Tracker.setProperty(TC_Tracker.CHECK_IN,db.toString());
						}
						date = value.pop();
						if (date) {
							db = DateBuilder.fromString(date,DateBuilder.MMDDYYYY);
							TC_Tracker.setProperty(TC_Tracker.CHECK_OUT,db.toString());
						}
					}
				});
				break;
			case "Customer Information":
				TC_Tracker.setProperty(TC_Tracker.PAGE_TYPE,TC_Tracker.PAGE_TYPE_PERSONAL_INFO);
				function piSetter (e) {
					TC_Tracker.setPropertyFrom(TC_Tracker.F_NAME,'.PersonalInfoDiv [id$="_FirstNameTextBox"]');
					TC_Tracker.setPropertyFrom(TC_Tracker.L_NAME,'.PersonalInfoDiv [id$="_LastNameTextBox"]');
					TC_Tracker.setPropertyFrom(TC_Tracker.EMAIL,'.PersonalInfoDiv [id$="_EmailTextBox"]');
					TC_Tracker.setPropertyFrom(TC_Tracker.OPT_IN,'[id$="OptInCheckBox"]','checked');
					TC_Tracker.sendTrackingData(false);
				}
				TC_Tracker.addDelegate('blur','.PersonalInfoDiv [id$="_FirstNameTextBox"],.PersonalInfoDiv [id$="_LastNameTextBox"],.PersonalInfoDiv [id$="_EmailTextBox"]',piSetter);
				TC_Tracker.addDelegate('change','[id$="OptInCheckBox"]',piSetter);
				break;				
			case "Reservation Review":
				TC_Tracker.setProperty(TC_Tracker.PAGE_TYPE,TC_Tracker.PAGE_TYPE_CONFIRMATION);
				break;
			case "Confirmation":
				var confirmation = window.Xbe && Xbe.State && Xbe.State.confirmNumber || false,el;
				if (confirmation && (el = document.querySelector('[id$="ConfirmNumberRow"]')) && (el.innerText || el.textContent).length > 10) {
					TC_Tracker.setProperty(TC_Tracker.PAGE_TYPE,TC_Tracker.PAGE_TYPE_COMPLETED_BOOKING);
					TC_Tracker.setProperty(TC_Tracker.RES_ID,confirmation);
					TC_Tracker.sendTrackingData(true);
				}
			}
		}
	}
})();