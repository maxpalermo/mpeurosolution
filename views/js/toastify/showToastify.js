function showToastify(title, message, type) {
    let background_color = null;
    let color = null;
    let node = null;

    switch (type) {
        case 'success':
            background_color = '#00b09b';
            color = '#fff';
            break;
        case 'warning':
            background_color = '#f0ad4e';
            color = '#fff';
            break;
        case 'danger':
            background_color = '#d9534f';
            color = '#fff';
            break;
        case 'info':
            background_color = '#5bc0de';
            color = '#fff';
            break;
        default:
            background_color = '#00b09b';
            color = '#fff';
            break;
    }

    node = $("<div>")
        .addClass("panel-body")
        .append($("<h4>").text(title))
        .append($("<p>").text(message));

    toast = Toastify({
        text: "",
        node: node[0],
        duration: 3000,
        destination: "",
        newWindow: true,
        className: "bg-info",
        close: false,
        gravity: "top", // `top` or `bottom`
        position: "center", // `left`, `center` or `right`
        stopOnFocus: true, // Prevents dismissing of toast on hover
        style: {
            background: background_color,
            color: color,
        },
        offset: {
            x: "0", // horizontal axis - can be a number or a string indicating unity. eg: '2em'
            y: "400", // vertical axis - can be a number or a string indicating unity. eg: '2em'
        },
        onClick: function(e) {
            toast.hideToast();
        } // Callback after click
    });

    toast.showToast();
}
