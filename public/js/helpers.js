//Contains helper functions
var Helpers = (function() {
	var api = {};

	var emailPattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);

	//Indicates if the given email address is valid
	api.isValidEmail = function(email) {
    	return emailPattern.test(email);
	};

	//Flattes the given errors
	api.flattenErrors = function(errors) {
		var flatten = [];

		for (var i in errors) {
			errors[i].forEach(function(error) {
				flatten.push(error);
			})
		}

		return flatten;
	}

	//Converts an image to base64
	api.convertImgToBase64URL = function(url, callback, outputFormat) {
	    var img = new Image();
	    img.crossOrigin = 'Anonymous';
	    
	    img.onload = function() {
	        var canvas = document.createElement('CANVAS'),
	        ctx = canvas.getContext('2d'), dataURL;
	        canvas.height = this.height;
	        canvas.width = this.width;
	        ctx.drawImage(this, 0, 0);
	        dataURL = canvas.toDataURL(outputFormat);
	        callback(dataURL);
	        canvas = null; 
	    };

	    img.src = url;
	}

	//Marks that the given field is invalid
    api.markInvalid = function(element) {
        element.parent().addClass("has-error");
    }

    //Marks that the given element is valid
    api.markValid = function(element) {
        element.parent().removeClass("has-error");
    }

    //Finds the index of the first element that matches the predicate
    api.indexOf = function(items, predicate) {
    	for (var i = 0; i < items.length; i++) {
    		if (predicate(items[i])) {
    			return i;
    		}
    	}

    	return -1;
    }

    //Selects al the given checkboxes
    api.selectAllCheckboxes = function(parent, name) {
    	var elements = parent.find("input[name='" + name + "']");
        var numSelected = parent.find("input[name='" + name + "']:checked").length;
        var numNotSelected = elements.length - numSelected;

        elements.each(function(i, element) {
            element = $(element);
            element.prop("checked", numNotSelected != 0);
        });
    }

    //Deselects al the given checkboxes
    api.deselectAllCheckboxes = function(parent, name) {
        var elements = parent.find("input[name='" + name + "']");
        
        elements.each(function(i, element) {
            element = $(element);
            element.prop("checked", false);
        });
    }

    //Returns the values for the given object
    api.values = function(obj) {
    	var values = [];

    	for (var key in obj) {
    		values.push(obj[key]);
    	}

    	return values;
    }

	return api;
})();