let notyf;

document.addEventListener("DOMContentLoaded", function () {
    // Create the Notyf instance
    notyf = new Notyf({
        duration: 3000,
        ripple: true,
        dismissible: false,
        position: { x: window.current_direction === "rtl" ? "left" : "right", y: "top" },
        types: [
            {
                type: "info",
                background: config.colors.info,
                className: "notyf__info",
                icon: {
                    className: "icon-base ti tabler-info-circle-filled icon-md text-white",
                    tagName: "i"
                }
            },
            {
                type: "warning",
                background: config.colors.warning,
                className: "notyf__warning",
                icon: {
                    className: "icon-base ti tabler-alert-triangle-filled icon-md text-white",
                    tagName: "i"
                }
            },
            {
                type: "success",
                background: config.colors.success,
                className: "notyf__success",
                icon: {
                    className: "icon-base ti tabler-circle-check-filled icon-md text-white",
                    tagName: "i"
                }
            },
            {
                type: "error",
                background: config.colors.danger,
                className: "notyf__error",
                icon: {
                    className: "icon-base ti tabler-xbox-x-filled icon-md text-white",
                    tagName: "i"
                }
            }
        ]
    });
});

function showNotification(type, message) {
    if (!notyf) return console.error('Notyf is not initialized yet.');
    notyf.open({
        type: type,
        message: message
    });
}
function notifySuccess(msg) { showNotification('success', msg); }
function notifyError(msg) { showNotification('error', msg); }

function notifyInfo(msg) { showNotification('info', msg); }

function notifyWarning(msg) { showNotification('warning', msg); }
