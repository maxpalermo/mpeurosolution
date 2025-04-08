async function bindButton(button) {
    button.addEventListener("click", async () => {
        const MPEUROSOLUTION_customerId = button.dataset.customer_id;
        const eurosolutionId = button.dataset.eurosolution_id;

        Swal.fire({
            title: "Modifica EuroSolution ID",
            input: "number",
            inputValue: eurosolutionId,
            inputAttributes: {
                "min-width": "8rem",
                "max-width": "8rem",
                "text-align": "center"
            },
            showCancelButton: true,
            confirmButtonText: "Salva",
            cancelButtonText: "Annulla"
        }).then(async (result) => {
            if (result.isConfirmed) {
                const newEurosolutionId = result.value;

                // Invia una richiesta AJAX per aggiornare il valore
                const response = await fetch(MPEUROSOLUTION_adminAjaxURL, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-PS-Module": "mpeurosolution",
                        "X-PS-Module-Version": "2.0.3",
                        "X-PS-method": "updateEurosolutionId",
                        "X-PS-ajax": 1
                    },
                    body: JSON.stringify({
                        ajax: 1,
                        action: "updateEurosolutionId",
                        id_customer: MPEUROSOLUTION_customerId,
                        id_eurosolution: newEurosolutionId,
                        id_employee: MPEUROSOLUTION_employeeId
                    })
                });

                const json = await response.json();

                if (json.success) {
                    Swal.fire("Successo!", "ID EuroSolution aggiornato.", "success");
                    const btnEurosolution = json.button;
                    if (MPEUROSOLUTION_orderId && MPEUROSOLUTION_orderId > 0) {
                        const productRow = document.querySelector(".product-row");
                        const customerCard = productRow.querySelector(".customer.card");
                        const customerCardHeader = customerCard.querySelector(".card-header");
                        const container = document.createElement("div");
                        container.classList.add("pull-right", "eurosolution-container");
                        container.innerHTML = btnEurosolution;
                        customerCardHeader.querySelector(".eurosolution-container").remove();
                        customerCardHeader.appendChild(container);
                        const rebindBtn = container.querySelector(".eurosolutionId");
                        bindButton(rebindBtn);
                        tippy(rebindBtn);
                    } else {
                        const td = button.closest("td");
                        td.replaceChildren();
                        td.innerHTML = btnEurosolution;
                        const tdBtn = td.querySelector(".eurosolutionId");
                        bindButton(tdBtn);
                        tippy(tdBtn);
                    }
                } else {
                    Swal.fire("Errore!", "Si è verificato un errore.", "error");
                }
            }
        });
    });
}
document.addEventListener("MpEurosolutionReady", async (e) => {
    // Configura MutationObserver
    const observer = new MutationObserver((mutationsList) => {
        for (const mutation of mutationsList) {
            if (mutation.type === "childList") {
                // Reinizializza Tippy.js sui nuovi elementi
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1 && node.classList.contains("eurosolutionId")) {
                        tippy(node);
                    }
                });
            }
        }
    });

    // Osserva il contenitore per cambiamenti nel DOM
    if (MPEUROSOLUTION_orderId && MPEUROSOLUTION_orderId > 0) {
        const container = document.querySelector(".product-row").querySelector(".customer.card").querySelector(".card-header");
        observer.observe(container, { childList: true });
    }

    const eurosolutionButtons = document.querySelectorAll(".eurosolutionId");
    eurosolutionButtons.forEach((button) => {
        bindButton(button);
    });

    if (MPEUROSOLUTION_orderId && MPEUROSOLUTION_orderId > 0) {
        const response = await fetch(MPEUROSOLUTION_adminAjaxURL, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest",
                "X-PS-Module": "mpeurosolution",
                "X-PS-Module-Version": "2.0.3",
                "X-PS-method": "getEurosolutionId",
                "X-PS-ajax": 1
            },
            body: JSON.stringify({
                ajax: 1,
                action: "renderButton",
                id_order: MPEUROSOLUTION_orderId,
                id_customer: 0,
                id_employee: MPEUROSOLUTION_employeeId
            })
        });

        const json = await response.json();
        const button = json;
        const productRow = document.querySelector(".product-row");
        const customerCard = productRow.querySelector(".customer.card");
        const customerCardHeader = customerCard.querySelector(".card-header");

        const container = document.createElement("div");
        container.classList.add("pull-right", "eurosolution-container");
        container.innerHTML = button;
        customerCardHeader.appendChild(container);
        const rebindBtn = container.querySelector(".eurosolutionId");
        bindButton(rebindBtn);
    }

    tippy(".eurosolutionId");

    const customerCard = document.querySelector(".card.customer-personal-informations-card");
    const eurosolutionRow = document.getElementById("mpeurosolution-personal-info");
    if (customerCard && eurosolutionRow) {
        const templateContent = eurosolutionRow.content;
        const cardBody = customerCard.querySelector(".card-body");
        const childNode = templateContent.cloneNode(true);

        cardBody.appendChild(childNode);
    }
});
