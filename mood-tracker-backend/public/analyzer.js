let trendChartInstance = null;
let emotionChartInstance = null;

const DAYS = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];

function getWeekDates() {
    const today = new Date();
    const currentDay = today.getDay(); // 0=Sun, 1=Mon, ... 6=Sat
    const mondayOffset = currentDay === 0 ? -6 : 1 - currentDay;

    const start = new Date(today);
    start.setDate(today.getDate() + mondayOffset);
    start.setHours(0, 0, 0, 0);

    const end = new Date(start);
    end.setDate(start.getDate() + 6);
    end.setHours(23, 59, 59, 999);

    return { start, end };
}

function parseDateTime(date, time) {
    return new Date(`${date}T${time}`);
}

function isCurrentWeekItem(item) {
    const itemDate = parseDateTime(item.date, item.time);
    const { start, end } = getWeekDates();
    return itemDate >= start && itemDate <= end;
}

function getDayLabel(item) {
    return parseDateTime(item.date, item.time).toLocaleDateString("en-US", {
        weekday: "short"
    });
}

function safeNumber(value) {
    const num = Number(value);
    return Number.isFinite(num) ? num : 0;
}

function average(values) {
    if (!values.length) {
        return 0;
    }

    const total = values.reduce((sum, value) => sum + safeNumber(value), 0);
    return total / values.length;
}

function setText(id, value) {
    const el = document.getElementById(id);
    if (el) {
        el.textContent = value;
    }
}

function showFallbackCard(container, text) {
    container.innerHTML = `<div class="no-activity-card">${text}</div>`;
}

function getActivityIcon(activityName) {
    const name = activityName.toLowerCase();

    if (name.includes("gym")) return "🏋️";
    if (name.includes("walking")) return "🚶";
    if (name.includes("jogging")) return "🏃";
    if (name.includes("meditation")) return "🧘";
    if (name.includes("yoga")) return "🧎";
    if (name.includes("reading")) return "📚";
    if (name.includes("music")) return "🎵";
    if (name.includes("sleep")) return "😴";
    if (name.includes("gaming")) return "🎮";
    if (name.includes("friend")) return "💬";
    if (name.includes("journaling")) return "📝";
    if (name.includes("stretching")) return "🤸";
    if (name.includes("breathing")) return "🌬️";

    return "✨";
}

function renderTrendChart(labels, moodData, stressData) {
    const canvas = document.getElementById("trendChart");
    if (!canvas) return;

    const ctx = canvas.getContext("2d");

    if (trendChartInstance) {
        trendChartInstance.destroy();
    }

    trendChartInstance = new Chart(ctx, {
        type: "line",
        data: {
            labels,
            datasets: [
                {
                    label: "Mood Level",
                    data: moodData,
                    borderColor: "#4f46e5",
                    backgroundColor: "rgba(79, 70, 229, 0.12)",
                    tension: 0.3,
                    fill: false,
                    spanGaps: true
                },
                {
                    label: "Stress Level",
                    data: stressData,
                    borderColor: "#ef4444",
                    backgroundColor: "rgba(239, 68, 68, 0.12)",
                    tension: 0.3,
                    fill: false,
                    spanGaps: true
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
    const canvas = document.getElementById("emotionChart");
    if (!canvas) return;

    const ctx = canvas.getContext("2d");

    if (emotionChartInstance) {
        emotionChartInstance.destroy();
    }

    emotionChartInstance = new Chart(ctx, {
        type: "bar",
        data: {
            labels,
            datasets: [
                {
                    label: "Emotion Count",
                    data,
                    backgroundColor: [
                        "#4f46e5",
                        "#06b6d4",
                        "#10b981",
                        "#f59e0b",
                        "#ef4444"
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

async function fetchJson(url) {
    const response = await fetch(url);
    const rawText = await response.text();

    let result;
    try {
        result = JSON.parse(rawText);
    } catch (error) {
        throw new Error(`Invalid JSON response from ${url}`);
    }

    if (!response.ok || !result.success) {
        throw new Error(result.message || `Request failed for ${url}`);
    }

    return result;
}

function renderEmptyState() {
    setText("totalEntries", "0");
    setText("avgMood", "0.0");
    setText("avgStress", "0.0");
    setText("avgIntensity", "0.0");

    renderTrendChart(DAYS, [null, null, null, null, null, null, null], [null, null, null, null, null, null, null]);
    renderEmotionChart(["No Data"], [0]);

    const topActivitiesEl = document.getElementById("topActivities");
    const recommendationsEl = document.getElementById("recommendations");
    const moodBoostingActivitiesEl = document.getElementById("moodBoostingActivities");
const stressfulDaysEl = document.getElementById("stressfulDays");

if (moodBoostingActivitiesEl) {
    showFallbackCard(moodBoostingActivitiesEl, "No matched mood/activity data this week");
}

if (stressfulDaysEl) {
    showFallbackCard(stressfulDaysEl, "No stress trend data this week");
}

    showFallbackCard(topActivitiesEl, "No activity data this week");
    showFallbackCard(recommendationsEl, "No recommendations available");
}

function renderStats(entries) {
    setText("totalEntries", String(entries.length));
    setText("avgMood", average(entries.map(entry => entry.mood_level)).toFixed(1));
    setText("avgStress", average(entries.map(entry => entry.stress_level)).toFixed(1));
}

function renderWeeklyTrend(entries) {
    const grouped = {
        Mon: [],
        Tue: [],
        Wed: [],
        Thu: [],
        Fri: [],
        Sat: [],
        Sun: []
    };

    const sortedEntries = [...entries].sort((a, b) => {
        return parseDateTime(a.date, a.time) - parseDateTime(b.date, b.time);
    });

    sortedEntries.forEach(entry => {
        const day = getDayLabel(entry);
        if (grouped[day]) {
            grouped[day].push(entry);
        }
    });

    const moodData = [];
    const stressData = [];

    DAYS.forEach(day => {
        const dayEntries = grouped[day];

        if (!dayEntries.length) {
            moodData.push(null);
            stressData.push(null);
            return;
        }

        moodData.push(Number(average(dayEntries.map(entry => entry.mood_level)).toFixed(2)));
        stressData.push(Number(average(dayEntries.map(entry => entry.stress_level)).toFixed(2)));
    });

    renderTrendChart(DAYS, moodData, stressData);
}

function renderTopEmotions(entries) {
    const emotionCounts = {};

    entries.forEach(entry => {
        const raw = (entry.emotions || "").trim();
        if (!raw) return;

        raw.split(",")
            .map(emotion => emotion.trim())
            .filter(Boolean)
            .forEach(emotion => {
                emotionCounts[emotion] = (emotionCounts[emotion] || 0) + 1;
            });
    });

    const topEmotions = Object.entries(emotionCounts)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 5);

    if (!topEmotions.length) {
        renderEmotionChart(["No Data"], [0]);
        return;
    }

    renderEmotionChart(
        topEmotions.map(([emotion]) => emotion),
        topEmotions.map(([, count]) => count)
    );
}

function renderTopActivities(activities) {
    const topActivitiesEl = document.getElementById("topActivities");
    topActivitiesEl.innerHTML = "";

    if (!activities.length) {
        showFallbackCard(topActivitiesEl, "No activity data this week");
        return;
    }

    const counts = {};
    activities.forEach(activity => {
        const name = activity.activity_name;
        counts[name] = (counts[name] || 0) + 1;
    });

    const topActivities = Object.entries(counts)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 3);

    topActivities.forEach(([activity, count], index) => {
        const icon = getActivityIcon(activity);

        const card = document.createElement("div");
        card.className = "activity-rank-card";
        card.innerHTML = `
            <div class="activity-rank-number">${index + 1}.</div>
            <div class="activity-rank-icon">${icon}</div>
            <div class="activity-rank-name">${activity}</div>
            <div class="activity-rank-count">${count} time${count > 1 ? "s" : ""} this week</div>
        `;
        topActivitiesEl.appendChild(card);
    });
}

function renderRecommendations(recommendations) {
    const recommendationsEl = document.getElementById("recommendations");
    recommendationsEl.innerHTML = "";

    if (!recommendations.length) {
        showFallbackCard(recommendationsEl, "No recommendations available");
        return;
    }

    recommendations.forEach(rec => {
        const card = document.createElement("div");
        card.className = "recommendation-card";

        const confidenceText =
            rec.historical_match_count > 0
                ? `${rec.historical_match_count} matched historical entr${rec.historical_match_count === 1 ? "y" : "ies"}`
                : "No personal history yet";

        card.innerHTML = `
            <div class="recommendation-emotion">
                Match: ${rec.matched_on.emotion} | Stress ${rec.matched_on.stress_level} | Mood ${rec.matched_on.mood_level}
            </div>
            <div class="recommendation-arrow">→</div>
            <div class="recommendation-activity">${rec.activity_name}</div>
            <div class="recommendation-text">${rec.recommendation_text}</div>
            <div class="insight-card-subtext" style="margin-top: 10px;">
                Priority ${rec.priority_score} • ${confidenceText}
                ${rec.historical_match_count > 0
                    ? ` • Avg Mood ${Number(rec.avg_mood_after_activity).toFixed(2)} • Avg Stress ${Number(rec.avg_stress_after_activity).toFixed(2)}`
                    : ""
                }
            </div>
        `;

        recommendationsEl.appendChild(card);
    });
}

async function loadAnalyzerInsightsSafely() {
    try {
        const analyzerResult = await fetchJson("/api/analyzer");
        const recommendations = analyzerResult.data.recommendations || [];
        const bestMoodImprovingActivities = analyzerResult.data.best_mood_improving_activities || [];
        const mostStressfulDays = analyzerResult.data.most_stressful_days || [];

        renderRecommendations(recommendations);
        renderBestMoodImprovingActivities(bestMoodImprovingActivities);
        renderMostStressfulDays(mostStressfulDays);
    } catch (error) {
        console.error("Analyzer insight error:", error);
        renderRecommendations([]);
        renderBestMoodImprovingActivities([]);
        renderMostStressfulDays([]);
    }
}

async function loadAnalyzer() {
    try {
        const [moodResult, activityResult] = await Promise.all([
            fetchJson("/api/moods"),
            fetchJson("/api/activities")
        ]);

        const allEntries = moodResult.data.entries || [];
        const allActivities = activityResult.data.activities || [];

        const weekEntries = allEntries.filter(isCurrentWeekItem);
        const weekActivities = allActivities.filter(isCurrentWeekItem);

        if (!weekEntries.length) {
            renderEmptyState();
            await loadAnalyzerInsightsSafely();
            return;
        }

        renderStats(weekEntries);
        renderWeeklyTrend(weekEntries);
        renderTopEmotions(weekEntries);
        renderTopActivities(weekActivities);

        await loadAnalyzerInsightsSafely();
    } catch (error) {
        console.error("Analyzer load error:", error);
        renderEmptyState();
        try {
            await loadAnalyzerInsightsSafely();
        } catch (innerError) {
            console.error("Analyzer fallback insight load error:", innerError);
        }
    }
}

function renderBestMoodImprovingActivities(items) {
    const container = document.getElementById("moodBoostingActivities");
    if (!container) return;

    container.innerHTML = "";

    if (!items.length) {
        showFallbackCard(container, "No matched mood/activity data this week");
        return;
    }

    function getBoostIcon(activityName) {
        const name = activityName.toLowerCase();

        if (name.includes("walking")) return "🚶";
        if (name.includes("jogging")) return "🏃";
        if (name.includes("meditation")) return "🧘";
        if (name.includes("yoga")) return "🧎";
        if (name.includes("reading")) return "📚";
        if (name.includes("music")) return "🎵";
        if (name.includes("journaling")) return "📝";
        if (name.includes("stretching")) return "🤸";
        if (name.includes("breathing")) return "🌬️";
        if (name.includes("friend")) return "💬";

        return "✨";
    }

    function getRankLabel(index) {
        if (index === 0) return "Top Pick";
        if (index === 1) return "Strong Match";
        return "Helpful Option";
    }

    items.forEach((item, index) => {
        const card = document.createElement("div");
        card.className = "mood-boost-card";

        const moodScore = Number(item.avg_mood_after_activity);
        const progressWidth = Math.max(8, Math.min((moodScore / 5) * 100, 100));

        card.innerHTML = `
            <div class="mood-boost-rank">#${index + 1}</div>
            <div class="mood-boost-badge">${getRankLabel(index)}</div>

            <div class="mood-boost-icon-wrap">
                <div class="mood-boost-icon">${getBoostIcon(item.activity_name)}</div>
            </div>

            <div class="mood-boost-title">${item.activity_name}</div>

            <div class="mood-boost-score-row">
                <span class="mood-boost-score-label">Avg Mood After Activity</span>
                <span class="mood-boost-score-value">${moodScore.toFixed(2)}</span>
            </div>

            <div class="mood-boost-progress">
                <div class="mood-boost-progress-fill" style="width: ${progressWidth}%;"></div>
            </div>

            <div class="mood-boost-meta">
                Based on ${item.match_count} matched entr${Number(item.match_count) === 1 ? "y" : "ies"} this week
            </div>
        `;

        container.appendChild(card);
    });
}

function renderMostStressfulDays(items) {
    const container = document.getElementById("stressfulDays");
    if (!container) return;

    container.innerHTML = "";

    if (!items.length) {
        showFallbackCard(container, "No stress trend data this week");
        return;
    }

    items.forEach(item => {
        const card = document.createElement("div");
        card.className = "insight-card";
        card.innerHTML = `
            <div class="insight-card-title">${item.weekday}</div>
            <div class="insight-card-value">Avg Stress: ${Number(item.avg_stress).toFixed(2)}</div>
            <div class="insight-card-subtext">${item.entry_count} entr${Number(item.entry_count) === 1 ? "y" : "ies"} this week</div>
        `;
        container.appendChild(card);
    });
}

document.addEventListener("DOMContentLoaded", loadAnalyzer);