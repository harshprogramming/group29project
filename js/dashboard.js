const BASE = 'http://localhost:8000';

async function apiFetch(path) {
    const res = await fetch(BASE + path, { credentials: 'include' });
    const json = await res.json();
    if (!res.ok) throw new Error(json.message || 'Request failed');
    return json.data;
}

async function loadDashboard() {
    try {
        const [summaryData, analyzer, moodsData] = await Promise.all([
            apiFetch('/api/summary?period=week'),
            apiFetch('/api/analyzer'),
            apiFetch('/api/moods'),
        ]);

        // --- Stat cards ---
        const s = summaryData.summary;
        document.getElementById('totalEntries').textContent  = s.total_entries    ?? '—';
        document.getElementById('avgMood').textContent       = s.avg_mood_level   ?? '—';
        document.getElementById('avgStress').textContent     = s.avg_stress_level ?? '—';
        document.getElementById('avgIntensity').textContent  = s.avg_intensity    ?? '—';

        // --- Trend chart (last 14 entries, oldest → newest) ---
        const entries = [...moodsData.entries].reverse().slice(-14);
        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: entries.map(e => e.date),
                datasets: [
                    {
                        label: 'Mood Level',
                        data: entries.map(e => e.mood_level),
                        borderColor: '#6c63ff',
                        backgroundColor: 'rgba(108,99,255,0.1)',
                        tension: 0.3,
                        fill: true,
                    },
                    {
                        label: 'Stress Level',
                        data: entries.map(e => e.stress_level),
                        borderColor: '#ff6584',
                        backgroundColor: 'rgba(255,101,132,0.1)',
                        tension: 0.3,
                        fill: true,
                    },
                    {
                        label: 'Intensity',
                        data: entries.map(e => e.intensity),
                        borderColor: '#43b89c',
                        backgroundColor: 'rgba(67,184,156,0.1)',
                        tension: 0.3,
                        fill: true,
                    },
                ],
            },
            options: {
                responsive: true,
                scales: { y: { min: 0, max: 10 } },
            },
        });

        // --- Top Emotions doughnut ---
        const emotions = analyzer.top_emotions;
        new Chart(document.getElementById('emotionChart'), {
            type: 'doughnut',
            data: {
                labels: emotions.map(e => e.emotion_name),
                datasets: [{
                    data: emotions.map(e => e.count),
                    backgroundColor: ['#6c63ff', '#ff6584', '#43b89c', '#ffd166', '#ef476f', '#118ab2'],
                }],
            },
            options: { responsive: true },
        });

        // --- Top Stressors horizontal bar ---
        const stressors = analyzer.top_stressors;
        new Chart(document.getElementById('stressorChart'), {
            type: 'bar',
            data: {
                labels: stressors.map(s => s.factor_name),
                datasets: [{
                    label: 'Occurrences',
                    data: stressors.map(s => s.count),
                    backgroundColor: '#ff6584',
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });

        // --- Best activities list ---
        const actList = document.getElementById('topActivities');
        actList.innerHTML = analyzer.best_activities.length
            ? analyzer.best_activities
                .map(a => `<div>${a.activity_name} <small>(avg mood: ${a.avg_mood_level})</small></div>`)
                .join('')
            : '<div>No data yet. Log some activities!</div>';

        // --- Recommendations list ---
        const recList = document.getElementById('recommendations');
        recList.innerHTML = analyzer.recommendations.length
            ? analyzer.recommendations
                .map(r => `<div>Feeling <strong>${r.emotion_name}</strong>? Try <strong>${r.suggested_activity}</strong>.</div>`)
                .join('')
            : '<div>No recommendations yet.</div>';

        // --- Insight text ---
        document.getElementById('insightText').textContent = analyzer.insight;

    } catch (err) {
        console.error(err);
        alert('Could not load dashboard: ' + err.message);
    }
}

loadDashboard();
