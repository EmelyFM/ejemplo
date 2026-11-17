/**
 * Integración de Chart.js con datos reales de MySQL
 */

let chartAprobacionInstance = null;
let chartCursosInstance = null;
let chartDistribucionInstance = null;

async function cargarEstadisticasDashboard() {
    try {
        const res = await apiFetch(`${BASE_URL}/index.php?controller=estadistica&action=getDatosDashboard`);
        if (res.success) {
            const data = res.data;

            // 1. Gráfica de Dona: Aprobados vs Reprobados
            renderizarChartAprobacion(data.aprobados, data.reprobados);

            // 2. Gráfica de Barras: Promedio por Curso
            renderizarChartCursos(data.promedio_cursos);

            // 3. Gráfica de Distribución de Notas
            renderizarChartDistribucion(data.distribucion);
        }
    } catch (e) {
        console.error("Error al cargar estadísticas:", e);
    }
}

function renderizarChartAprobacion(aprobados, reprobados) {
    const canvas = document.getElementById('chartAprobacion');
    if (!canvas) return;

    if (chartAprobacionInstance) {
        chartAprobacionInstance.destroy();
    }

    const total = aprobados + reprobados;
    const datos = total > 0 ? [aprobados, reprobados] : [1, 0];
    const labels = total > 0 ? ['Aprobados (>= 50% pts)', 'Reprobados (< 50% pts)'] : ['Sin Evaluaciones', ''];

    chartAprobacionInstance = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: datos,
                backgroundColor: total > 0 ? ['#10b981', '#f43f5e'] : ['#e2e8f0', '#f1f5f9'],
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        font: { family: "'Plus Jakarta Sans', sans-serif", size: 12, weight: 600 }
                    }
                }
            },
            cutout: '72%'
        }
    });
}

function renderizarChartCursos(cursos) {
    const canvas = document.getElementById('chartCursos');
    if (!canvas) return;

    if (chartCursosInstance) {
        chartCursosInstance.destroy();
    }

    const labels = cursos.length > 0 ? cursos.map(c => c.nombre) : ['Sin evaluaciones'];
    const valores = cursos.length > 0 ? cursos.map(c => parseFloat(c.promedio)) : [0];

    chartCursosInstance = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Promedio General',
                data: valores,
                backgroundColor: '#6366f1',
                borderRadius: 8,
                barThickness: 28
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: val => val + ' pts',
                        font: { family: "'Plus Jakarta Sans', sans-serif" }
                    },
                    grid: { color: '#f1f5f9' }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: "'Plus Jakarta Sans', sans-serif" } }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
}

function renderizarChartDistribucion(distribucion) {
    const canvas = document.getElementById('chartDistribucion');
    if (!canvas) return;

    if (chartDistribucionInstance) {
        chartDistribucionInstance.destroy();
    }

    const labels = ['0 - 59 pts', '60 - 69 pts', '70 - 79 pts', '80 - 89 pts', '90 - 100 pts'];
    const valores = [
        distribucion['0-59'] || 0,
        distribucion['60-69'] || 0,
        distribucion['70-79'] || 0,
        distribucion['80-89'] || 0,
        distribucion['90-100'] || 0
    ];

    chartDistribucionInstance = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Cantidad de Alumnos',
                data: valores,
                backgroundColor: ['#f87171', '#fbbf24', '#38bdf8', '#818cf8', '#34d399'],
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 },
                    grid: { color: '#f1f5f9' }
                },
                x: {
                    grid: { display: false }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
}

// ==========================================
// ESTADÍSTICAS DE RENDIMIENTO DEL PROFESOR
// ==========================================

let chartAprobacionProfInstance = null;
let chartCursosProfInstance = null;
let chartExamenesProfInstance = null;

async function cargarEstadisticasProfesor() {
    try {
        const res = await apiFetch(`${BASE_URL}/index.php?controller=estadistica&action=getDatosProfesor`);
        if (res.success) {
            const data = res.data;

            // 1. Gráfica de Dona: Porcentaje de Aprobados vs Reprobados
            renderizarChartAprobacionProfesor(data.aprobados, data.reprobados, data.porcentaje_aprobados);

            // 2. Gráfica de Barras: Promedio de Calificaciones por Curso
            renderizarChartCursosProfesor(data.promedio_cursos);

            // 3. Gráfica de Barras: Resultados por Examen
            renderizarChartExamenesProfesor(data.resultados_examenes);
        }
    } catch (e) {
        console.error("Error al cargar estadísticas del profesor:", e);
    }
}

function renderizarChartAprobacionProfesor(aprobados, reprobados, porcentaje) {
    const canvas = document.getElementById('chartAprobacionProf');
    if (!canvas) return;

    const badge = document.getElementById('badgePorcentajeAprobadosProf');
    if (badge) {
        badge.innerText = `${porcentaje}% Aprobados`;
    }

    if (chartAprobacionProfInstance) {
        chartAprobacionProfInstance.destroy();
    }

    const total = aprobados + reprobados;
    const pctReprobados = total > 0 ? (100 - porcentaje).toFixed(1) : 0;
    const datos = total > 0 ? [aprobados, reprobados] : [1, 0];
    const labels = total > 0
        ? [`Aprobados: ${aprobados} (${porcentaje}%)`, `Reprobados: ${reprobados} (${pctReprobados}%)`]
        : ['Sin Evaluaciones', ''];

    chartAprobacionProfInstance = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: datos,
                backgroundColor: total > 0 ? ['#10b981', '#f43f5e'] : ['#e2e8f0', '#f1f5f9'],
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        font: { family: "'Plus Jakarta Sans', sans-serif", size: 12, weight: 600 }
                    }
                }
            },
            cutout: '72%'
        }
    });
}

function renderizarChartCursosProfesor(cursos) {
    const canvas = document.getElementById('chartCursosProf');
    if (!canvas) return;

    if (chartCursosProfInstance) {
        chartCursosProfInstance.destroy();
    }

    const labels = cursos.length > 0 ? cursos.map(c => c.nombre) : ['Sin cursos asignados'];
    const valores = cursos.length > 0 ? cursos.map(c => parseFloat(c.promedio)) : [0];

    chartCursosProfInstance = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Promedio del Curso (pts)',
                data: valores,
                backgroundColor: '#6366f1',
                borderRadius: 8,
                barThickness: 28
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: val => val + ' pts',
                        font: { family: "'Plus Jakarta Sans', sans-serif" }
                    },
                    grid: { color: '#f1f5f9' }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: "'Plus Jakarta Sans', sans-serif" } }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
}

function renderizarChartExamenesProfesor(examenes) {
    const canvas = document.getElementById('chartExamenesProf');
    if (!canvas) return;

    if (chartExamenesProfInstance) {
        chartExamenesProfInstance.destroy();
    }

    const labels = examenes.length > 0 ? examenes.map(ex => `${ex.nom_exam} (${ex.curso_nombre})`) : ['Sin exámenes'];
    const promedios = examenes.length > 0 ? examenes.map(ex => parseFloat(ex.promedio)) : [0];
    const maximas = examenes.length > 0 ? examenes.map(ex => parseFloat(ex.maxima)) : [0];
    const minimas = examenes.length > 0 ? examenes.map(ex => parseFloat(ex.minima)) : [0];

    chartExamenesProfInstance = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Promedio General (pts)',
                    data: promedios,
                    backgroundColor: '#6366f1',
                    borderRadius: 6
                },
                {
                    label: 'Nota Máxima (pts)',
                    data: maximas,
                    backgroundColor: '#10b981',
                    borderRadius: 6
                },
                {
                    label: 'Nota Mínima (pts)',
                    data: minimas,
                    backgroundColor: '#f43f5e',
                    borderRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: val => val + ' pts',
                        font: { family: "'Plus Jakarta Sans', sans-serif" }
                    },
                    grid: { color: '#f1f5f9' }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: "'Plus Jakarta Sans', sans-serif" } }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        font: { family: "'Plus Jakarta Sans', sans-serif", size: 12, weight: 600 }
                    }
                },
                tooltip: {
                    callbacks: {
                        afterBody: function(context) {
                            const idx = context[0]?.dataIndex;
                            if (examenes[idx]) {
                                const ex = examenes[idx];
                                return `Aprobados: ${ex.aprobados} | Reprobados: ${ex.reprobados} | Evaluados: ${ex.total_evaluaciones}`;
                            }
                            return '';
                        }
                    }
                }
            }
        }
    });
}
