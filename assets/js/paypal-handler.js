const paypalButtons = window.paypal.Buttons({
    style: {
        shape: "rect",
        layout: "vertical",
        color: "gold",
        label: "paypal",
    },

    async createOrder() {
        try {
            const response = await fetch("/api/paypal/order/create", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({}),
            });

            const orderData = await response.json();

            if (orderData.id) {
                return orderData.id;
            }
            const errorDetail = orderData?.details?.[0];
            const errorMessage = errorDetail
                ? `${errorDetail.issue} ${errorDetail.description} (${orderData.debug_id})`
                : JSON.stringify(orderData);

            throw new Error(errorMessage);
        } catch (error) {
            console.error(error);
            resultMessage(
                `Impossible d'initier le paiement PayPal...<br><br>${error}`,
            );
        }
    },
    async onApprove(data, actions) {
        try {
            const response = await fetch(
                `/api/paypal/order/capture/${data.orderID}`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                },
            );

            const orderData = await response.json();

            if (orderData.status === "success") { // Redirection vers la page de succès
                window.location.href = "/panier/merci?id=" + orderData.orderId;
            }

            const errorDetail = orderData?.details?.[0];

            if (errorDetail?.issue === "INSTRUMENT_DECLINED") {
                return actions.restart();
            } else if (errorDetail) {
                throw new Error(
                    `${errorDetail.description} (${orderData.debug_id})`,
                );
            } else if (!orderData.purchase_units) {
                throw new Error(JSON.stringify(orderData));
            } else {
                // Si ce n'est pas un succès global (statut 'COMPLETED' côté serveur),
                // mais que PayPal a renvoyé d'autres infos, on affiche le message par défaut
                const transaction =
                    orderData?.purchase_units?.[0]?.payments?.captures?.[0] ||
                    orderData?.purchase_units?.[0]?.payments
                        ?.authorizations?.[0];
                resultMessage(
                    `Transaction ${transaction.status}: ${transaction.id}<br>
                    <br>Voir la console pour tous les détails`,
                );
                console.log(
                    "Capture result",
                    orderData,
                    JSON.stringify(orderData, null, 2),
                );
            }
        } catch (error) {
            console.error(error);
            resultMessage(
                `Désolé, votre transaction n'a pas pu être traitée...<br><br>${error}`,
            );
        }
    },
});
paypalButtons.render("#paypal-button-container");

function resultMessage(message) {
    const container = document.querySelector("#result-message");
    container.innerHTML = message;
}

const cgvCheckbox = document.getElementById("agree-cgv");
const paypalWrapper = document.getElementById("paypal-wrapper");

cgvCheckbox.addEventListener("change", function () {
    if (this.checked) {
        paypalWrapper.style.pointerEvents = "auto";
        paypalWrapper.style.opacity = "1";
    } else {
        paypalWrapper.style.pointerEvents = "none";
        paypalWrapper.style.opacity = "0.5";
    }
});