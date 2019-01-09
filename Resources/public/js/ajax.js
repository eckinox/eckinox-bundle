// Defines a generic ajax form submission
// Can be used by changing the "action" attribute by "ajax-action" on your form
// The request's response can be handle using the "ajax-response" event on your form (the response is stored in event.detail)
// You can define that the expected response is JSON by adding the following attribute to your form: [ajax-type="json"]
// When using [ajax-type="json"], the response received by "ajax-response" will already be parsed as JSON
document.addEventListener('submit', function(e) {
    if (!e.target.matches('form[ajax-action]')) {
        return;
    }

    let pageContentWrapper = document.querySelector('.page-wrapper > .content-wrapper > .content > .scrollable');
    let form = e.target;

    // Basic HTML form validation
    if (!form.checkValidity()) {
        return;
    }

    e.preventDefault();

    // If there's file upload within the form, display a loading screen
    let loadingScreen = null;
    let loadingScreenProgress = null;
    if (form.querySelectorAll('input[type="file"]').length) {
        // Display a loading screen while the upload and processing takes place
        loadingScreen = document.createElement('div');
        loadingScreen.innerHTML = '<div><i class="fa fa-spinner fa-spin"></i></div><progress value="0" max="100"></progress>';
        loadingScreen.classList.add('loading-screen');
        pageContentWrapper.appendChild(loadingScreen);
        loadingScreenProgress = loadingScreen.querySelector('progress');
    }

    let formData = new FormData(form);
    var xhr = new XMLHttpRequest();

    // When a response is received, pass it along via the "ajax_reponse" custom event
    xhr.addEventListener('load', function() {
        if (loadingScreen) {
            loadingScreen.parentNode.removeChild(loadingScreen);
        }

        let response = xhr.response;
        if (response && form.getAttribute('ajax-type') && form.getAttribute('ajax-type').toLowerCase() == 'json') {
            try {
                response = JSON.parse(response);
            } catch (error) {
                console.error("[ajax-form] The response is not a valid JSON string.");
                form.dispatchEvent(new CustomEvent('ajax-error', { bubbles: true, detail: "The response is not a valid JSON string." }));
                return;
            }
        }

        // Trigger the "ajax-response" event on the form with the response
        form.dispatchEvent(new CustomEvent('ajax-response', { bubbles: true, detail: response }));
    });

    // Error handling
    xhr.addEventListener('readystatechange', function(e) {
        if (xhr.readyState === 4 && xhr.status !== 200) {
            // Trigger the "ajax-error" event on the form with the error details
            form.dispatchEvent(new CustomEvent('ajax-error', { bubbles: true, detail: xhr.statusText }));

            if (loadingScreen) {
                loadingScreen.parentNode.removeChild(loadingScreen);
            }
        }
    });

    // Update the loading screen's progress bar if need be
    if (loadingScreen) {
        xhr.upload.addEventListener('progress', function(e) {
            var percentage = Math.round((e.loaded / e.total) * 100);
            loadingScreenProgress.value = percentage;
        });
    }

    xhr.open('POST', form.getAttribute('ajax-action'), true);
    xhr.send(formData);
});
