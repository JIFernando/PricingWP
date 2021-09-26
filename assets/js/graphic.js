(async () => {
    const respuestRaw = await fetch("../wp-content/plugins/Pricing/includes/actions/pricingwp_graphic_data.php");
    const respuest = await respuestRaw.json();
    const $grafic = document.querySelector("#sales_by_day");
    const labels = respuest.dates; 
    const saleData = {
        label: "Sales by Day",
        data: respuest.data_sales, 
        backgroundColor: 'rgba(54, 162, 235, 0.2)', 
        borderColor: 'rgba(54, 162, 235, 1)', 
        borderWidth: 1, 
    };
    const $grafic2 = document.querySelector("#price_by_day");

    const priceData = {
        label: "Price by day",
        data: respuest.data_prices, 
        backgroundColor: 'rgba(54, 162, 235, 0.2)', 
        borderColor: 'rgba(54, 162, 135, 1)', 
        borderWidth: 1, 
    };
    new Chart($grafic, {
        type: 'line', 
        data: {
            labels: labels,
            datasets: [
                saleData,
            ]
        },
        options: {
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    }
                }],
            },
        }
    });
    new Chart($grafic2, {
        type: 'line', 
        data: {
            labels: labels,
            datasets: [
                priceData,
            ]
        },
        options: {
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    }
                }],
            },
        }
    });
})();