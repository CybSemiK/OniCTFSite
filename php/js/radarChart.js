document.addEventListener('DOMContentLoaded', function() {
    var canvas = document.getElementById('radarChart');
    if (canvas) {
        var ctx = canvas.getContext('2d');
        var radarChart = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: categories,
                datasets: [{
                    label: 'Nombre de flags par catégorie',
                    data: dataPoints,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgb(54, 162, 235)',
                    pointBackgroundColor: 'rgb(54, 162, 235)'
                }]
            },
            options: {
                scale: {
                    r: {
                        angleLines: {
                            display: false
                        },
                        suggestedMin: 0,
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
               responsive: true
            }
        });
    } else {
        console.error("Canvas element #radarChart not found!");
    }
});
