let trendChartInstance = null;
let emotionChartInstance = null;

function getWeekDates() {
    const today = new Date();
    const currentDay = today.getDay(); // 0 = Sun, 1 = Mon, ..., 6 = Sat

    const mondayOffset = currentDay === 0 ? -6 : 1 - currentDay;

    const start = new Date(today);
    start.setDate(today.getDate() + mondayOffset);
    start.setHours(0, 0, 0, 0);

    const end = new Date(start);
    end.setDate(start.getDate() + 6);
    end.setHours(23, 59, 59, 999);

    return { start, end };
}

function parseEntryDate(entry) {
    // Safer parsing for "YYYY-MM-DD" + "HH:MM:SS"
    return new Date(`${entry.date} ${entry.time}`);
}

function isCurrentWeekEntry(entry) {
    const entryDate = parseEntryDate(entry);
    const { start, end } = getWeekDates();
    return entryDate >= start && entryDate <= end;
}

function getDayLabel(entry) {
    const date = parseEntryDate(entry);
    return date.toLocaleDateString("en-US", { weekday: "short" });
}

function renderTrendChart(labels, moodData, stressData) {
    const ctx = document.getElementById("trendChart").getContext("2d");

    if (trendChartInstance) {
        trendChartInstance.destroy();
    }

    trendChartInstance = new Chart(ctx, {
        type: "line",
        data: {
            labels: labels,
            datasets: [
                {
                    label: "Mood Level",
                    data: moodData,
                    borderColor: "#4f46e5",
                    backgroundColor: "rgba(79, 70, 229, 0.12)",
                    tension: 0.3,
                    fill: false
                },
                {
                    label: "Stress Level",
                    data: stressData,
                    borderColor: "#ef4444",
                    backgroundColor: "rgba(239, 68, 68, 0.12)",
                    tension: 0.3,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    min: 0,
                    max: 5,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

function renderEmotionChart(labels, data) {
    const ctx = document.getElementById("emotionChart").getContext("2d");

    if (emotionChartInstance) {
        emotionChartInstance.destroy();
    }

    emotionChartInstance = new Chart(ctx, {
        type: "bar",
        data: {
            labels: labels,
            datasets: [
                {
                    label: "Emotion Count",
                    data: data,
                    backgroundColor: [
                        "#4f46e5",
                        "#06b6d4",
                        "#10b981",
                        "#f59e0b",
                        "#ef4444",
                        "#8b5cf6",
                        "#14b8a6",
                        "#f97316"
                    ]
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

async function loadAnalyzer() {
    const totalEntriesEl = document.getElementById("totalEntries");
    const avgMoodEl = document.getElementById("avgMood");
    const avgStressEl = document.getElementById("avgStress");
    const avgIntensityEl = document.getElementById("avgIntensity");
    const topActivitiesEl = document.getElementById("topActivities");

    try {
        const response = await fetch("/api/moods");
        const result = await response.json();

        if (!result.success) {
            alert(result.message || "Failed to load analyzer data.");
            return;
        }

        const allEntries = result.data.entries || [];
        const weekEntries = allEntries.filter(isCurrentWeekEntry);

        if (weekEntries.length === 0) {
            totalEntriesEl.textContent = "0";
            avgMoodEl.textContent = "0.0";
            avgStressEl.textContent = "0.0";
            avgIntensityEl.textContent = "0.0";
            topActivitiesEl.innerHTML = "<div class='list-pill'>No activity data this week</div>";

            renderTrendChart(["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"], [0, 0, 0, 0, 0, 0, 0], [0, 0, 0, 0, 0, 0, 0]);
            renderEmotionChart(["No Data"], [0]);
            return;
        }

        // Cards: current week only
        totalEntriesEl.textContent = weekEntries.length;

        const avgMood =
            weekEntries.reduce((sum, entry) => sum + Number(entry.mood_level || 0), 0) / weekEntries.length;

        const avgStress =
            weekEntries.reduce((sum, entry) => sum + Number(entry.stress_level || 0), 0) / weekEntries.length;

        const avgIntensity =
            weekEntries.reduce((sum, entry) => sum + Number(entry.intensity || 0), 0) / weekEntries.length;

        avgMoodEl.textContent = avgMood.toFixed(1);
        avgStressEl.textContent = avgStress.toFixed(1);
        avgIntensityEl.textContent = avgIntensity.toFixed(1);

        // Sort entries in time order
        const sortedEntries = [...weekEntries].sort((a, b) => parseEntryDate(a) - parseEntryDate(b));

        // Group by day
        const orderedDays = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
        const grouped = {
            Mon: [],
            Tue: [],
            Wed: [],
            Thu: [],
            Fri: [],
            Sat: [],
            Sun: []
        };

        sortedEntries.forEach(entry => {
            const day = getDayLabel(entry);
            if (grouped[day]) {
                grouped[day].push(entry);
            }
        });

        const labels = orderedDays;
        const moodData = [];
        const stressData = [];

        orderedDays.forEach(day => {
            const entries = grouped[day];

            if (entries.length === 0) {
                moodData.push(null);
                stressData.push(null);
            } else {
                const dayAvgMood =
                    entries.reduce((sum, entry) => sum + Number(entry.mood_level || 0), 0) / entries.length;

                const dayAvgStress =
                    entries.reduce((sum, entry) => sum + Number(entry.stress_level || 0), 0) / entries.length;

                moodData.push(Number(dayAvgMood.toFixed(2)));
                stressData.push(Number(dayAvgStress.toFixed(2)));
            }
        });

        renderTrendChart(labels, moodData, stressData);

        // Emotions: current week only
        const emotionCounts = {};
        sortedEntries.forEach(entry => {
            (entry.emotions || []).forEach(emotion => {
                emotionCounts[emotion] = (emotionCounts[emotion] || 0) + 1;
            });
        });

        const emotionLabels = Object.keys(emotionCounts);
        const emotionData = Object.values(emotionCounts);

        if (emotionLabels.length === 0) {
            renderEmotionChart(["No Data"], [0]);
        } else {
            renderEmotionChart(emotionLabels, emotionData);
        }

        // Top activities: current week only
        const activityCounts = {};
        sortedEntries.forEach(entry => {
            (entry.activities || []).forEach(activity => {
                activityCounts[activity] = (activityCounts[activity] || 0) + 1;
            });
        });

        const topActivities = Object.entries(activityCounts)
            .sort((a, b) => b[1] - a[1])
            .slice(0, 5);

        topActivitiesEl.innerHTML = "";

        if (topActivities.length === 0) {
            topActivitiesEl.innerHTML = "<div class='list-pill'>No activity data this week</div>";
        } else {
            topActivities.forEach(([activity, count]) => {
                const pill = document.createElement("div");
                pill.className = "list-pill";
                pill.textContent = `${activity} (${count})`;
                topActivitiesEl.appendChild(pill);
            });
        }

    } catch (error) {
        console.error("Analyzer load error:", error);
        alert("Could not load analyzer data.");
    }
}

document.addEventListener("DOMContentLoaded", loadAnalyzer);