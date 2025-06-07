(function ($) {
    "use strict";
    if ($("#visit-sale-chart").length) {
        const ctx = document
            .getElementById("visit-sale-chart")
            .getContext("2d");

        const gradientStrokeViolet = ctx.createLinearGradient(0, 0, 0, 181);
        gradientStrokeViolet.addColorStop(0, "rgba(218, 140, 255, 1)");
        gradientStrokeViolet.addColorStop(1, "rgba(154, 85, 255, 1)");

        const gradientStrokeBlue = ctx.createLinearGradient(0, 0, 0, 360);
        gradientStrokeBlue.addColorStop(0, "rgba(54, 215, 232, 1)");
        gradientStrokeBlue.addColorStop(1, "rgba(177, 148, 250, 1)");

        const gradientStrokeRed = ctx.createLinearGradient(0, 0, 0, 300);
        gradientStrokeRed.addColorStop(0, "rgba(255, 191, 150, 1)");
        gradientStrokeRed.addColorStop(1, "rgba(254, 112, 150, 1)");

        // Your dynamic data variables (replace with actual values)
        const activeCount = window.activeCount || 0;
        const inactiveCount = window.inactiveCount || 0;
        const repairingCount = window.repairingCount || 0;
        const assignedCount = window.assignedCount || 0;
        const unassignedCount = window.unassignedCount || 0;

        new Chart(ctx, {
            type: "bar",
            data: {
                labels: [
                    "Active",
                    "Inactive",
                    "En réparation",
                    "Attribué",
                    "Non attribué",
                ],
                datasets: [
                    {
                        label: "Devices",
                        data: [
                            activeCount,
                            inactiveCount,
                            repairingCount,
                            assignedCount,
                            unassignedCount,
                        ],
                        backgroundColor: [
                            gradientStrokeViolet,
                            gradientStrokeRed,
                            gradientStrokeBlue,
                            gradientStrokeViolet,
                            gradientStrokeRed,
                        ],
                        borderWidth: 1,
                        borderColor: [
                            "rgba(154, 85, 255, 1)",
                            "rgba(254, 112, 150, 1)",
                            "rgba(177, 148, 250, 1)",
                            "rgba(154, 85, 255, 1)",
                            "rgba(254, 112, 150, 1)",
                        ],
                        barPercentage: 0.5,
                        categoryPercentage: 0.5,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                        },
                    },
                    x: {
                        grid: {
                            display: false,
                        },
                    },
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                },
            },
        });
    }

    if ($("#traffic-chart").length) {
        const ctx = document.getElementById("traffic-chart").getContext("2d");

        // Gradients
        const gradientStrokeBlue = ctx.createLinearGradient(0, 0, 0, 181);
        gradientStrokeBlue.addColorStop(0, "rgba(54, 215, 232, 1)");
        gradientStrokeBlue.addColorStop(1, "rgba(177, 148, 250, 1)");

        const gradientStrokeRed = ctx.createLinearGradient(0, 0, 0, 50);
        gradientStrokeRed.addColorStop(0, "rgba(255, 191, 150, 1)");
        gradientStrokeRed.addColorStop(1, "rgba(254, 112, 150, 1)");

        const gradientStrokeGreen = ctx.createLinearGradient(0, 0, 0, 300);
        gradientStrokeGreen.addColorStop(0, "rgba(6, 185, 157, 1)");
        gradientStrokeGreen.addColorStop(1, "rgba(132, 217, 210, 1)");

        const gradients = [
            gradientStrokeBlue,
            gradientStrokeGreen,
            gradientStrokeRed,
        ];

        // Use window arrays directly
        const labels = window.deviceTypes || [];
        const data = window.devicePercentages || [];

        // Build labels with percentages
        const chartLabels = labels.map((label, i) => `${label} ${data[i]}%`);

        // Assign gradient colors dynamically (repeat if needed)
        const backgroundColors = labels.map(
            (_, i) => gradients[i % gradients.length]
        );

        new Chart(ctx, {
            type: "doughnut",
            data: {
                labels: chartLabels,
                datasets: [
                    {
                        data: data,
                        backgroundColor: backgroundColors,
                        hoverBackgroundColor: backgroundColors,
                        borderColor: backgroundColors,
                    },
                ],
            },
            options: {
                cutout: 50,
                animationEasing: "easeOutBounce",
                animateRotate: true,
                animateScale: false,
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: "bottom",
                    },
                },
            },
        });
    }

    if ($("#inline-datepicker").length) {
        $("#inline-datepicker").datepicker({
            enableOnReadonly: true,
            todayHighlight: true,
        });
    }
    if ($.cookie("purple-pro-banner") != "true") {
        document.querySelector("#proBanner").classList.add("d-flex");
        document.querySelector(".navbar").classList.remove("fixed-top");
    } else {
        document.querySelector("#proBanner").classList.add("d-none");
        document.querySelector(".navbar").classList.add("fixed-top");
    }

    if ($(".navbar").hasClass("fixed-top")) {
        document.querySelector(".page-body-wrapper").classList.remove("pt-0");
        document.querySelector(".navbar").classList.remove("pt-5");
    } else {
        document.querySelector(".page-body-wrapper").classList.add("pt-0");
        document.querySelector(".navbar").classList.add("pt-5");
        document.querySelector(".navbar").classList.add("mt-3");
    }
    document
        .querySelector("#bannerClose")
        .addEventListener("click", function () {
            document.querySelector("#proBanner").classList.add("d-none");
            document.querySelector("#proBanner").classList.remove("d-flex");
            document.querySelector(".navbar").classList.remove("pt-5");
            document.querySelector(".navbar").classList.add("fixed-top");
            document
                .querySelector(".page-body-wrapper")
                .classList.add("proBanner-padding-top");
            document.querySelector(".navbar").classList.remove("mt-3");
            var date = new Date();
            date.setTime(date.getTime() + 24 * 60 * 60 * 1000);
            $.cookie("purple-pro-banner", "true", {
                expires: date,
            });
        });
})(jQuery);
